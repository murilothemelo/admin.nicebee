<?php
/**
 * CRUD de Planos
 * GET, POST, PUT, DELETE /api/planos
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
$plano_id = $path_info ? ltrim($path_info, '/') : null;

// Criar tabela se não existir
createTableIfNotExists($db);

switch ($method) {
    case 'GET':
        if ($plano_id) {
            getPlano($db, $plano_id);
        } else {
            getPlanos($db);
        }
        break;
    
    case 'POST':
        createPlano($db, $user_data);
        break;
    
    case 'PUT':
        if ($plano_id) {
            updatePlano($db, $plano_id, $user_data);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do plano é obrigatório']);
        }
        break;
    
    case 'DELETE':
        if ($plano_id) {
            deletePlano($db, $plano_id, $user_data);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do plano é obrigatório']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        break;
}

function createTableIfNotExists($db) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS planos (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nome VARCHAR(255) NOT NULL,
            limite_mb INT NOT NULL DEFAULT 1000,
            usuarios_max INT NOT NULL DEFAULT 10,
            valor_mensal DECIMAL(10,2) NOT NULL,
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($sql);

        // Inserir planos padrão se não existirem
        $check = "SELECT COUNT(*) as count FROM planos";
        $stmt = $db->prepare($check);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] == 0) {
            $insert = "INSERT INTO planos (nome, limite_mb, usuarios_max, valor_mensal, status) VALUES 
                ('Básico', 500, 5, 99.90, 'ativo'),
                ('Profissional', 2000, 15, 199.90, 'ativo'),
                ('Empresarial', 5000, 50, 399.90, 'ativo'),
                ('Premium', 10000, 100, 699.90, 'ativo')";
            $db->exec($insert);
        }
    } catch(PDOException $exception) {
        error_log("Error creating planos table: " . $exception->getMessage());
    }
}

function getPlanos($db) {
    try {
        $query = "SELECT * FROM planos ORDER BY valor_mensal ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $planos]);
    } catch(PDOException $exception) {
        error_log("Error fetching planos: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar planos']);
    }
}

function getPlano($db, $plano_id) {
    try {
        $query = "SELECT * FROM planos WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$plano_id]);
        
        if ($stmt->rowCount() > 0) {
            $plano = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $plano]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Plano não encontrado']);
        }
    } catch(PDOException $exception) {
        error_log("Error fetching plano: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar plano']);
    }
}

function createPlano($db, $user_data) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    // Validações obrigatórias
    $required_fields = ['nome', 'limite_mb', 'usuarios_max', 'valor_mensal'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Validações de tipo e valor
    if (!is_numeric($data['limite_mb']) || $data['limite_mb'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Limite MB deve ser um número positivo']);
        return;
    }
    
    if (!is_numeric($data['usuarios_max']) || $data['usuarios_max'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Usuários máximo deve ser um número positivo']);
        return;
    }
    
    if (!is_numeric($data['valor_mensal']) || $data['valor_mensal'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valor mensal deve ser um número positivo']);
        return;
    }
    
    try {
        $query = "INSERT INTO planos (nome, limite_mb, usuarios_max, valor_mensal, status) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $data['nome'],
            intval($data['limite_mb']),
            intval($data['usuarios_max']),
            floatval($data['valor_mensal']),
            $data['status'] ?? 'ativo'
        ]);
        
        if ($result) {
            $plano_id = $db->lastInsertId();
            logAction($db, $user_data['id'], 'CRIAR_PLANO', "Plano {$data['nome']} criado com ID {$plano_id}");
            
            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'message' => 'Plano criado com sucesso',
                'data' => ['id' => $plano_id]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao criar plano']);
        }
        
    } catch(PDOException $exception) {
        error_log("Error creating plano: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao criar plano']);
    }
}

function updatePlano($db, $plano_id, $user_data) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    // Validações obrigatórias
    $required_fields = ['nome', 'limite_mb', 'usuarios_max', 'valor_mensal'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    try {
        $query = "UPDATE planos SET 
                  nome = ?,
                  limite_mb = ?,
                  usuarios_max = ?,
                  valor_mensal = ?,
                  status = ?
                  WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $data['nome'],
            intval($data['limite_mb']),
            intval($data['usuarios_max']),
            floatval($data['valor_mensal']),
            $data['status'],
            $plano_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            logAction($db, $user_data['id'], 'ATUALIZAR_PLANO', "Plano ID {$plano_id} atualizado");
            echo json_encode(['success' => true, 'message' => 'Plano atualizado com sucesso']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Plano não encontrado']);
        }
        
    } catch(PDOException $exception) {
        error_log("Error updating plano: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar plano']);
    }
}

function deletePlano($db, $plano_id, $user_data) {
    try {
        // Verificar se há clientes usando este plano
        $check_query = "SELECT COUNT(*) as count FROM clientes WHERE plano_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$plano_id]);
        
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Não é possível deletar plano com clientes vinculados']);
            return;
        }
        
        $delete_query = "DELETE FROM planos WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([$plano_id]);
        
        if ($delete_stmt->rowCount() > 0) {
            logAction($db, $user_data['id'], 'DELETAR_PLANO', "Plano ID {$plano_id} deletado");
            echo json_encode(['success' => true, 'message' => 'Plano deletado com sucesso']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Plano não encontrado']);
        }
        
    } catch(PDOException $exception) {
        error_log("Error deleting plano: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao deletar plano']);
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