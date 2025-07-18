<div class="w-64 bg-white shadow-lg border-r border-gray-200">
    <div class="p-6 border-b border-gray-200">
        <h1 class="text-xl font-bold text-gray-900">NiceBee Admin</h1>
        <p class="text-sm text-gray-600">Painel Multi-Tenant</p>
    </div>
    
    <nav class="mt-6">
        <?php
        $menuItems = [
            'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt'],
            'clientes' => ['label' => 'Clientes', 'icon' => 'fas fa-users'],
            'planos' => ['label' => 'Planos', 'icon' => 'fas fa-credit-card'],
            'faturas' => ['label' => 'Faturas', 'icon' => 'fas fa-receipt'],
            'backups' => ['label' => 'Backups', 'icon' => 'fas fa-database'],
            'configuracoes' => ['label' => 'Configurações', 'icon' => 'fas fa-cog'],
            'perfil' => ['label' => 'Meu Perfil', 'icon' => 'fas fa-user']
        ];
        
        foreach ($menuItems as $key => $item):
            $isActive = $page === $key;
            $activeClass = $isActive ? 'bg-blue-50 border-r-2 border-blue-500 text-blue-700' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50';
            $iconClass = $isActive ? 'text-blue-600' : 'text-gray-400';
        ?>
            <a href="?page=<?= $key ?>" class="w-full flex items-center px-6 py-3 text-left transition-colors <?= $activeClass ?>">
                <i class="<?= $item['icon'] ?> w-5 h-5 mr-3 <?= $iconClass ?>"></i>
                <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>