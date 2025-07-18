<?php
/**
 * CRUD de Faturas
 * GET, POST, PUT, DELETE /api/faturas
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
$fatura_id = $segments[0] ?? null;
$action = $segments[1] ?? null;

// Criar tabela se não existir
createTableIfNotExists($db);

switch ($method) {
    case 'GET':
        if ($fatura_id) {
            getFatura($db, $fatura_id);
        } else {
            getFaturas($db);
        }
        break;
    
    case 'POST':
        if ($action === 'gerar-mensais') {
            gerarFaturasMensais($db, $user_data);
        } else {
            createFatura($db, $user_data);
        }
        break;
    
    case 'PUT':
        if ($fatura_id && $action === 'marcar-pago') {
            marcarComoPago($db, $fatura_id, $user_data);
        } elseif ($fatura_id) {
            updateFatura($db, $fatura_id, $user_data);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID da fatura é obrigatório']);
        }
        break;
    
    case 'DELETE':
        if ($fatura_id) {
            deleteFatura($db, $fatura_id, $user_data);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID da fatura é obrigatório']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        break;
}

function createTableIfNotExists($db) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS faturas (
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
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_cliente (cliente_id),
            INDEX idx_referencia (referencia),
            INDEX idx_status (status),
            INDEX idx_vencimento (vencimento)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($sql);
    } catch(PDOException $exception) {
        error_log("Error creating faturas table: " . $exception->getMessage());
    }
}

function getFaturas($db) {
    try {
        $query = "SELECT f.*, c.nome_fantasia, c.email as cliente_email 
                  FROM faturas f 
                  LEFT JOIN clientes c ON f.cliente_id = c.id 
                  ORDER BY f.vencimento DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $faturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Atualizar status de faturas vencidas e criar objeto cliente
        foreach ($faturas as &$fatura) {
            if ($fatura['status'] === 'pendente' && strtotime($fatura['vencimento']) < time()) {
                $fatura['status'] = 'vencido';
                updateFaturaStatus($db, $fatura['id'], 'vencido');
            }
            
            // Criar objeto cliente para compatibilidade com frontend
            if ($fatura['nome_fantasia']) {
                $fatura['cliente'] = [
                    'nome_fantasia' => $fatura['nome_fantasia'],
                    'email' => $fatura['cliente_email']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'data' => $faturas]);
    } catch(PDOException $exception) {
        error_log("Error fetching faturas: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar faturas']);
    }
}

function getFatura($db, $fatura_id) {
    try {
        $query = "SELECT f.*, c.nome_fantasia, c.email as cliente_email 
                  FROM faturas f 
                  LEFT JOIN clientes c ON f.cliente_id = c.id 
                  WHERE f.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$fatura_id]);
        
        if ($stmt->rowCount() > 0) {
            $fatura = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($fatura['nome_fantasia']) {
                $fatura['cliente'] = [
                    'nome_fantasia' => $fatura['nome_fantasia'],
                    'email' => $fatura['cliente_email']
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $fatura]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Fatura não encontrada']);
        }
    } catch(PDOException $exception) {
        error_log("Error fetching fatura: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar fatura']);
    }
}

function createFatura($db, $user_data) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    // Validações obrigatórias
    $required_fields = ['cliente_id', 'valor', 'vencimento'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Validar valor
    if (!is_numeric($data['valor']) || $data['valor'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valor deve ser um número positivo']);
        return;
    }
    
    // Validar data
    if (!strtotime($data['vencimento'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data de vencimento inválida']);
        return;
    }
    
    try {
        // Gerar referência única
        $referencia = date('Y-m') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Verificar se referência já existe
        $check_ref = "SELECT id FROM faturas WHERE referencia = ?";
        $stmt = $db->prepare($check_ref);
        $stmt->execute([$referencia]);
        
        if ($stmt->rowCount() > 0) {
            $referencia = date('Y-m-d-H-i-s') . '-' . rand(100, 999);
        }
        
        $query = "INSERT INTO faturas (cliente_id, referencia, vencimento, valor, status) 
                  VALUES (?, ?, ?, ?, 'pendente')";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $data['cliente_id'],
            $referencia,
            $data['vencimento'],
            floatval($data['valor'])
        ]);
        
        if ($result) {
            $fatura_id = $db->lastInsertId();
            logAction($db, $user_data['id'], 'CRIAR_FATURA', "Fatura {$referencia} criada para cliente ID {$data['cliente_id']}");
            
            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'message' => 'Fatura criada com sucesso',
                'data' => ['id' => $fatura_id, 'referencia' => $referencia]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao criar fatura']);
        }
        
    } catch(PDOException $exception) {
        error_log("Error creating fatura: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao criar fatura']);
    }
}

function gerarFaturasMensais($db, $user_data) {
    try {
        $mes_referencia = date('Y-m');
        $vencimento = date('Y-m-15'); // Vencimento dia 15 do mês
        
        // Buscar clientes ativos que não têm fatura no mês atual
        $query = "SELECT c.id, c.nome_fantasia, p.valor_mensal 
                  FROM clientes c 
                  JOIN planos p ON c.plano_id = p.id 
                  WHERE c.status = 'ativo' 
                  AND c.id NOT IN (
                      SELECT cliente_id FROM faturas 
                      WHERE referencia LIKE ?
                  )";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$mes_referencia . '%']);
        
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $faturas_criadas = 0;
        
        foreach ($clientes as $cliente) {
            $referencia = $mes_referencia . '-' . str_pad($cliente['id'], 4, '0', STR_PAD_LEFT);
            
            $insert_query = "INSERT INTO faturas (cliente_id, referencia, vencimento, valor, status) 
                            VALUES (?, ?, ?, ?, 'pendente')";
            
            $insert_stmt = $db->prepare($insert_query);
            $result = $insert_stmt->execute([
                $cliente['id'],
                $referencia,
                $vencimento,
                floatval($cliente['valor_mensal'])
            ]);
            
            if ($result) {
                $faturas_criadas++;
            }
        }
        
        logAction($db, $user_data['id'], 'GERAR_FATURAS_MENSAIS', "{$faturas_criadas} faturas mensais geradas para {$mes_referencia}");
        
        echo json_encode([
            'success' => true, 
            'message' => "{$faturas_criadas} faturas mensais geradas com sucesso",
            'data' => ['faturas_criadas' => $faturas_criadas]
        ]);
        
    } catch(PDOException $exception) {
        error_log("Error generating monthly invoices: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao gerar faturas mensais']);
    }
}

function marcarComoPago($db, $fatura_id, $user_data) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    try {
        $query = "UPDATE faturas SET 
                  status = 'pago',
                  data_pagamento = NOW(),
                  forma_pagamento = ?
                  WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $data['forma_pagamento'] ?? 'manual',
            $fatura_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            logAction($db, $user_data['id'], 'MARCAR_FATURA_PAGO', "Fatura ID {$fatura_id} marcada como paga");
            echo json_encode(['success' => true, 'message' => 'Fatura marcada como paga']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Fatura não encontrada']);
        }
        
    } catch(PDOException $exception) {
        error_log("Error marking invoice as paid: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao marcar fatura como paga']);
    }
}

function updateFatura($db, $fatura_id, $user_data) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    try {
        $query = "UPDATE faturas SET 
                  vencimento = ?,
                  valor = ?,
                  status = ?
                  WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $data['vencimento'],
            floatval($data['valor']),
            $data['status'],
            $fatura_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            logAction($db, $user_data['id'], 'ATUALIZAR_FATURA', "Fatura ID {$fatura_id} atualizada");
            echo json_encode(['success' => true, 'message' => 'Fatura atualizada com sucesso']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Fatura não encontrada']);
        }
        
    } catch(PDOException $exception) {
        error_log("Error updating fatura: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar fatura']);
    }
}

function deleteFatura($db, $fatura_id, $user_data) {
    try {
        $delete_query = "DELETE FROM faturas WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([$fatura_id]);
        
        if ($delete_stmt->rowCount() > 0) {
            logAction($db, $user_data['id'], 'DELETAR_FATURA', "Fatura ID {$fatura_id} deletada");
            echo json_encode(['success' => true, 'message' => 'Fatura deletada com sucesso']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Fatura não encontrada']);
        }
        
    } catch(PDOException $exception) {
        error_log("Error deleting fatura: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao deletar fatura']);
    }
}

function updateFaturaStatus($db, $fatura_id, $status) {
    try {
        $query = "UPDATE faturas SET status = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$status, $fatura_id]);
    } catch(PDOException $exception) {
        error_log("Error updating fatura status: " . $exception->getMessage());
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