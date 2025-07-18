<?php
/**
 * Dashboard Principal
 */

// Buscar estatísticas
try {
    // Total de clientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes");
    $total_clientes = $stmt->fetch()['total'];
    
    // Clientes ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE status = 'ativo'");
    $clientes_ativos = $stmt->fetch()['total'];
    
    // Receita mensal
    $stmt = $pdo->query("SELECT COALESCE(SUM(valor), 0) as total FROM faturas WHERE status = 'pago' AND MONTH(data_pagamento) = MONTH(NOW()) AND YEAR(data_pagamento) = YEAR(NOW())");
    $receita_mensal = $stmt->fetch()['total'];
    
    // Uso total de MB
    $stmt = $pdo->query("SELECT SUM(uso_mb) as total FROM clientes");
    $uso_total_mb = $stmt->fetch()['total'] ?? 0;
    
    // Faturas pendentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM faturas WHERE status IN ('pendente', 'vencido')");
    $faturas_pendentes = $stmt->fetch()['total'];
    
    // Atividade recente
    $stmt = $pdo->query("SELECT l.*, u.nome as usuario_nome FROM logs_admin l LEFT JOIN usuarios_admin u ON l.usuario_id = u.id ORDER BY l.criado_em DESC LIMIT 10");
    $atividades = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $total_clientes = $clientes_ativos = $receita_mensal = $uso_total_mb = $faturas_pendentes = 0;
    $atividades = [];
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <div class="text-sm text-gray-500">
            Última atualização: <?= date('H:i:s') ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Clientes Ativos</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= $clientes_ativos ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-white"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center">
                <span class="text-sm font-medium text-green-600">+12%</span>
                <span class="text-sm text-gray-500 ml-1">vs mês anterior</span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Receita Mensal</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= formatCurrency($receita_mensal) ?></p>
                </div>
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-white"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center">
                <span class="text-sm font-medium text-green-600">+8%</span>
                <span class="text-sm text-gray-500 ml-1">vs mês anterior</span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Uso de Disco</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($uso_total_mb / 1024, 1) ?> GB</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-hdd text-white"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center">
                <span class="text-sm font-medium text-gray-600">+15%</span>
                <span class="text-sm text-gray-500 ml-1">vs mês anterior</span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Alertas</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= $faturas_pendentes ?></p>
                </div>
                <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-white"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center">
                <span class="text-sm font-medium text-green-600">-2</span>
                <span class="text-sm text-gray-500 ml-1">vs mês anterior</span>
            </div>
        </div>
    </div>

    <!-- Charts and Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Crescimento de Clientes -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Crescimento de Clientes</h3>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-users text-blue-500"></i>
                    <span class="text-sm font-medium text-gray-600"><?= $total_clientes ?> total</span>
                </div>
            </div>
            
            <div class="space-y-4">
                <?php
                $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
                $valores = [15, 23, 31, 28, 35, $total_clientes];
                $max_valor = max($valores);
                
                foreach ($meses as $i => $mes):
                    $valor = $valores[$i];
                    $percentage = ($valor / $max_valor) * 100;
                ?>
                    <div class="flex items-center">
                        <div class="w-12 text-sm font-medium text-gray-600"><?= $mes ?></div>
                        <div class="flex-1 mx-4">
                            <div class="bg-gray-200 rounded-full h-2 relative overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-1000" style="width: <?= $percentage ?>%"></div>
                            </div>
                        </div>
                        <div class="w-12 text-sm font-semibold text-gray-900 text-right"><?= $valor ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-6 p-4 bg-gradient-to-r from-green-50 to-blue-50 rounded-lg border border-green-200">
                <div class="flex items-center">
                    <i class="fas fa-trending-up text-green-600 mr-2"></i>
                    <span class="text-sm text-green-800">
                        Crescimento de <strong>133%</strong> nos últimos 6 meses
                    </span>
                </div>
            </div>
        </div>

        <!-- Atividade Recente -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Atividade Recente</h3>
                    <i class="fas fa-history text-gray-500"></i>
                </div>
            </div>
            
            <div class="p-6">
                <div class="space-y-4">
                    <?php foreach ($atividades as $atividade): ?>
                        <div class="flex items-start space-x-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-blue-100 text-blue-600">
                                <?php
                                $icons = [
                                    'LOGIN' => 'fas fa-sign-in-alt',
                                    'CRIAR_CLIENTE' => 'fas fa-user-plus',
                                    'GERAR_BACKUP' => 'fas fa-database',
                                    'MARCAR_FATURA_PAGO' => 'fas fa-check'
                                ];
                                $icon = $icons[$atividade['acao']] ?? 'fas fa-info';
                                ?>
                                <i class="<?= $icon ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900"><?= ucfirst(str_replace('_', ' ', strtolower($atividade['acao']))) ?></p>
                                    <span class="text-xs text-gray-500"><?= formatDateTime($atividade['criado_em']) ?></span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($atividade['detalhes']) ?></p>
                                <p class="text-xs text-gray-500 mt-1">por <?= htmlspecialchars($atividade['usuario_nome'] ?? 'Sistema') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <a href="?page=logs" class="w-full text-center text-sm text-blue-600 hover:text-blue-700 font-medium">
                        Ver histórico completo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>