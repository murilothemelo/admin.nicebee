<?php
/**
 * Atualização de Perfil
 * PUT /api/auth/update-profile
 */

include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../config/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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

if (!isset($data->nome) || !isset($data->email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nome e email são obrigatórios']);
    exit();
}

// Validar email
if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit();
}

try {
    // Verificar se o email já está em uso por outro usuário
    $check_query = "SELECT id FROM usuarios_admin WHERE email = :email AND id != :current_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $data->email);
    $check_stmt->bindParam(':current_id', $user_data['id']);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Este email já está sendo usado por outro usuário']);
        exit();
    }
    
    // Atualizar perfil
    $update_query = "UPDATE usuarios_admin SET nome = :nome, email = :email WHERE id = :id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':nome', $data->nome);
    $update_stmt->bindParam(':email', $data->email);
    $update_stmt->bindParam(':id', $user_data['id']);
    $update_stmt->execute();
    
    // Buscar dados atualizados
    $select_query = "SELECT id, nome, email, tipo, ultimo_login, status, criado_em FROM usuarios_admin WHERE id = :id";
    $select_stmt = $db->prepare($select_query);
    $select_stmt->bindParam(':id', $user_data['id']);
    $select_stmt->execute();
    
    $updated_user = $select_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log da ação
    $log_query = "INSERT INTO logs_admin (usuario_id, acao, detalhes, ip) VALUES (:usuario_id, 'UPDATE_PROFILE', 'Perfil atualizado pelo usuário', :ip)";
    $log_stmt = $db->prepare($log_query);
    $log_stmt->bindParam(':usuario_id', $user_data['id']);
    $log_stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
    $log_stmt->execute();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Perfil atualizado com sucesso',
        'data' => $updated_user
    ]);

} catch(PDOException $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>