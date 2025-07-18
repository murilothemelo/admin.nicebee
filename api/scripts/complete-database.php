<?php
/**
 * Script para completar o schema do banco de dados
 * Execute este script para criar todas as tabelas que estão faltando
 */

// Configurações do banco (ajuste conforme necessário)
$host = "localhost";
$db_name = "admin_nicebee";
$username = "root"; // Alterar para produção
$password = "";     // Alterar para produção

echo "=== COMPLETANDO SCHEMA DO BANCO DE DADOS ===\n\n";

try {
    $pdo = new PDO(
        "mysql:host=" . $host . ";dbname=" . $db_name,
        $username,
        $password,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4")
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conectado ao banco de dados\n\n";
    
    // 1. Verificar e corrigir charset das tabelas existentes
    echo "🔧 Corrigindo charset das tabelas existentes...\n";
    
    $charset_fixes = [
        "ALTER TABLE usuarios_admin CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "ALTER TABLE planos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", 
        "ALTER TABLE clientes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    foreach ($charset_fixes as $sql) {
        try {
            $pdo->exec($sql);
            echo "  ✅ Charset corrigido\n";
        } catch (Exception $e) {
            echo "  ⚠️  Charset já correto ou erro: " . $e->getMessage() . "\n";
        }
    }
    
    // 2. Criar tabela de faturas
    echo "\n📄 Criando tabela de faturas...\n";
    $faturas_sql = "CREATE TABLE IF NOT EXISTS faturas (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($faturas_sql);
    echo "✅ Tabela faturas criada\n";
    
    // 3. Criar tabela de backups
    echo "\n💾 Criando tabela de backups...\n";
    $backups_sql = "CREATE TABLE IF NOT EXISTS backups (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($backups_sql);
    echo "✅ Tabela backups criada\n";
    
    // 4. Criar tabela de logs administrativos
    echo "\n📋 Criando tabela de logs administrativos...\n";
    $logs_sql = "CREATE TABLE IF NOT EXISTS logs_admin (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($logs_sql);
    echo "✅ Tabela logs_admin criada\n";
    
    // 5. Criar tabela de reset de senhas
    echo "\n🔑 Criando tabela de reset de senhas...\n";
    $password_resets_sql = "CREATE TABLE IF NOT EXISTS password_resets (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($password_resets_sql);
    echo "✅ Tabela password_resets criada\n";
    
    // 6. Verificar se faltam colunas nas tabelas existentes
    echo "\n🔍 Verificando colunas das tabelas existentes...\n";
    
    // Verificar coluna atualizado_em nas tabelas
    $tables_to_check = ['usuarios_admin', 'planos', 'clientes'];
    foreach ($tables_to_check as $table) {
        $check_column = "SHOW COLUMNS FROM {$table} LIKE 'atualizado_em'";
        $result = $pdo->query($check_column);
        
        if ($result->rowCount() == 0) {
            $add_column = "ALTER TABLE {$table} ADD COLUMN atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
            $pdo->exec($add_column);
            echo "  ✅ Coluna atualizado_em adicionada à tabela {$table}\n";
        } else {
            echo "  ✅ Coluna atualizado_em já existe na tabela {$table}\n";
        }
    }
    
    // 7. Inserir dados de exemplo se as tabelas estiverem vazias
    echo "\n📊 Verificando dados de exemplo...\n";
    
    // Verificar se há planos
    $count_planos = $pdo->query("SELECT COUNT(*) FROM planos")->fetchColumn();
    if ($count_planos == 0) {
        echo "Inserindo planos padrão...\n";
        $planos_sql = "INSERT INTO planos (nome, limite_mb, usuarios_max, valor_mensal, status) VALUES 
            ('Básico', 500, 5, 99.90, 'ativo'),
            ('Profissional', 2000, 15, 199.90, 'ativo'),
            ('Empresarial', 5000, 50, 399.90, 'ativo'),
            ('Premium', 10000, 100, 699.90, 'ativo')";
        $pdo->exec($planos_sql);
        echo "✅ Planos padrão inseridos\n";
    }
    
    // Verificar se há usuário admin
    $count_admin = $pdo->query("SELECT COUNT(*) FROM usuarios_admin WHERE email = 'admin@nicebee.com.br'")->fetchColumn();
    if ($count_admin == 0) {
        echo "Criando usuário admin...\n";
        $senha_hash = password_hash('123456', PASSWORD_DEFAULT);
        $admin_sql = "INSERT INTO usuarios_admin (nome, email, senha_hash, tipo, status) VALUES 
            ('Administrador', 'admin@nicebee.com.br', :senha_hash, 'admin', 'ativo')";
        $stmt = $pdo->prepare($admin_sql);
        $stmt->bindParam(':senha_hash', $senha_hash);
        $stmt->execute();
        echo "✅ Usuário admin criado (senha: 123456)\n";
    }
    
    // Inserir alguns dados de exemplo se não existirem
    $count_clientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    if ($count_clientes == 0) {
        echo "Inserindo clientes de exemplo...\n";
        $clientes_sql = "INSERT INTO clientes (codigo_cliente, nome_fantasia, razao_social, email, telefone, documento, plano_id, status, banco_nome, banco_usuario, banco_senha_encrypted, uso_mb) VALUES 
            ('abc123', 'TechSolutions Ltda', 'TechSolutions Tecnologia Ltda', 'contato@techsolutions.com', '(11) 99999-1234', '12.345.678/0001-90', 2, 'ativo', 'nicebeec_cliente_abc123', 'nicebeec_usr_abc123', 'encrypted_password_here', 1200),
            ('def456', 'ABC Corp', 'ABC Corporação S.A.', 'admin@abccorp.com.br', '(11) 88888-5678', '98.765.432/0001-10', 3, 'ativo', 'nicebeec_cliente_def456', 'nicebeec_usr_def456', 'encrypted_password_here', 3800),
            ('ghi789', 'StartupXYZ', 'StartupXYZ Inovação Ltda', 'hello@startupxyz.com', '(11) 77777-9012', '11.222.333/0001-44', 1, 'bloqueado', 'nicebeec_cliente_ghi789', 'nicebeec_usr_ghi789', 'encrypted_password_here', 480)";
        $pdo->exec($clientes_sql);
        echo "✅ Clientes de exemplo inseridos\n";
        
        // Inserir faturas de exemplo
        echo "Inserindo faturas de exemplo...\n";
        $faturas_sql = "INSERT INTO faturas (cliente_id, referencia, vencimento, valor, status, forma_pagamento, data_pagamento) VALUES 
            (1, '2024-03-001', '2024-03-15', 199.90, 'pago', 'PIX', '2024-03-14 10:30:00'),
            (2, '2024-03-002', '2024-03-20', 399.90, 'pendente', NULL, NULL),
            (3, '2024-03-003', '2024-03-10', 99.90, 'vencido', NULL, NULL)";
        $pdo->exec($faturas_sql);
        echo "✅ Faturas de exemplo inseridas\n";
        
        // Inserir backups de exemplo
        echo "Inserindo backups de exemplo...\n";
        $backups_sql = "INSERT INTO backups (cliente_id, arquivo, tamanho_mb, status, tipo) VALUES 
            (1, 'backup_abc123_2024-03-15_02-00-00.sql', 45.2, 'concluido', 'automatico'),
            (2, 'backup_def456_2024-03-15_02-15-00.sql', 128.7, 'concluido', 'automatico'),
            (1, 'backup_abc123_2024-03-14_15-30-00.sql', 44.8, 'concluido', 'manual')";
        $pdo->exec($backups_sql);
        echo "✅ Backups de exemplo inseridos\n";
    }
    
    // 8. Criar views úteis
    echo "\n👁️ Criando views úteis...\n";
    
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
    echo "✅ View view_clientes_completo criada\n";
    
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
    echo "✅ View view_faturas_resumo criada\n";
    
    // 9. Mostrar resumo final
    echo "\n=== RESUMO FINAL ===\n";
    $tables_query = "SHOW TABLES";
    $tables_result = $pdo->query($tables_query);
    $tables = $tables_result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 Tabelas no banco de dados:\n";
    foreach ($tables as $table) {
        $count_query = "SELECT COUNT(*) FROM {$table}";
        try {
            $count = $pdo->query($count_query)->fetchColumn();
            echo "  ✅ {$table}: {$count} registros\n";
        } catch (Exception $e) {
            echo "  ⚠️  {$table}: (view ou erro)\n";
        }
    }
    
    echo "\n🎉 BANCO DE DADOS COMPLETADO COM SUCESSO!\n";
    echo "\n📋 Credenciais de acesso:\n";
    echo "Email: admin@nicebee.com.br\n";
    echo "Senha: 123456\n";
    echo "\n⚠️  IMPORTANTE: Altere a senha após o primeiro login!\n";
    
} catch(PDOException $exception) {
    echo "❌ Erro de banco: " . $exception->getMessage() . "\n";
} catch(Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n=== FIM ===\n";
?>