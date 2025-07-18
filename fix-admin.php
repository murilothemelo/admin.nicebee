<?php
/**
 * Script de correção rápida - Execute no navegador ou linha de comando
 * URL: http://localhost/admin.nicebee.com.br/fix-admin.php
 */

// Configurações do banco (ajuste conforme necessário)
$host = "localhost";
$db_name = "admin_nicebee";
$username = "root"; // Alterar para produção
$password = "";     // Alterar para produção

echo "<h1>🔧 Correção de Senha do Admin</h1>";

try {
    $pdo = new PDO(
        "mysql:host=" . $host . ";dbname=" . $db_name,
        $username,
        $password,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Conectado ao banco de dados</p>";
    
    // Nova senha padrão
    $nova_senha = '123456';
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    
    // Verificar se usuário existe
    $check_query = "SELECT id, nome, email FROM usuarios_admin WHERE email = 'admin@nicebee.com.br'";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        // Atualizar senha existente
        $update_query = "UPDATE usuarios_admin SET senha_hash = :senha_hash WHERE email = 'admin@nicebee.com.br'";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->bindParam(':senha_hash', $senha_hash);
        $update_stmt->execute();
        
        echo "<p>✅ Senha do admin atualizada!</p>";
    } else {
        // Criar usuário admin
        $create_query = "INSERT INTO usuarios_admin (nome, email, senha_hash, tipo, status) VALUES ('Administrador', 'admin@nicebee.com.br', :senha_hash, 'admin', 'ativo')";
        $create_stmt = $pdo->prepare($create_query);
        $create_stmt->bindParam(':senha_hash', $senha_hash);
        $create_stmt->execute();
        
        echo "<p>✅ Usuário admin criado!</p>";
    }
    
    // Verificar se a senha funciona
    $verify_query = "SELECT senha_hash FROM usuarios_admin WHERE email = 'admin@nicebee.com.br'";
    $verify_stmt = $pdo->prepare($verify_query);
    $verify_stmt->execute();
    $user = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($nova_senha, $user['senha_hash'])) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>🎉 Sucesso!</h3>";
        echo "<p><strong>Email:</strong> admin@nicebee.com.br</p>";
        echo "<p><strong>Senha:</strong> {$nova_senha}</p>";
        echo "<p>Agora você pode fazer login no sistema!</p>";
        echo "</div>";
    } else {
        echo "<p>❌ Erro na verificação da senha</p>";
    }
    
    // Listar todos os usuários
    echo "<h3>👥 Usuários no Sistema:</h3>";
    $list_query = "SELECT id, nome, email, tipo, status FROM usuarios_admin ORDER BY id";
    $list_stmt = $pdo->prepare($list_query);
    $list_stmt->execute();
    $users = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Tipo</th><th>Status</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['nome']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['tipo']}</td>";
        echo "<td>{$user['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #cce5ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📋 Próximos Passos:</h3>";
    echo "<ol>";
    echo "<li>Faça login com as credenciais acima</li>";
    echo "<li>Vá em 'Meu Perfil' para alterar a senha</li>";
    echo "<li>Delete este arquivo (fix-admin.php) por segurança</li>";
    echo "</ol>";
    echo "</div>";
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>❌ Erro de banco: " . $exception->getMessage() . "</p>";
    echo "<p>Verifique as configurações de conexão no início deste arquivo.</p>";
}
?>