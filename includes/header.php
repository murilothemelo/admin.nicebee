<header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Bem-vindo, <?= htmlspecialchars($user['nome']) ?>
            </h2>
            <p class="text-sm text-gray-600">
                <?= strftime('%A, %d de %B de %Y', time()) ?>
            </p>
        </div>
        
        <div class="flex items-center space-x-4">
            <button class="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="fas fa-bell"></i>
                <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full"></span>
            </button>
            
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center space-x-3 p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-blue-600"></i>
                    </div>
                    <div class="text-sm text-left">
                        <p class="font-medium text-gray-900"><?= htmlspecialchars($user['nome']) ?></p>
                        <p class="text-gray-600 flex items-center">
                            <i class="fas fa-shield-alt text-xs mr-1"></i>
                            <?= ucfirst($user['tipo']) ?>
                        </p>
                    </div>
                </button>

                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                    <div class="px-4 py-2 border-b border-gray-200">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['nome']) ?></p>
                        <p class="text-xs text-gray-600"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    
                    <a href="?page=perfil" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                        <i class="fas fa-user w-4 h-4 mr-2"></i>
                        Meu Perfil
                    </a>
                    
                    <a href="?page=configuracoes" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                        <i class="fas fa-cog w-4 h-4 mr-2"></i>
                        Configurações
                    </a>
                    
                    <div class="border-t border-gray-200 mt-1">
                        <a href="logout.php" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center">
                            <i class="fas fa-power-off w-4 h-4 mr-2"></i>
                            Sair do Painel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>