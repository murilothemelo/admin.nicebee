<?php
/**
 * Sistema de Backups
 * GET, POST /api/backups
 */

include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../config/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Validar token
$user_data = $jwt->validateToken();
if (!$user_data) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token inválido']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
$segments = explode('/', trim($path_info, '/'));
$action = $segments[0] ?? null;
$cliente_id = $segments[1] ?? null;

switch ($method) {
    case 'GET':
        if ($action === 'cliente' && $cliente_id) {
            getBackupsCliente($db, $cliente_id);
        } else {
            getBackups($db);
        }
        break;
    
    case 'POST':
        if ($action === 'criar') {
            criarBackup($db, $database, $user_data);
        } elseif ($action === 'restaurar') {
            restaurarBackup($db, $database, $user_data);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        break;
}

function getBackups($db) {
    try {
        $query = "SELECT b.*, c.nome_fantasia, c.codigo_cliente 
                  FROM backups b 
                  LEFT JOIN clientes c ON b.cliente_id = c.id 
                  ORDER BY b.criado_em DESC 
                  LIMIT 100";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $backups]);
    } catch(PDOException $exception) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar backups']);
    }
}

function getBackupsCliente($db, $cliente_id) {
    try {
        $query = "SELECT b.*, c.nome_fantasia 
                  FROM backups b 
                  LEFT JOIN clientes c ON b.cliente_id = c.id 
                  WHERE b.cliente_id = :cliente_id 
                  ORDER BY b.criado_em DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->execute();
        
        $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $backups]);
    } catch(PDOException $exception) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar backups do cliente']);
    }
}

function criarBackup($db, $database, $user_data) {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->cliente_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID do cliente é obrigatório']);
        return;
    }
    
    try {
        // Buscar dados do cliente
        $query = "SELECT codigo_cliente, nome_fantasia, banco_nome, banco_usuario, banco_senha_encrypted 
                  FROM clientes WHERE id = :cliente_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cliente_id', $data->cliente_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
            return;
        }
        
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Descriptografar senha do banco
        $banco_senha = openssl_decrypt($cliente['banco_senha_encrypted'], 'AES-256-CBC', 'nicebee_encryption_key', 0, '1234567890123456');
        
        // Criar nome do arquivo de backup
        $timestamp = date('Y-m-d_H-i-s');
        $arquivo_backup = "backup_{$cliente['codigo_cliente']}_{$timestamp}.sql";
        $caminho_backup = "../backups/{$arquivo_backup}";
        
        // Registrar backup como "processando"
        $insert_query = "INSERT INTO backups (cliente_id, arquivo, status, tipo) 
                        VALUES (:cliente_id, :arquivo, 'processando', :tipo)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':cliente_id', $data->cliente_id);
        $insert_stmt->bindParam(':arquivo', $arquivo_backup);
        $insert_stmt->bindParam(':tipo', $data->tipo ?? 'manual');
        $insert_stmt->execute();
        
        $backup_id = $db->lastInsertId();
        
        // Executar mysqldump
        $comando = "mysqldump -h localhost -u {$cliente['banco_usuario']} -p{$banco_senha} {$cliente['banco_nome']} > {$caminho_backup} 2>&1";
        $output = [];
        $return_code = 0;
        
        exec($comando, $output, $return_code);
        
        if ($return_code === 0 && file_exists($caminho_backup)) {
            // Backup bem-sucedido
            $tamanho_mb = round(filesize($caminho_backup) / 1024 / 1024, 2);
            
            $update_query = "UPDATE backups SET status = 'concluido', tamanho_mb = :tamanho_mb WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':tamanho_mb', $tamanho_mb);
            $update_stmt->bindParam(':id', $backup_id);
            $update_stmt->execute();
            
            logAction($db, $user_data['id'], 'CRIAR_BACKUP', "Backup criado para cliente {$cliente['nome_fantasia']} - {$arquivo_backup}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Backup criado com sucesso',
                'data' => [
                    'id' => $backup_id,
                    'arquivo' => $arquivo_backup,
                    'tamanho_mb' => $tamanho_mb
                ]
            ]);
        } else {
            // Erro no backup
            $update_query = "UPDATE backups SET status = 'erro' WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':id', $backup_id);
            $update_stmt->execute();
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao criar backup',
                'error' => implode("\n", $output)
            ]);
        }
        
    } catch(PDOException $exception) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao processar backup']);
    }
}

function restaurarBackup($db, $database, $user_data) {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->backup_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID do backup é obrigatório']);
        return;
    }
    
    try {
        // Buscar dados do backup e cliente
        $query = "SELECT b.arquivo, c.codigo_cliente, c.nome_fantasia, c.banco_nome, c.banco_usuario, c.banco_senha_encrypted 
                  FROM backups b 
                  JOIN clientes c ON b.cliente_id = c.id 
                  WHERE b.id = :backup_id AND b.status = 'concluido'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':backup_id', $data->backup_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Backup não encontrado ou não está concluído']);
            return;
        }
        
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);
        $caminho_backup = "../backups/{$backup['arquivo']}";
        
        if (!file_exists($caminho_backup)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Arquivo de backup não encontrado']);
            return;
        }
        
        // Descriptografar senha do banco
        $banco_senha = openssl_decrypt($backup['banco_senha_encrypted'], 'AES-256-CBC', 'nicebee_encryption_key', 0, '1234567890123456');
        
        // Executar restauração
        $comando = "mysql -h localhost -u {$backup['banco_usuario']} -p{$banco_senha} {$backup['banco_nome']} < {$caminho_backup} 2>&1";
        $output = [];
        $return_code = 0;
        
        exec($comando, $output, $return_code);
        
        if ($return_code === 0) {
            logAction($db, $user_data['id'], 'RESTAURAR_BACKUP', "Backup restaurado para cliente {$backup['nome_fantasia']} - {$backup['arquivo']}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Backup restaurado com sucesso'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao restaurar backup',
                'error' => implode("\n", $output)
            ]);
        }
        
    } catch(PDOException $exception) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao processar restauração']);
    }
}

function logAction($db, $usuario_id, $acao, $detalhes) {
    try {
        $query = "INSERT INTO logs_admin (usuario_id, acao, detalhes, ip) VALUES (:usuario_id, :acao, :detalhes, :ip)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':acao', $acao);
        $stmt->bindParam(':detalhes', $detalhes);
        $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
        $stmt->execute();
    } catch(PDOException $exception) {
        // Log error silently
    }
}
?>