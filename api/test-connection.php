<?php
/**
 * Script de teste de conexão e estrutura
 * URL: https://admin.nicebee.com.br/api/test-connection.php
 */

header('Content-Type: application/json');
include_once 'config/cors.php';
include_once 'config/database.php';
include_once 'config/jwt.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexão com banco estabelecida',
        'timestamp' => date('Y-m-d H:i:s'),
        'database' => 'Conectado',
        'jwt' => 'Classe carregada',
        'server_info' => [
            'php_version' => phpversion(),
            'mysql_version' => $db->getAttribute(PDO::ATTR_SERVER_VERSION),
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown'
        ]
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro de conexão: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>