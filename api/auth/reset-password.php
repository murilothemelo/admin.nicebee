<?php
/**
 * Reset de Senha
 * POST /api/auth/reset-password
 */

include_once '../config/cors.php';
include_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Receber dados JSON
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email é obrigatório']);
    exit();
}

try {
    // Verificar se o usuário existe
    $query = "SELECT id, nome, email FROM usuarios_admin WHERE email = :email AND status = 'ativo'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Email não encontrado no sistema']);
        exit();
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Gerar token de reset
    $reset_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora
    
    // Salvar token no banco (criar tabela se não existir)
    $create_table_query = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES usuarios_admin(id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_expires (expires_at)
    )";
    $db->exec($create_table_query);
    
    // Inserir token
    $insert_query = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(':user_id', $user['id']);
    $insert_stmt->bindParam(':token', $reset_token);
    $insert_stmt->bindParam(':expires_at', $expires_at);
    $insert_stmt->execute();
    
    // Em produção, enviar email aqui
    // Por enquanto, apenas simular o envio
    
    // Log da ação
    $log_query = "INSERT INTO logs_admin (usuario_id, acao, detalhes, ip) VALUES (:usuario_id, 'RESET_PASSWORD_REQUEST', 'Solicitação de reset de senha', :ip)";
    $log_stmt = $db->prepare($log_query);
    $log_stmt->bindParam(':usuario_id', $user['id']);
    $log_stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
    $log_stmt->execute();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Email de recuperação enviado com sucesso',
        'debug_token' => $reset_token // Remover em produção
    ]);

} catch(PDOException $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>