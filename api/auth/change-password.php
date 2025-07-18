<?php
/**
 * Alteração de Senha
 * POST /api/auth/change-password
 */

include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../config/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

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

// Receber dados JSON
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->current_password) || !isset($data->new_password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Senha atual e nova senha são obrigatórias']);
    exit();
}

// Validar nova senha
if (strlen($data->new_password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A nova senha deve ter pelo menos 8 caracteres']);
    exit();
}

try {
    // Buscar usuário atual
    $query = "SELECT id, nome, email, senha_hash FROM usuarios_admin WHERE id = :id AND status = 'ativo'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $user_data['id']);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit();
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar senha atual
    if (!password_verify($data->current_password, $user['senha_hash'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Senha atual incorreta']);
        exit();
    }
    
    // Gerar hash da nova senha
    $new_password_hash = password_hash($data->new_password, PASSWORD_DEFAULT);
    
    // Atualizar senha no banco
    $update_query = "UPDATE usuarios_admin SET senha_hash = :senha_hash WHERE id = :id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':senha_hash', $new_password_hash);
    $update_stmt->bindParam(':id', $user['id']);
    $update_stmt->execute();
    
    // Log da ação
    $log_query = "INSERT INTO logs_admin (usuario_id, acao, detalhes, ip) VALUES (:usuario_id, 'CHANGE_PASSWORD', 'Senha alterada pelo usuário', :ip)";
    $log_stmt = $db->prepare($log_query);
    $log_stmt->bindParam(':usuario_id', $user['id']);
    $log_stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
    $log_stmt->execute();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Senha alterada com sucesso'
    ]);

} catch(PDOException $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>