<?php
/**
 * Script para corrigir a senha do usuário admin
 * Execute este script para redefinir a senha do admin para '123456'
 */

include_once '../config/database.php';

echo "=== CORREÇÃO DE SENHA DO ADMIN ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Nova senha padrão
    $nova_senha = '123456';
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    
    // Atualizar senha do admin
    $query = "UPDATE usuarios_admin SET senha_hash = :senha_hash WHERE email = 'admin@nicebee.com.br'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':senha_hash', $senha_hash);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Senha do admin atualizada com sucesso!\n";
        echo "📧 Email: admin@nicebee.com.br\n";
        echo "🔑 Senha: {$nova_senha}\n\n";
        
        // Verificar se a senha foi salva corretamente
        $verify_query = "SELECT email, senha_hash FROM usuarios_admin WHERE email = 'admin@nicebee.com.br'";
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->execute();
        $user = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($nova_senha, $user['senha_hash'])) {
            echo "✅ Verificação: Senha salva corretamente no banco!\n";
        } else {
            echo "❌ Erro: Problema na verificação da senha!\n";
        }
        
    } else {
        echo "❌ Nenhum usuário encontrado com email 'admin@nicebee.com.br'\n";
        
        // Criar usuário admin se não existir
        echo "Criando usuário admin...\n";
        $create_query = "INSERT INTO usuarios_admin (nome, email, senha_hash, tipo, status) VALUES ('Administrador', 'admin@nicebee.com.br', :senha_hash, 'admin', 'ativo')";
        $create_stmt = $db->prepare($create_query);
        $create_stmt->bindParam(':senha_hash', $senha_hash);
        $create_stmt->execute();
        
        echo "✅ Usuário admin criado com sucesso!\n";
        echo "📧 Email: admin@nicebee.com.br\n";
        echo "🔑 Senha: {$nova_senha}\n\n";
    }
    
    // Mostrar todos os usuários
    echo "=== USUÁRIOS NO SISTEMA ===\n";
    $list_query = "SELECT id, nome, email, tipo, status, criado_em FROM usuarios_admin ORDER BY id";
    $list_stmt = $db->prepare($list_query);
    $list_stmt->execute();
    $users = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "ID: {$user['id']} | {$user['nome']} | {$user['email']} | {$user['tipo']} | {$user['status']}\n";
    }
    
    echo "\n=== INSTRUÇÕES ===\n";
    echo "1. Use as credenciais acima para fazer login\n";
    echo "2. Após o login, vá em 'Meu Perfil' para alterar a senha\n";
    echo "3. A nova senha deve ter pelo menos 8 caracteres\n";
    echo "4. O sistema agora suporta recuperação de senha\n\n";
    
} catch(PDOException $exception) {
    echo "❌ Erro de banco de dados: " . $exception->getMessage() . "\n";
} catch(Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "=== FIM ===\n";
?>