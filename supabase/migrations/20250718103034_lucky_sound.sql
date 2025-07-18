-- Banco de Dados: admin_nicebee
-- Sistema Administrativo Multi-Tenant NiceBee
-- admin.nicebee.com.br

CREATE DATABASE IF NOT EXISTS admin_nicebee CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE admin_nicebee;

-- Tabela de usuários administradores
CREATE TABLE usuarios_admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'operador') DEFAULT 'operador',
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    ultimo_login TIMESTAMP NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_tipo (tipo)
);

-- Tabela de planos
CREATE TABLE planos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    limite_mb INT NOT NULL DEFAULT 1000,
    usuarios_max INT NOT NULL DEFAULT 10,
    valor_mensal DECIMAL(10,2) NOT NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_valor (valor_mensal)
);

-- Tabela de clientes
CREATE TABLE clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo_cliente VARCHAR(50) UNIQUE NOT NULL,
    nome_fantasia VARCHAR(255) NOT NULL,
    razao_social VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    documento VARCHAR(20),
    plano_id INT,
    status ENUM('ativo', 'inativo', 'bloqueado') DEFAULT 'ativo',
    banco_nome VARCHAR(100) NOT NULL,
    banco_usuario VARCHAR(100) NOT NULL,
    banco_senha_encrypted TEXT NOT NULL,
    uso_mb INT DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE SET NULL,
    INDEX idx_codigo_cliente (codigo_cliente),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_plano (plano_id),
    INDEX idx_banco_nome (banco_nome)
);

-- Tabela de faturas
CREATE TABLE faturas (
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
);

-- Tabela de backups
CREATE TABLE backups (
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
);

-- Tabela de logs administrativos
CREATE TABLE logs_admin (
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
);

