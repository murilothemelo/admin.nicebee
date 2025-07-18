<?php
/**
 * Gestão de Planos
 */

$action = $_GET['action'] ?? 'list';
$message = '';

// Processar ações
if ($_POST) {
    switch ($action) {
        case 'create':
        case 'edit':
            $nome = sanitizeInput($_POST['nome']);
            $limite_mb = (int)$_POST['limite_mb'];
            $usuarios_max = (int)$_POST['usuarios_max'];
            $valor_mensal = (float)$_POST['valor_mensal'];
            $status = $_POST['status'];
            
            // Validações
            if (empty($nome) || $limite_mb <= 0 || $usuarios_max <= 0 || $valor_mensal <= 0) {
                $message = showAlert('error', 'Todos os campos são obrigatórios e devem ter valores válidos');
            } else {
                try {
                    if ($action === 'create') {
                        $stmt = $pdo->prepare("INSERT INTO planos (nome, limite_mb, usuarios_max, valor_mensal, status) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$nome, $limite_mb, $usuarios_max, $valor_mensal, $status]);
                        
                        logAction($_SESSION['user']['id'], 'CRIAR_PLANO', "Plano {$nome} criado");
                        $message = showAlert('success', 'Plano criado com sucesso!');
                    } else {
                        $id = (int)$_POST['id'];
                        $stmt = $pdo->prepare("UPDATE planos SET nome = ?, limite_mb = ?, usuarios_max = ?, valor_mensal = ?, status = ? WHERE id = ?");
                        $stmt->execute([$nome, $limite_mb, $usuarios_max, $valor_mensal, $status, $id]);
                        
                        logAction($_SESSION['user']['id'], 'ATUALIZAR_PLANO', "Plano ID {$id} atualizado");
                        $message = showAlert('success', 'Plano atualizado com sucesso!');
                    }
                } catch(PDOException $e) {
                    error_log("Plano error: " . $e->getMessage());
                    $message = showAlert('error', 'Erro ao salvar plano');
                }
            }
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            try {
                // Verificar se há clientes usando este plano
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM clientes WHERE plano_id = ?");
                $stmt->execute([$id]);
                $count = $stmt->fetch()['count'];
                
                if ($count > 0) {
                    $message = showAlert('error', 'Não é possível deletar plano com clientes vinculados');
                } else {
                    $stmt = $pdo->prepare("DELETE FROM planos WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    logAction($_SESSION['user']['id'], 'DELETAR_PLANO', "Plano ID {$id} deletado");
                    $message = showAlert('success', 'Plano deletado com sucesso!');
                }
            } catch(PDOException $e) {
                error_log("Delete plano error: " . $e->getMessage());
                $message = showAlert('error', 'Erro ao deletar plano');
            }
            break;
    }
}

// Buscar dados
try {
    $stmt = $pdo->query("SELECT * FROM planos ORDER BY valor_mensal ASC");
    $planos = $stmt->fetchAll();
    
    // Plano para edição
    $plano_edit = null;
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM planos WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $plano_edit = $stmt->fetch();
    }
    
} catch(PDOException $e) {
    error_log("Planos fetch error: " . $e->getMessage());
    $planos = [];
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Planos</h1>
            <p class="text-gray-600">Gerencie os planos de assinatura</p>
        </div>
        <a href="?page=planos&action=create" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Novo Plano
        </a>
    </div>

    <?= $message ?>

    <?php if ($action === 'create' || $action === 'edit'): ?>
        <!-- Formulário -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <?= $action === 'create' ? 'Novo Plano' : 'Editar Plano' ?>
            </h2>
            
            <form method="POST" class="space-y-6">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= $plano_edit['id'] ?>">
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Plano *</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($plano_edit['nome'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ex: Plano Básico" required>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Limite de Armazenamento (MB) *</label>
                        <input type="number" name="limite_mb" value="<?= $plano_edit['limite_mb'] ?? '' ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="1000" min="1" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Máximo de Usuários *</label>
                        <input type="number" name="usuarios_max" value="<?= $plano_edit['usuarios_max'] ?? '' ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="10" min="1" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Valor Mensal (R$) *</label>
                        <input type="number" step="0.01" name="valor_mensal" value="<?= $plano_edit['valor_mensal'] ?? '' ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="99.90" min="0.01" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="ativo" <?= ($plano_edit['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="inativo" <?= ($plano_edit['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex space-x-3 pt-4 border-t border-gray-200">
                    <a href="?page=planos" class="flex-1 bg-gray-200 text-gray-900 py-2 px-4 rounded-lg font-semibold hover:bg-gray-300 transition-colors text-center">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i><?= $action === 'create' ? 'Criar' : 'Atualizar' ?> Plano
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Lista de Planos -->
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-credit-card text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total de Planos</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count($planos) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Planos Ativos</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count(array_filter($planos, fn($p) => $p['status'] === 'ativo')) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Valor Médio</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= count($planos) ? formatCurrency(array_sum(array_column($planos, 'valor_mensal')) / count($planos)) : 'R$ 0,00' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Planos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($planos as $plano): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($plano['nome']) ?></h3>
                            <?= getStatusBadge($plano['status']) ?>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-gray-900">
                                    <?= formatCurrency($plano['valor_mensal']) ?>
                                </div>
                                <div class="text-sm text-gray-500">por mês</div>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-hdd w-4 h-4 mr-2 text-gray-400"></i>
                                    <span><?= $plano['limite_mb'] ?> MB de armazenamento</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-users w-4 h-4 mr-2 text-gray-400"></i>
                                    <span>Até <?= $plano['usuarios_max'] ?> usuários</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-xs text-gray-500">
                                Criado em <?= formatDate($plano['criado_em']) ?>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="?page=planos&action=edit&id=<?= $plano['id'] ?>" class="text-blue-600 hover:text-blue-900 p-1 rounded" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="deletePlano(<?= $plano['id'] ?>, '<?= htmlspecialchars($plano['nome']) ?>')" class="text-red-600 hover:text-red-900 p-1 rounded" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <p class="text-sm text-yellow-800">
                    <strong>Atenção:</strong> Certifique-se de que nenhum cliente está utilizando este plano antes de excluí-lo.
                </p>
            </div>
            
            <p class="text-sm text-gray-600 mb-6">
                Tem certeza que deseja excluir o plano <strong id="planoName"></strong>?
            </p>
            
            <div class="flex space-x-3">
                <button onclick="closeDeleteModal()" class="flex-1 bg-gray-200 text-gray-900 py-2 px-4 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                    Cancelar
                </button>
                <form method="POST" class="flex-1">
                    <input type="hidden" name="id" id="deletePlanoId">
                    <button type="submit" class="w-full bg-red-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-red-700 transition-colors">
                        Excluir Plano
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deletePlano(id, nome) {
    document.getElementById('deletePlanoId').value = id;
    document.getElementById('planoName').textContent = nome;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
}
</script>