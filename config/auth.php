<?php
/**
 * Sistema de Autenticação
 */

function login($email, $senha) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios_admin WHERE email = ? AND status = 'ativo'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($senha, $user['senha_hash'])) {
            // Atualizar último login
            $stmt = $pdo->prepare("UPDATE usuarios_admin SET ultimo_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Log da ação
            logAction($user['id'], 'LOGIN', 'Login realizado com sucesso');
            
            return $user;
        }
        
        return false;
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function logout() {
    if (isset($_SESSION['user'])) {
        logAction($_SESSION['user']['id'], 'LOGOUT', 'Logout realizado');
    }
    
    session_destroy();
    header('Location: login.php');
    exit();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function logAction($usuario_id, $acao, $detalhes) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO logs_admin (usuario_id, acao, detalhes, ip) VALUES (?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $acao, $detalhes, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    } catch(PDOException $e) {
        error_log("Log action error: " . $e->getMessage());
    }
}