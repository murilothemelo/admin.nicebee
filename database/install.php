<?php
/**
 * Script de Instalação do Banco de Dados
 * Execute este arquivo via navegador para instalar o sistema
 */

// Configurações de conexão (ajuste conforme necessário)
$config = [
    'host' => 'localhost',
    'root_user' => 'root',
    'root_password' => '', // Senha do root do MySQL
    'database' => 'nicebeec_admin',
    'admin_user' => 'nicebeec_admin',
    'admin_password' => '123@Elektro'
];

$errors = [];
$success = [];

if ($_POST) {
    try {
        // Conectar como root para criar banco e usuário
        $pdo = new PDO(
            "mysql:host={$config['host']}", 
            $_POST['root_user'], 
            $_POST['root_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $success[] = "✅ Conexão com MySQL estabelecida";
        
        // Ler e executar o script SQL
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        
        if (!$sql) {
            throw new Exception("Não foi possível ler o arquivo schema.sql");
        }
        
        // Dividir o SQL em comandos individuais
        $commands = array_filter(
            array_map('trim', explode(';', $sql)),
            function($cmd) { return !empty($cmd) && !preg_match('/^--/', $cmd); }
        );
        
        foreach ($commands as $command) {
            if (trim($command)) {
                $pdo->exec($command);
            }
        }
        
        $success[] = "✅ Banco de dados criado com sucesso";
        
        // Criar usuário administrativo do banco
        $admin_user = $_POST['admin_user'];
        $admin_password = $_POST['admin_password'];
        $database = $_POST['database'];
        
        $pdo->exec("CREATE USER IF NOT EXISTS '{$admin_user}'@'localhost' IDENTIFIED BY '{$admin_password}'");
        $pdo->exec("GRANT ALL PRIVILEGES ON `{$database}`.* TO '{$admin_user}'@'localhost'");
        $pdo->exec("FLUSH PRIVILEGES");
        
        $success[] = "✅ Usuário administrativo criado: {$admin_user}";
        
        // Testar conexão com o novo usuário
        $test_pdo = new PDO(
            "mysql:host={$config['host']};dbname={$database}",
            $admin_user,
            $admin_password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $success[] = "✅ Conexão com usuário administrativo testada";
        
        // Verificar se as tabelas foram criadas
        $stmt = $test_pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $expected_tables = [
            'usuarios_admin', 'planos', 'clientes', 'faturas', 
            'backups', 'logs_admin', 'configuracoes_sistema', 'notificacoes'
        ];
        
        $missing_tables = array_diff($expected_tables, $tables);
        
        if (empty($missing_tables)) {
            $success[] = "✅ Todas as tabelas foram criadas (" . count($tables) . " tabelas)";
        } else {
            $errors[] = "❌ Tabelas não criadas: " . implode(', ', $missing_tables);
        }
        
        // Verificar dados iniciais
        $stmt = $test_pdo->query("SELECT COUNT(*) FROM usuarios_admin");
        $admin_count = $stmt->fetchColumn();
        
        $stmt = $test_pdo->query("SELECT COUNT(*) FROM planos");
        $planos_count = $stmt->fetchColumn();
        
        if ($admin_count > 0 && $planos_count > 0) {
            $success[] = "✅ Dados iniciais inseridos (Admin: {$admin_count}, Planos: {$planos_count})";
        } else {
            $errors[] = "❌ Falha ao inserir dados iniciais";
        }
        
        if (empty($errors)) {
            $success[] = "🎉 INSTALAÇÃO CONCLUÍDA COM SUCESSO!";
            $success[] = "📧 Login: admin@nicebee.com.br";
            $success[] = "🔑 Senha: 123456";
            $success[] = "🔗 Acesse: <a href='../index.php' class='text-blue-600 underline'>Sistema Administrativo</a>";
        }
        
    } catch (Exception $e) {
        $errors[] = "❌ Erro: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - NiceBee Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-database text-3xl text-blue-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Instalação do Sistema</h1>
                <p class="text-gray-600">NiceBee Admin - Sistema Multi-Tenant</p>
            </div>

            <!-- Resultados -->
            <?php if (!empty($success)): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-check-circle text-green-600 text-xl mr-2"></i>
                        <h3 class="text-lg font-semibold text-green-800">Sucesso!</h3>
                    </div>
                    <ul class="space-y-2">
                        <?php foreach ($success as $msg): ?>
                            <li class="text-green-700"><?= $msg ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl mr-2"></i>
                        <h3 class="text-lg font-semibold text-red-800">Erros Encontrados</h3>
                    </div>
                    <ul class="space-y-2">
                        <?php foreach ($errors as $error): ?>
                            <li class="text-red-700"><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Formulário de Instalação -->
            <?php if (empty($success) || !empty($errors)): ?>
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Configuração do Banco de Dados</h2>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Usuário Root MySQL</label>
                                <input type="text" name="root_user" value="<?= $_POST['root_user'] ?? 'root' ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Senha Root MySQL</label>
                                <input type="password" name="root_password" value="<?= $_POST['root_password'] ?? '' ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Deixe vazio se não houver senha">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Banco</label>
                                <input type="text" name="database" value="<?= $_POST['database'] ?? $config['database'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Usuário Admin do Sistema</label>
                                <input type="text" name="admin_user" value="<?= $_POST['admin_user'] ?? $config['admin_user'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Senha Admin do Sistema</label>
                                <input type="password" name="admin_password" value="<?= $_POST['admin_password'] ?? $config['admin_password'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-600 mr-2 mt-0.5"></i>
                                <div class="text-sm text-blue-800">
                                    <p class="font-medium mb-2">O que será criado:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Banco de dados principal com todas as tabelas</li>
                                        <li>Usuário administrativo com permissões específicas</li>
                                        <li>Dados iniciais (usuário admin e planos padrão)</li>
                                        <li>Views, triggers e procedures para auditoria</li>
                                        <li>Índices otimizados para performance</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                            <i class="fas fa-play mr-2"></i>Instalar Sistema
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Informações Adicionais -->
            <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Requisitos do Sistema</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Servidor:</h4>
                        <ul class="space-y-1 text-gray-600">
                            <li>• PHP 7.4 ou superior</li>
                            <li>• MySQL 5.7 ou superior</li>
                            <li>• Extensão PDO MySQL</li>
                            <li>• Mod_rewrite (Apache)</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Permissões:</h4>
                        <ul class="space-y-1 text-gray-600">
                            <li>• CREATE DATABASE</li>
                            <li>• CREATE USER</li>
                            <li>• GRANT PRIVILEGES</li>
                            <li>• Escrita em diretórios</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-sm text-gray-500">
                <p>NiceBee Admin v1.0.0 - Sistema Multi-Tenant</p>
                <p class="mt-1">© <?= date('Y') ?> - Todos os direitos reservados</p>
            </div>
        </div>
    </div>
</body>
</html>