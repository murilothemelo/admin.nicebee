<?php
/**
 * Página de Login
 */

session_start();

require_once 'config/database.php';
require_once 'config/auth.php';

// Se já está logado, redirecionar
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos';
    } else {
        $user = login($email, $senha);
        if ($user) {
            $_SESSION['user'] = $user;
            $_SESSION['logged_in'] = true;
            header('Location: index.php');
            exit();
        } else {
            $error = 'Email ou senha incorretos';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NiceBee Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-shield-alt text-2xl text-blue-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Painel Administrativo</h1>
                <p class="text-gray-600 text-lg">admin.nicebee.com.br</p>
                <p class="text-sm text-gray-500 mt-2">Acesso restrito para administradores</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                        <span class="text-red-700 text-sm font-medium"><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="admin@nicebee.com.br"
                            required
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input
                            type="password"
                            name="senha"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="••••••••"
                            required
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]"
                >
                    Entrar no Painel
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="text-center text-xs text-gray-500">
                    <p>Sistema Multi-Tenant NiceBee</p>
                    <p class="mt-1">© <?= date('Y') ?> - Todos os direitos reservados</p>
                    <div class="mt-3 p-2 bg-blue-50 rounded border border-blue-200">
                        <p class="font-medium text-blue-800 mb-1">Credenciais de Teste:</p>
                        <p class="text-blue-700">Email: admin@nicebee.com.br</p>
                        <p class="text-blue-700">Senha: 123456</p>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <p class="text-xs text-gray-400">
                        Versão 1.0.0 | Suporte: suporte@nicebee.com.br
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>