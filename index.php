<?php
/**
 * Painel Administrativo Multi-Tenant - NiceBee
 * Sistema completo em PHP para gerenciar clientes, planos, faturas e backups
 */

session_start();

// Configurações
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'includes/functions.php';

// Verificar se está logado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$page = $_GET['page'] ?? 'dashboard';
$user = $_SESSION['user'];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NiceBee Admin - Painel Multi-Tenant</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>
            
            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                <?php
                switch ($page) {
                    case 'dashboard':
                        include 'pages/dashboard.php';
                        break;
                    case 'clientes':
                        include 'pages/clientes.php';
                        break;
                    case 'planos':
                        include 'pages/planos.php';
                        break;
                    case 'faturas':
                        include 'pages/faturas.php';
                        break;
                    case 'backups':
                        include 'pages/backups.php';
                        break;
                    case 'configuracoes':
                        include 'pages/configuracoes.php';
                        break;
                    case 'perfil':
                        include 'pages/perfil.php';
                        break;
                    default:
                        include 'pages/dashboard.php';
                }
                ?>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="assets/js/app.js"></script>
</body>
</html>