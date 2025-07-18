<?php
/**
 * Script de correção completa do banco - Execute no navegador
 * URL: http://localhost/admin.nicebee.com.br/complete-database.php
 */

// Configurações do banco (ajuste conforme necessário)
$host = "localhost";
$db_name = "admin_nicebee";
$username = "root"; // Alterar para produção
$password = "";     // Alterar para produção

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Banco de Dados - NiceBee Admin</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .success { background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #28a745; }
        .error { background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #dc3545; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #ffc107; }
        .info { background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #007bff; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .step h3 { margin-top: 0; color: #333; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Completar Schema do Banco de Dados</h1>
    <p>Este script irá criar todas as tabelas que estão faltando no seu banco de dados.</p>

<?php
try {
    $pdo = new PDO(
        "mysql:host=" . $host . ";dbname=" . $db_name,
        $username,
        $password,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4")
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✅ Conectado ao banco de dados com sucesso!</div>";
    
    // Verificar tabelas existentes
    echo "<div class='step'>";
    echo "<h3>📊 Tabelas Existentes</h3>";
    $existing_tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tabelas encontradas: " . count($existing_tables) . "</p>";
    echo "<ul>";
    foreach ($existing_tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        echo "<li><strong>{$table}</strong>: {$count} registros</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    $tables_created = 0;
    $tables_updated = 0;
    
    // 1. Corrigir charset
    echo "<div class='step'>";
    echo "<h3>🔧 Corrigindo Charset das Tabelas</h3>";
    $charset_fixes = [
        "usuarios_admin" => "ALTER TABLE usuarios_admin CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "planos" => "ALTER TABLE planos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", 
        "clientes" => "ALTER TABLE clientes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    foreach ($charset_fixes as $table => $sql) {
        try {
            $pdo->exec($sql);
            echo "<p>✅ Charset da tabela <strong>{$table}</strong> corrigido</p>";
            $tables_updated++;
        } catch (Exception $e) {
            echo "<p>⚠️ Charset da tabela <strong>{$table}</strong> já estava correto</p>";
        }
    }
    echo "</div>";
    
    // 2. Criar tabelas que faltam
    $tables_to_create = [
        'faturas' => "CREATE TABLE IF NOT EXISTS faturas (
            id INT PRIMARY KEY AUTO_INCREMENT,
            cliente_id INT NOT NULL,
            referencia VARCHAR(100) UNIQUE NOT NULL,
            vencimento DATE NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            status ENUM('pendente', 'pago', 'vencido', 'cancelado') DEFAULT 'pendente',
            forma_pagamento VARCHAR(50) NULL,
            data_pagamento TIMESTAMP NULL,
            observacoes TEXT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
            INDEX idx_cliente (cliente_id),
            INDEX idx_referencia (referencia),
            INDEX idx_status (status),
            INDEX idx_vencimento (vencimento),
            INDEX idx_data_pagamento (data_pagamento)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'backups' => "CREATE TABLE IF NOT EXISTS backups (
            id INT PRIMARY KEY AUTO_INCREMENT,
            cliente_id INT NOT NULL,
            arquivo VARCHAR(255) NOT NULL,
            tamanho_mb DECIMAL(10,2) DEFAULT 0,
            status ENUM('processando', 'concluido', 'erro') DEFAULT 'processando',
            tipo ENUM('manual', 'automatico') DEFAULT 'manual',
            observacoes TEXT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
            INDEX idx_cliente (cliente_id),
            INDEX idx_status (status),
            INDEX idx_tipo (tipo),
            INDEX idx_criado_em (criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'logs_admin' => "CREATE TABLE IF NOT EXISTS logs_admin (
            id INT PRIMARY KEY AUTO_INCREMENT,
            usuario_id INT NOT NULL,
            acao VARCHAR(100) NOT NULL,
            detalhes TEXT,
            ip VARCHAR(45),
            user_agent TEXT,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (usuario_id) REFERENCES usuarios_admin(id) ON DELETE CASCADE,
            INDEX idx_usuario (usuario_id),
            INDEX idx_acao (acao),
            INDEX idx_criado_em (criado_em),
            INDEX idx_ip (ip)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'password_resets' => "CREATE TABLE IF NOT EXISTS password_resets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES usuarios_admin(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_expires (expires_at),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    echo "<div class='step'>";
    echo "<h3>🏗️ Criando Tabelas que Faltam</h3>";
    
    foreach ($tables_to_create as $table_name => $sql) {
        try {
            $pdo->exec($sql);
            echo "<p>✅ Tabela <strong>{$table_name}</strong> criada com sucesso</p>";
            $tables_created++;
        } catch (Exception $e) {
            echo "<p>⚠️ Tabela <strong>{$table_name}</strong> já existe ou erro: " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
    
    // 3. Adicionar colunas que faltam
    echo "<div class='step'>";
    echo "<h3>🔍 Verificando Colunas das Tabelas</h3>";
    
    $tables_to_check = ['usuarios_admin', 'planos', 'clientes'];
    foreach ($tables_to_check as $table) {
        $check_column = "SHOW COLUMNS FROM {$table} LIKE 'atualizado_em'";
        $result = $pdo->query($check_column);
        
        if ($result->rowCount() == 0) {
            $add_column = "ALTER TABLE {$table} ADD COLUMN atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
            $pdo->exec($add_column);
            echo "<p>✅ Coluna <strong>atualizado_em</strong> adicionada à tabela <strong>{$table}</strong></p>";
            $tables_updated++;
        } else {
            echo "<p>✅ Coluna <strong>atualizado_em</strong> já existe na tabela <strong>{$table}</strong></p>";
        }
    }
    echo "</div>";
    
    // 4. Inserir dados de exemplo
    echo "<div class='step'>";
    echo "<h3>📊 Inserindo Dados de Exemplo</h3>";
    
    // Planos
    $count_planos = $pdo->query("SELECT COUNT(*) FROM planos")->fetchColumn();
    if ($count_planos == 0) {
        $planos_sql = "INSERT INTO planos (nome, limite_mb, usuarios_max, valor_mensal, status) VALUES 
            ('Básico', 500, 5, 99.90, 'ativo'),
            ('Profissional', 2000, 15, 199.90, 'ativo'),
            ('Empresarial', 5000, 50, 399.90, 'ativo'),
            ('Premium', 10000, 100, 699.90, 'ativo')";
        $pdo->exec($planos_sql);
        echo "<p>✅ <strong>4 planos padrão</strong> inseridos</p>";
    } else {
        echo "<p>✅ Planos já existem ({$count_planos} encontrados)</p>";
    }
    
    // Usuário admin
    $count_admin = $pdo->query("SELECT COUNT(*) FROM usuarios_admin WHERE email = 'admin@nicebee.com.br'")->fetchColumn();
    if ($count_admin == 0) {
        $senha_hash = password_hash('123456', PASSWORD_DEFAULT);
        $admin_sql = "INSERT INTO usuarios_admin (nome, email, senha_hash, tipo, status) VALUES 
            ('Administrador', 'admin@nicebee.com.br', :senha_hash, 'admin', 'ativo')";
        $stmt = $pdo->prepare($admin_sql);
        $stmt->bindParam(':senha_hash', $senha_hash);
        $stmt->execute();
        echo "<p>✅ <strong>Usuário admin</strong> criado (senha: 123456)</p>";
    } else {
        echo "<p>✅ Usuário admin já existe</p>";
    }
    
    // Clientes de exemplo
    $count_clientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    if ($count_clientes == 0) {
        $clientes_sql = "INSERT INTO clientes (codigo_cliente, nome_fantasia, razao_social, email, telefone, documento, plano_id, status, banco_nome, banco_usuario, banco_senha_encrypted, uso_mb) VALUES 
            ('abc123', 'TechSolutions Ltda', 'TechSolutions Tecnologia Ltda', 'contato@techsolutions.com', '(11) 99999-1234', '12.345.678/0001-90', 2, 'ativo', 'nicebeec_cliente_abc123', 'nicebeec_usr_abc123', 'encrypted_password_here', 1200),
            ('def456', 'ABC Corp', 'ABC Corporação S.A.', 'admin@abccorp.com.br', '(11) 88888-5678', '98.765.432/0001-10', 3, 'ativo', 'nicebeec_cliente_def456', 'nicebeec_usr_def456', 'encrypted_password_here', 3800),
            ('ghi789', 'StartupXYZ', 'StartupXYZ Inovação Ltda', 'hello@startupxyz.com', '(11) 77777-9012', '11.222.333/0001-44', 1, 'bloqueado', 'nicebeec_cliente_ghi789', 'nicebeec_usr_ghi789', 'encrypted_password_here', 480)";
        $pdo->exec($clientes_sql);
        echo "<p>✅ <strong>3 clientes de exemplo</strong> inseridos</p>";
        
        // Faturas de exemplo
        $faturas_sql = "INSERT INTO faturas (cliente_id, referencia, vencimento, valor, status, forma_pagamento, data_pagamento) VALUES 
            (1, '2024-03-001', '2024-03-15', 199.90, 'pago', 'PIX', '2024-03-14 10:30:00'),
            (2, '2024-03-002', '2024-03-20', 399.90, 'pendente', NULL, NULL),
            (3, '2024-03-003', '2024-03-10', 99.90, 'vencido', NULL, NULL)";
        $pdo->exec($faturas_sql);
        echo "<p>✅ <strong>3 faturas de exemplo</strong> inseridas</p>";
        
        // Backups de exemplo
        $backups_sql = "INSERT INTO backups (cliente_id, arquivo, tamanho_mb, status, tipo) VALUES 
            (1, 'backup_abc123_2024-03-15_02-00-00.sql', 45.2, 'concluido', 'automatico'),
            (2, 'backup_def456_2024-03-15_02-15-00.sql', 128.7, 'concluido', 'automatico'),
            (1, 'backup_abc123_2024-03-14_15-30-00.sql', 44.8, 'concluido', 'manual')";
        $pdo->exec($backups_sql);
        echo "<p>✅ <strong>3 backups de exemplo</strong> inseridos</p>";
    } else {
        echo "<p>✅ Clientes já existem ({$count_clientes} encontrados)</p>";
    }
    echo "</div>";
    
    // 5. Criar views
    echo "<div class='step'>";
    echo "<h3>👁️ Criando Views Úteis</h3>";
    
    $view_clientes_sql = "CREATE OR REPLACE VIEW view_clientes_completo AS
        SELECT 
            c.*,
            p.nome as plano_nome,
            p.limite_mb,
            p.usuarios_max,
            p.valor_mensal,
            ROUND((c.uso_mb / p.limite_mb) * 100, 2) as percentual_uso,
            CASE 
                WHEN c.uso_mb / p.limite_mb > 0.9 THEN 'CRÍTICO'
                WHEN c.uso_mb / p.limite_mb > 0.7 THEN 'ALERTA'
                ELSE 'OK'
            END as status_uso
        FROM clientes c
        LEFT JOIN planos p ON c.plano_id = p.id";
    $pdo->exec($view_clientes_sql);
    echo "<p>✅ View <strong>view_clientes_completo</strong> criada</p>";
    
    $view_faturas_sql = "CREATE OR REPLACE VIEW view_faturas_resumo AS
        SELECT 
            DATE_FORMAT(f.vencimento, '%Y-%m') as mes_referencia,
            COUNT(*) as total_faturas,
            SUM(CASE WHEN f.status = 'pago' THEN f.valor ELSE 0 END) as valor_pago,
            SUM(CASE WHEN f.status = 'pendente' THEN f.valor ELSE 0 END) as valor_pendente,
            SUM(CASE WHEN f.status = 'vencido' THEN f.valor ELSE 0 END) as valor_vencido,
            SUM(f.valor) as valor_total
        FROM faturas f
        GROUP BY DATE_FORMAT(f.vencimento, '%Y-%m')
        ORDER BY mes_referencia DESC";
    $pdo->exec($view_faturas_sql);
    echo "<p>✅ View <strong>view_faturas_resumo</strong> criada</p>";
    echo "</div>";
    
    // Resumo final
    echo "<div class='success'>";
    echo "<h3>🎉 BANCO DE DADOS COMPLETADO COM SUCESSO!</h3>";
    echo "<p><strong>Resumo das alterações:</strong></p>";
    echo "<ul>";
    echo "<li>✅ {$tables_created} tabelas criadas</li>";
    echo "<li>✅ {$tables_updated} tabelas atualizadas</li>";
    echo "<li>✅ 2 views criadas</li>";
    echo "<li>✅ Dados de exemplo inseridos</li>";
    echo "</ul>";
    echo "</div>";
    
    // Mostrar tabelas finais
    echo "<div class='step'>";
    echo "<h3>📊 Estado Final do Banco</h3>";
    $final_tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table>";
    echo "<tr><th>Tabela</th><th>Registros</th><th>Status</th></tr>";
    foreach ($final_tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            $status = $count > 0 ? "✅ Com dados" : "⚠️ Vazia";
            echo "<tr><td><strong>{$table}</strong></td><td>{$count}</td><td>{$status}</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td><strong>{$table}</strong></td><td>-</td><td>📊 View</td></tr>";
        }
    }
    echo "</table>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔑 Credenciais de Acesso</h3>";
    echo "<p><strong>Email:</strong> admin@nicebee.com.br</p>";
    echo "<p><strong>Senha:</strong> 123456</p>";
    echo "<p><strong>⚠️ IMPORTANTE:</strong> Altere a senha após o primeiro login!</p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>📋 Próximos Passos</h3>";
    echo "<ol>";
    echo "<li>Faça login no sistema com as credenciais acima</li>";
    echo "<li>Vá em 'Meu Perfil' e altere a senha</li>";
    echo "<li>Explore as funcionalidades: Clientes, Planos, Faturas, Backups</li>";
    echo "<li>Delete este arquivo (complete-database.php) por segurança</li>";
    echo "</ol>";
    echo "</div>";
    
} catch(PDOException $exception) {
    echo "<div class='error'>";
    echo "<h3>❌ Erro de Banco de Dados</h3>";
    echo "<p><strong>Erro:</strong> " . $exception->getMessage() . "</p>";
    echo "<p><strong>Verifique:</strong></p>";
    echo "<ul>";
    echo "<li>Se o banco 'admin_nicebee' existe</li>";
    echo "<li>Se as credenciais estão corretas</li>";
    echo "<li>Se o MySQL está rodando</li>";
    echo "</ul>";
    echo "</div>";
}
?>

</body>
</html>