-- Inserir usuário administrador padrão
INSERT INTO usuarios_admin (nome, email, senha_hash, tipo, status) VALUES 
('Administrador', 'admin@nicebee.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'ativo');
-- Senha: password (alterar em produção!)

-- Inserir planos padrão
INSERT INTO planos (nome, limite_mb, usuarios_max, valor_mensal, status) VALUES 
('Básico', 500, 5, 99.90, 'ativo'),
('Profissional', 2000, 15, 199.90, 'ativo'),
('Empresarial', 5000, 50, 399.90, 'ativo'),
('Premium', 10000, 100, 699.90, 'ativo');

-- Inserir clientes de exemplo (opcional para testes)
INSERT INTO clientes (codigo_cliente, nome_fantasia, razao_social, email, telefone, documento, plano_id, status, banco_nome, banco_usuario, banco_senha_encrypted, uso_mb) VALUES 
('abc123', 'TechSolutions Ltda', 'TechSolutions Tecnologia Ltda', 'contato@techsolutions.com', '(11) 99999-1234', '12.345.678/0001-90', 2, 'ativo', 'nicebeec_cliente_abc123', 'nicebeec_usr_abc123', 'encrypted_password_here', 1200),
('def456', 'ABC Corp', 'ABC Corporação S.A.', 'admin@abccorp.com.br', '(11) 88888-5678', '98.765.432/0001-10', 3, 'ativo', 'nicebeec_cliente_def456', 'nicebeec_usr_def456', 'encrypted_password_here', 3800),
('ghi789', 'StartupXYZ', 'StartupXYZ Inovação Ltda', 'hello@startupxyz.com', '(11) 77777-9012', '11.222.333/0001-44', 1, 'bloqueado', 'nicebeec_cliente_ghi789', 'nicebeec_usr_ghi789', 'encrypted_password_here', 480);

-- Inserir faturas de exemplo
INSERT INTO faturas (cliente_id, referencia, vencimento, valor, status, forma_pagamento, data_pagamento) VALUES 
(1, '2024-03-001', '2024-03-15', 199.90, 'pago', 'PIX', '2024-03-14 10:30:00'),
(2, '2024-03-002', '2024-03-20', 399.90, 'pendente', NULL, NULL),
(3, '2024-03-003', '2024-03-10', 99.90, 'vencido', NULL, NULL);

-- Inserir backups de exemplo
INSERT INTO backups (cliente_id, arquivo, tamanho_mb, status, tipo) VALUES 
(1, 'backup_abc123_2024-03-15_02-00-00.sql', 45.2, 'concluido', 'automatico'),
(2, 'backup_def456_2024-03-15_02-15-00.sql', 128.7, 'concluido', 'automatico'),
(1, 'backup_abc123_2024-03-14_15-30-00.sql', 44.8, 'concluido', 'manual');

-- Inserir logs de exemplo
INSERT INTO logs_admin (usuario_id, acao, detalhes, ip) VALUES 
(1, 'LOGIN', 'Login realizado com sucesso', '192.168.1.100'),
(1, 'CRIAR_CLIENTE', 'Cliente TechSolutions Ltda criado', '192.168.1.100'),
(1, 'GERAR_BACKUP', 'Backup manual do cliente ABC123 iniciado', '192.168.1.100');

-- Criar diretório para backups (executar no sistema de arquivos)
-- mkdir -p ../backups
-- chmod 755 ../backups

-- Views úteis para relatórios
CREATE VIEW view_clientes_completo AS
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
LEFT JOIN planos p ON c.plano_id = p.id;

CREATE VIEW view_faturas_resumo AS
SELECT 
    DATE_FORMAT(f.vencimento, '%Y-%m') as mes_referencia,
    COUNT(*) as total_faturas,
    SUM(CASE WHEN f.status = 'pago' THEN f.valor ELSE 0 END) as valor_pago,
    SUM(CASE WHEN f.status = 'pendente' THEN f.valor ELSE 0 END) as valor_pendente,
    SUM(CASE WHEN f.status = 'vencido' THEN f.valor ELSE 0 END) as valor_vencido,
    SUM(f.valor) as valor_total
FROM faturas f
GROUP BY DATE_FORMAT(f.vencimento, '%Y-%m')
ORDER BY mes_referencia DESC;

-- Triggers para auditoria automática
DELIMITER //

CREATE TRIGGER tr_clientes_log_insert 
AFTER INSERT ON clientes
FOR EACH ROW
BEGIN
    INSERT INTO logs_admin (usuario_id, acao, detalhes, ip) 
    VALUES (1, 'CLIENTE_CRIADO', CONCAT('Cliente ', NEW.nome_fantasia, ' criado automaticamente'), '127.0.0.1');
END//

CREATE TRIGGER tr_clientes_log_update 
AFTER UPDATE ON clientes
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO logs_admin (usuario_id, acao, detalhes, ip) 
        VALUES (1, 'CLIENTE_STATUS_ALTERADO', CONCAT('Status do cliente ', NEW.nome_fantasia, ' alterado de ', OLD.status, ' para ', NEW.status), '127.0.0.1');
    END IF;
END//

DELIMITER ;

-- Índices adicionais para performance
CREATE INDEX idx_faturas_mes_status ON faturas (DATE_FORMAT(vencimento, '%Y-%m'), status);
CREATE INDEX idx_logs_data_acao ON logs_admin (DATE(criado_em), acao);
CREATE INDEX idx_backups_cliente_data ON backups (cliente_id, DATE(criado_em));

-- Configurações de otimização
SET GLOBAL innodb_buffer_pool_size = 268435456; -- 256MB
SET GLOBAL query_cache_size = 67108864; -- 64MB
SET GLOBAL query_cache_type = 1;

-- Comentários nas tabelas
ALTER TABLE usuarios_admin COMMENT = 'Usuários com acesso ao painel administrativo';
ALTER TABLE clientes COMMENT = 'Clientes do sistema multi-tenant com bancos isolados';
ALTER TABLE planos COMMENT = 'Planos de assinatura com limites e valores';
ALTER TABLE faturas COMMENT = 'Controle financeiro e cobrança mensal';
ALTER TABLE backups COMMENT = 'Registro de backups dos bancos de clientes';
ALTER TABLE logs_admin COMMENT = 'Auditoria de ações no painel administrativo';