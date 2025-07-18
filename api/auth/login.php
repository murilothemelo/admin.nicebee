<?php
/**
 * Login do Administrador
 * POST /api/auth/login
 */

include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../config/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Log de debug
error_log("Login attempt started");

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Receber dados JSON
$input = file_get_contents("php://input");
$data = json_decode($input, true);

error_log("Login attempt for: " . ($data['email'] ?? 'no email'));

if (!isset($data['email']) || !isset($data['senha'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios']);
    exit();
}

// Validações básicas
if (empty(trim($data['email'])) || empty(trim($data['senha']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email e senha não podem estar vazios']);
    exit();
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit();
}

try {
    // Primeiro, verificar se a tabela existe e criar se necessário
    $create_table = "CREATE TABLE IF NOT EXISTS usuarios_admin (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        senha_hash VARCHAR(255) NOT NULL,
        tipo ENUM('admin', 'operador') DEFAULT 'operador',
        status ENUM('ativo', 'inativo') DEFAULT 'ativo',
        ultimo_login TIMESTAMP NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($create_table);

    // Verificar se existe usuário admin padrão
    $check_admin = "SELECT COUNT(*) as count FROM usuarios_admin WHERE email = 'admin@nicebee.com.br'";
    $stmt = $db->prepare($check_admin);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        // Criar usuário admin padrão
        $admin_password = password_hash('123456', PASSWORD_DEFAULT);
        $insert_admin = "INSERT INTO usuarios_admin (nome, email, senha_hash, tipo, status) VALUES (?, ?, ?, 'admin', 'ativo')";
        $stmt = $db->prepare($insert_admin);
        $stmt->execute(['Administrador', 'admin@nicebee.com.br', $admin_password]);
        error_log("Admin user created");
    }

    // Buscar usuário
    $query = "SELECT id, nome, email, senha_hash, tipo, status FROM usuarios_admin WHERE email = ? AND status = 'ativo'";
    $stmt = $db->prepare($query);
    $stmt->execute([$data['email']]);


    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($data['senha'], $user['senha_hash'])) {
            
            // Atualizar último login
            $update_query = "UPDATE usuarios_admin SET ultimo_login = NOW() WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$user['id']]);

            // Gerar token JWT
            $token_data = [
                'id' => $user['id'],
                'nome' => $user['nome'],
                'email' => $user['email'],
                'tipo' => $user['tipo']
            ];

            $token = $jwt->encode($token_data);

            // Log de login (criar tabela se não existir)
            try {
                $create_logs = "CREATE TABLE IF NOT EXISTS logs_admin (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    usuario_id INT NOT NULL,
                    acao VARCHAR(100) NOT NULL,
                    detalhes TEXT,
                    ip VARCHAR(45),
                    user_agent TEXT,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $db->exec($create_logs);
                
                $log_query = "INSERT INTO logs_admin (usuario_id, acao, detalhes, ip) VALUES (?, 'LOGIN', 'Login realizado com sucesso', ?)";
                $log_stmt = $db->prepare($log_query);
                $log_stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
            } catch (Exception $e) {
                error_log("Error creating log: " . $e->getMessage());
            }

            unset($user['senha_hash']);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'token' => $token,
                'user' => $user
            ]);
        } else {
            error_log("Password verification failed for: " . $data['email']);
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Senha incorreta']);
        }
    } else {
        error_log("User not found or inactive: " . $data['email']);
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado ou inativo']);
    }

} catch(PDOException $exception) {
    error_log("Database error: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
} catch(Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>