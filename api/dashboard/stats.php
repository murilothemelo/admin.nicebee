<?php
/**
 * Estatísticas do Dashboard
 * GET /api/dashboard/stats
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Criar tabelas se não existirem
createTablesIfNotExist($db);

try {
    $stats = [];
    
    // Total de clientes
    $query = "SELECT COUNT(*) as total FROM clientes";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_clientes'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
    
    // Clientes ativos
    $query = "SELECT COUNT(*) as total FROM clientes WHERE status = 'ativo'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['clientes_ativos'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
    
    // Receita mensal (faturas pagas no mês atual)
    $query = "SELECT COALESCE(SUM(valor), 0) as total FROM faturas 
              WHERE status = 'pago' AND MONTH(data_pagamento) = MONTH(NOW()) AND YEAR(data_pagamento) = YEAR(NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['receita_mensal'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
    
    // Uso total de MB (soma de todos os bancos)
    $query = "SELECT SUM(uso_mb) as total FROM clientes";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['uso_total_mb'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    
    // Faturas pendentes
    $query = "SELECT COUNT(*) as total FROM faturas WHERE status IN ('pendente', 'vencido')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['faturas_pendentes'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
    
    // Backups hoje
    $query = "SELECT COUNT(*) as total FROM backups WHERE DATE(criado_em) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['backups_hoje'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
    
    // Crescimento de clientes (últimos 6 meses)
    $crescimento = [];
    for ($i = 5; $i >= 0; $i--) {
        $mes = date('Y-m', strtotime("-$i months"));
        $query = "SELECT COUNT(*) as total FROM clientes WHERE DATE_FORMAT(criado_em, '%Y-%m') <= ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$mes]);
        $crescimento[] = [
            'mes' => date('M', strtotime("-$i months")),
            'total' => intval($stmt->fetch(PDO::FETCH_ASSOC)['total'])
        ];
    }
    $stats['crescimento_clientes'] = $crescimento;
    
    // Alertas
    $alertas = [];
    
    // Clientes próximos do limite
    $query = "SELECT c.nome_fantasia, c.uso_mb, p.limite_mb 
              FROM clientes c 
              JOIN planos p ON c.plano_id = p.id 
              WHERE c.status = 'ativo' AND (c.uso_mb / p.limite_mb) > 0.9";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $clientes_limite = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($clientes_limite as $cliente) {
        $alertas[] = [
            'tipo' => 'limite_disco',
            'titulo' => 'Limite de Disco',
            'mensagem' => "{$cliente['nome_fantasia']} próximo do limite ({$cliente['uso_mb']}MB/{$cliente['limite_mb']}MB)",
            'severidade' => 'warning'
        ];
    }
    
    // Faturas vencidas
    $query = "SELECT COUNT(*) as total FROM faturas WHERE status = 'vencido'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $faturas_vencidas = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
    
    if ($faturas_vencidas > 0) {
        $alertas[] = [
            'tipo' => 'faturas_vencidas',
            'titulo' => 'Faturas Vencidas',
            'mensagem' => "{$faturas_vencidas} faturas em atraso",
            'severidade' => 'error'
        ];
    }
    
    $stats['alertas'] = $alertas;
    
    echo json_encode(['success' => true, 'data' => $stats]);
    
} catch(PDOException $exception) {
    error_log("Error fetching dashboard stats: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar estatísticas']);
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
            
            FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($clientes_sql);

        // Criar tabela de faturas
        $faturas_sql = "CREATE TABLE IF NOT EXISTS faturas (
            id INT PRIMARY KEY AUTO_INCREMENT,
            cliente_id INT NOT NULL,
            referencia VARCHAR(100) UNIQUE NOT NULL,
            vencimento DATE NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            status ENUM('pendente', 'pago', 'vencido', 'cancelado') DEFAULT 'pendente',
            forma_pagamento VARCHAR(50) NULL,
            data_pagamento TIMESTAMP NULL,
            observacoes TEXT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($faturas_sql);

        // Criar tabela de backups
        $backups_sql = "CREATE TABLE IF NOT EXISTS backups (
            id INT PRIMARY KEY AUTO_INCREMENT,
            cliente_id INT NOT NULL,
            arquivo VARCHAR(255) NOT NULL,
            tamanho_mb DECIMAL(10,2) DEFAULT 0,
            status ENUM('processando', 'concluido', 'erro') DEFAULT 'processando',
            tipo ENUM('manual', 'automatico') DEFAULT 'manual',
            observacoes TEXT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($backups_sql);

    } catch(PDOException $exception) {
        error_log("Error creating tables: " . $exception->getMessage());
    }
}
?>