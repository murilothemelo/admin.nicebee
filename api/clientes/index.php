<?php
/**
 * CRUD de Clientes
 * GET, POST, PUT, DELETE /api/clientes
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
$cliente_id = $path_info ? ltrim($path_info, '/') : null;

// Criar tabelas se não existirem
createTablesIfNotExist($db);

switch ($method) {
    case 'GET':
        if ($cliente_id) {
            getCliente($db, $cliente_id);
        } else {
            getClientes($db);
        }
        break;
    
    case 'POST':
        createCliente($db, $database, $user_data);
        break;
    
    case 'PUT':
        if ($cliente_id) {
            updateCliente($db, $cliente_id, $user_data);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do cliente é obrigatório']);
        }
        break;
    
    case 'DELETE':
        if ($cliente_id) {
            deleteCliente($db, $database, $cliente_id, $user_data);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do cliente é obrigatório']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        break;
}

function createTablesIfNotExist($db) {
    try {
        // Criar tabela de planos
        $planos_sql = "CREATE TABLE IF NOT EXISTS planos (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nome VARCHAR(255) NOT NULL,
            limite_mb INT NOT NULL DEFAULT 1000,
            usuarios_max INT NOT NULL DEFAULT 10,
            valor_mensal DECIMAL(10,2) NOT NULL,
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($planos_sql);

        // Inserir planos padrão se não existirem
        $check_planos = "SELECT COUNT(*) as count FROM planos";
        $stmt = $db->prepare($check_planos);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] == 0) {
            $insert_planos = "INSERT INTO planos (nome, limite_mb, usuarios_max, valor_mensal, status) VALUES 
                ('Básico', 500, 5, 99.90, 'ativo'),
                ('Profissional', 2000, 15, 199.90, 'ativo'),
                ('Empresarial', 5000, 50, 399.90, 'ativo'),
                ('Premium', 10000, 100, 699.90, 'ativo')";
            $db->exec($insert_planos);
        }

        // Criar tabela de clientes
        $clientes_sql = "CREATE TABLE IF NOT EXISTS clientes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            codigo_cliente VARCHAR(50) UNIQUE NOT NULL,
            nome_fantasia VARCHAR(255) NOT NULL,
            razao_social VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            telefone VARCHAR(20),
            documento VARCHAR(20),
            plano_id INT,
            status ENUM('ativo', 'inativo', 'bloqueado') DEFAULT 'ativo',
            banco_nome VARCHAR(100) NOT NULL,
            banco_usuario VARCHAR(100) NOT NULL,
            banco_senha_encrypted TEXT NOT NULL,
            uso_mb INT DEFAULT 0,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE SET NULL,
            INDEX idx_codigo_cliente (codigo_cliente),
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_plano (plano_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($clientes_sql);

        // Criar tabela de logs se não existir
        $logs_sql = "CREATE TABLE IF NOT EXISTS logs_admin (
            id INT PRIMARY KEY AUTO_INCREMENT,
            usuario_id INT NOT NULL,
            acao VARCHAR(100) NOT NULL,
            detalhes TEXT,
            ip VARCHAR(45),
            user_agent TEXT,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($logs_sql);

    } catch(PDOException $exception) {
        error_log("Error creating tables: " . $exception->getMessage());
    }
}

function getClientes($db) {
    try {
        $query = "SELECT c.*, p.nome as plano_nome, p.limite_mb, p.valor_mensal 
                  FROM clientes c 
                  LEFT JOIN planos p ON c.plano_id = p.id 
                  ORDER BY c.criado_em DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular uso real do banco para cada cliente
        foreach ($clientes as &$cliente) {
            $cliente['uso_mb'] = calculateDatabaseSize($db, $cliente['banco_nome']);
            // Criar objeto plano para compatibilidade com frontend
            if ($cliente['plano_nome']) {
                $cliente['plano'] = [
                    'nome' => $cliente['plano_nome'],
                    'limite_mb' => $cliente['limite_mb'],
                    'valor_mensal' => $cliente['valor_mensal']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'data' => $clientes]);
    } catch(PDOException $exception) {
        error_log("Error fetching clients: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar clientes']);
    }
}

function getCliente($db, $cliente_id) {
    try {
        $query = "SELECT c.*, p.nome as plano_nome, p.limite_mb, p.valor_mensal 
                  FROM clientes c 
                  LEFT JOIN planos p ON c.plano_id = p.id 
                  WHERE c.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$cliente_id]);
        
        if ($stmt->rowCount() > 0) {
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            $cliente['uso_mb'] = calculateDatabaseSize($db, $cliente['banco_nome']);
            
            if ($cliente['plano_nome']) {
                $cliente['plano'] = [
                    'nome' => $cliente['plano_nome'],
                    'limite_mb' => $cliente['limite_mb'],
                    'valor_mensal' => $cliente['valor_mensal']
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $cliente]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
        }
    } catch(PDOException $exception) {
        error_log("Error fetching client: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar cliente']);
    }
}

function createCliente($db, $database, $user_data) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    error_log("Creating client with data: " . json_encode($data));
    
    // Validações obrigatórias
    $required_fields = ['nome_fantasia', 'razao_social', 'email', 'telefone', 'documento', 'plano_id'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Validar email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        return;
    }
    
    // Validar se plano existe
    $check_plano = "SELECT id FROM planos WHERE id = ? AND status = 'ativo'";
    $stmt = $db->prepare($check_plano);
    $stmt->execute([$data['plano_id']]);
    if ($stmt->rowCount() == 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Plano inválido ou inativo']);
        return;
    }
    
    try {
        $db->beginTransaction();
        
        // Gerar código único do cliente
        $codigo_cliente = generateUniqueCode($db);
        error_log("Generated client code: " . $codigo_cliente);
        
        // Criar banco de dados e usuário
        $db_result = $database->createClientDatabase($codigo_cliente);
        
        if (!$db_result['success']) {
            $db->rollBack();
            error_log("Database creation failed: " . $db_result['error']);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao criar banco de dados: ' . $db_result['error']]);
            return;
        }
        
        error_log("Database created successfully: " . json_encode($db_result));
        
        // Criptografar senha do banco
        $banco_senha_encrypted = base64_encode($db_result['banco_senha']);
        
        // Inserir cliente no sistema admin
        $query = "INSERT INTO clientes (codigo_cliente, nome_fantasia, razao_social, email, telefone, documento, plano_id, status, banco_nome, banco_usuario, banco_senha_encrypted, uso_mb) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $codigo_cliente,
            $data['nome_fantasia'],
            $data['razao_social'],
            $data['email'],
            $data['telefone'],
            $data['documento'],
            $data['plano_id'],
            $data['status'] ?? 'ativo',
            $db_result['banco_nome'],
            $db_result['banco_usuario'],
            $banco_senha_encrypted
        ]);
        
        if (!$result) {
            $db->rollBack();
            error_log("Failed to insert client into database");
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar cliente no banco']);
            return;
        }
        
        $cliente_id = $db->lastInsertId();
        error_log("Client inserted with ID: " . $cliente_id);
        
        // Log da ação
        logAction($db, $user_data['id'], 'CRIAR_CLIENTE', "Cliente {$data['nome_fantasia']} criado com ID {$cliente_id}");
        
        $db->commit();
        
        http_response_code(201);
        echo json_encode([
            'success' => true, 
            'message' => 'Cliente criado com sucesso',
            'data' => [
                'id' => $cliente_id,
                'codigo_cliente' => $codigo_cliente,
                'banco_nome' => $db_result['banco_nome'],
                'banco_usuario' => $db_result['banco_usuario']
            ]
        ]);
        
    } catch(PDOException $exception) {
        $db->rollBack();
        error_log("Database error creating client: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao criar cliente: ' . $exception->getMessage()]);
    } catch(Exception $e) {
        $db->rollBack();
        error_log("General error creating client: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao criar cliente: ' . $e->getMessage()]);
    }
}

function updateCliente($db, $cliente_id, $user_data) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    // Validações obrigatórias
    $required_fields = ['nome_fantasia', 'razao_social', 'email', 'telefone', 'documento', 'plano_id'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Validar email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        return;
    }
    
    try {
        $query = "UPDATE clientes SET 
                  nome_fantasia = ?,
                  razao_social = ?,
                  email = ?,
                  telefone = ?,
                  documento = ?,
                  plano_id = ?,
                  status = ?
                  WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $data['nome_fantasia'],
            $data['razao_social'],
            $data['email'],
            $data['telefone'],
            $data['documento'],
            $data['plano_id'],
            $data['status'],
            $cliente_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            logAction($db, $user_data['id'], 'ATUALIZAR_CLIENTE', "Cliente ID {$cliente_id} atualizado");
            echo json_encode(['success' => true, 'message' => 'Cliente atualizado com sucesso']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
        }
        
    } catch(PDOException $exception) {
        error_log("Error updating client: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar cliente']);
    }
}

function deleteCliente($db, $database, $cliente_id, $user_data) {
    try {
        // Buscar dados do cliente antes de deletar
        $query = "SELECT codigo_cliente, nome_fantasia, banco_nome, banco_usuario FROM clientes WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$cliente_id]);
        
        if ($stmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
            return;
        }
        
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $db->beginTransaction();
        
        // Deletar cliente do sistema admin
        $delete_query = "DELETE FROM clientes WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([$cliente_id]);
        
        // Deletar banco de dados e usuário
        $database->dropClientDatabase($cliente['banco_nome'], $cliente['banco_usuario']);
        
        logAction($db, $user_data['id'], 'DELETAR_CLIENTE', "Cliente {$cliente['nome_fantasia']} (ID {$cliente_id}) deletado");
        
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => 'Cliente deletado com sucesso']);
        
    } catch(PDOException $exception) {
        $db->rollBack();
        error_log("Error deleting client: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao deletar cliente']);
    }
}

function generateUniqueCode($db) {
    do {
        $code = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6);
        $query = "SELECT id FROM clientes WHERE codigo_cliente = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$code]);
    } while ($stmt->rowCount() > 0);
    
    return $code;
}

function calculateDatabaseSize($db, $banco_nome) {
    try {
        $query = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                  FROM information_schema.tables 
                  WHERE table_schema = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$banco_nome]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return floatval($result['size_mb'] ?: 0);
    } catch(PDOException $exception) {
        error_log("Error calculating database size: " . $exception->getMessage());
        return 0;
    }
}

function logAction($db, $usuario_id, $acao, $detalhes) {
    try {
        $query = "INSERT INTO logs_admin (usuario_id, acao, detalhes, ip) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$usuario_id, $acao, $detalhes, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    } catch(PDOException $exception) {
        error_log("Error logging action: " . $exception->getMessage());
    }
}
?>