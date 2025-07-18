<?php
/**
 * Gestão de Clientes
 */

$action = $_GET['action'] ?? 'list';
$message = '';

// Processar ações
if ($_POST) {
    switch ($action) {
        case 'create':
        case 'edit':
            $nome_fantasia = sanitizeInput($_POST['nome_fantasia']);
            $razao_social = sanitizeInput($_POST['razao_social']);
            $email = sanitizeInput($_POST['email']);
            $telefone = sanitizeInput($_POST['telefone']);
            $documento = sanitizeInput($_POST['documento']);
            $plano_id = (int)$_POST['plano_id'];
            $status = $_POST['status'];
            
            // Validações
            if (empty($nome_fantasia) || empty($razao_social) || empty($email) || empty($telefone) || empty($documento) || empty($plano_id)) {
                $message = showAlert('error', 'Todos os campos são obrigatórios');
            } elseif (!validateEmail($email)) {
                $message = showAlert('error', 'Email inválido');
            } else {
                try {
                    if ($action === 'create') {
                        // Criar cliente
                        $codigo_cliente = generateUniqueCode();
                        
                        // Criar banco de dados
                        $db_result = $database->createClientDatabase($codigo_cliente);
                        
                        if ($db_result['success']) {
                            $banco_senha_encrypted = base64_encode($db_result['banco_senha']);
                            
                            $stmt = $pdo->prepare("INSERT INTO clientes (codigo_cliente, nome_fantasia, razao_social, email, telefone, documento, plano_id, status, banco_nome, banco_usuario, banco_senha_encrypted, uso_mb) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
                            
                            $stmt->execute([
                                $codigo_cliente,
                                $nome_fantasia,
                                $razao_social,
                                $email,
                                $telefone,
                                $documento,
                                $plano_id,
                                $status,
                                $db_result['banco_nome'],
                                $db_result['banco_usuario'],
                                $banco_senha_encrypted
                            ]);
                            
                            logAction($_SESSION['user']['id'], 'CRIAR_CLIENTE', "Cliente {$nome_fantasia} criado");
                            $message = showAlert('success', 'Cliente criado com sucesso!');
                        } else {
                            $message = showAlert('error', 'Erro ao criar banco de dados: ' . $db_result['error']);
                        }
                    } else {
                        // Editar cliente
                        $id = (int)$_POST['id'];
                        $stmt = $pdo->prepare("UPDATE clientes SET nome_fantasia = ?, razao_social = ?, email = ?, telefone = ?, documento = ?, plano_id = ?, status = ? WHERE id = ?");
                        $stmt->execute([$nome_fantasia, $razao_social, $email, $telefone, $documento, $plano_id, $status, $id]);
                        
                        logAction($_SESSION['user']['id'], 'ATUALIZAR_CLIENTE', "Cliente ID {$id} atualizado");
                        $message = showAlert('success', 'Cliente atualizado com sucesso!');
                    }
                } catch(PDOException $e) {
                    error_log("Cliente error: " . $e->getMessage());
                    $message = showAlert('error', 'Erro ao salvar cliente');
                }
            }
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            try {
                // Buscar dados do cliente
                $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
                $stmt->execute([$id]);
                $cliente = $stmt->fetch();
                
                if ($cliente) {
                    // Deletar cliente
                    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    logAction($_SESSION['user']['id'], 'DELETAR_CLIENTE', "Cliente {$cliente['nome_fantasia']} deletado");
                    $message = showAlert('success', 'Cliente deletado com sucesso!');
                }
            } catch(PDOException $e) {
                error_log("Delete cliente error: " . $e->getMessage());
                $message = showAlert('error', 'Erro ao deletar cliente');
            }
            break;
    }
}

// Buscar dados
try {
    // Clientes
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(c.nome_fantasia LIKE ? OR c.razao_social LIKE ? OR c.email LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    if ($status_filter) {
        $where_conditions[] = "c.status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $pdo->prepare("SELECT c.*, p.nome as plano_nome, p.limite_mb, p.valor_mensal FROM clientes c LEFT JOIN planos p ON c.plano_id = p.id {$where_clause} ORDER BY c.criado_em DESC");
    $stmt->execute($params);
    $clientes = $stmt->fetchAll();
    
    // Planos para o formulário
    $stmt = $pdo->query("SELECT * FROM planos WHERE status = 'ativo' ORDER BY valor_mensal");
    $planos = $stmt->fetchAll();
    
    // Cliente para edição
    $cliente_edit = null;
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $cliente_edit = $stmt->fetch();
    }
    
} catch(PDOException $e) {
    error_log("Clientes fetch error: " . $e->getMessage());
    $clientes = [];
    $planos = [];
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Clientes</h1>
            <p class="text-gray-600">Gerencie todos os clientes do sistema</p>
        </div>
        <a href="?page=clientes&action=create" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Novo Cliente
        </a>
    </div>

    <?= $message ?>

    <?php if ($action === 'create' || $action === 'edit'): ?>
        <!-- Formulário -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <?= $action === 'create' ? 'Novo Cliente' : 'Editar Cliente' ?>
            </h2>
            
            <form method="POST" class="space-y-6">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= $cliente_edit['id'] ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome Fantasia *</label>
                        <input type="text" name="nome_fantasia" value="<?= htmlspecialchars($cliente_edit['nome_fantasia'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Razão Social *</label>
                        <input type="text" name="razao_social" value="<?= htmlspecialchars($cliente_edit['razao_social'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($cliente_edit['email'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefone *</label>
                        <input type="text" name="telefone" value="<?= htmlspecialchars($cliente_edit['telefone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Documento (CNPJ/CPF) *</label>
                        <input type="text" name="documento" value="<?= htmlspecialchars($cliente_edit['documento'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Plano *</label>
                        <select name="plano_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">Selecione um plano</option>
                            <?php foreach ($planos as $plano): ?>
                                <option value="<?= $plano['id'] ?>" <?= ($cliente_edit['plano_id'] ?? '') == $plano['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($plano['nome']) ?> - <?= formatCurrency($plano['valor_mensal']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="ativo" <?= ($cliente_edit['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="inativo" <?= ($cliente_edit['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                            <option value="bloqueado" <?= ($cliente_edit['status'] ?? '') === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                        </select>
                    </div>
                </div>
                
                <?php if ($action === 'create'): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 mr-2 mt-0.5"></i>
                            <div class="text-sm text-blue-800">
                                <p class="font-medium mb-1">Criação Automática:</p>
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Código único do cliente será gerado automaticamente</li>
                                    <li>Banco de dados será criado: <code>nicebeec_cliente_xxx</code></li>
                                    <li>Usuário do banco será criado: <code>nicebeec_usr_xxx</code></li>
                                    <li>Senha segura será gerada automaticamente</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="flex space-x-3 pt-4 border-t border-gray-200">
                    <a href="?page=clientes" class="flex-1 bg-gray-200 text-gray-900 py-2 px-4 rounded-lg font-semibold hover:bg-gray-300 transition-colors text-center">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i><?= $action === 'create' ? 'Criar' : 'Atualizar' ?> Cliente
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Lista de Clientes -->
        
        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <form method="GET" class="flex flex-col lg:flex-row gap-4">
                <input type="hidden" name="page" value="clientes">
                
                <div class="flex-1">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Buscar por nome, razão social ou email..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="lg:w-48">
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos os Status</option>
                        <option value="ativo" <?= ($_GET['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= ($_GET['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                        <option value="bloqueado" <?= ($_GET['status'] ?? '') === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                    </select>
                </div>
                
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
            </form>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count($clientes) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Ativos</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count(array_filter($clientes, fn($c) => $c['status'] === 'ativo')) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Bloqueados</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count(array_filter($clientes, fn($c) => $c['status'] === 'bloqueado')) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-database text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Bancos</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count($clientes) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plano</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uso do Banco</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criado em</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($clientes as $cliente): 
                            $usage_percentage = $cliente['limite_mb'] ? ($cliente['uso_mb'] / $cliente['limite_mb']) * 100 : 0;
                            $usage_color = $usage_percentage > 90 ? 'bg-red-500' : ($usage_percentage > 70 ? 'bg-yellow-500' : 'bg-green-500');
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($cliente['nome_fantasia']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($cliente['email']) ?></div>
                                        <div class="text-xs text-gray-400">Código: <?= htmlspecialchars($cliente['codigo_cliente']) ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($cliente['plano_nome'] ?? 'N/A') ?></div>
                                    <div class="text-sm text-gray-500"><?= formatCurrency($cliente['valor_mensal'] ?? 0) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <i class="fas fa-hdd text-gray-400 mr-2"></i>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between text-sm">
                                                <span><?= $cliente['uso_mb'] ?> MB</span>
                                                <span class="text-gray-500"><?= number_format($usage_percentage, 1) ?>%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                                <div class="h-2 rounded-full <?= $usage_color ?>" style="width: <?= min($usage_percentage, 100) ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= getStatusBadge($cliente['status']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= formatDate($cliente['criado_em']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="?page=clientes&action=edit&id=<?= $cliente['id'] ?>" class="text-blue-600 hover:text-blue-900 p-1 rounded" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteCliente(<?= $cliente['id'] ?>, '<?= htmlspecialchars($cliente['nome_fantasia']) ?>')" class="text-red-600 hover:text-red-900 p-1 rounded" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="p-6">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Confirmar Exclusão</h3>
                    <p class="text-sm text-gray-600">Esta ação não pode ser desfeita.</p>
                </div>
            </div>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <p class="text-sm text-red-800">
                    <strong>Atenção:</strong> Ao excluir este cliente, o banco de dados também será removido permanentemente.
                </p>
            </div>
            
            <p class="text-sm text-gray-600 mb-6">
                Tem certeza que deseja excluir o cliente <strong id="clienteName"></strong>?
            </p>
            
            <div class="flex space-x-3">
                <button onclick="closeDeleteModal()" class="flex-1 bg-gray-200 text-gray-900 py-2 px-4 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                    Cancelar
                </button>
                <form method="POST" class="flex-1">
                    <input type="hidden" name="id" id="deleteClienteId">
                    <button type="submit" class="w-full bg-red-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-red-700 transition-colors">
                        Excluir Cliente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteCliente(id, nome) {
    document.getElementById('deleteClienteId').value = id;
    document.getElementById('clienteName').textContent = nome;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
}
</script>