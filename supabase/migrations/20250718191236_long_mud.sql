-- =====================================================
-- SISTEMA ADMINISTRATIVO MULTI-TENANT - NICEBEE
-- Script de criação completa do banco de dados
-- =====================================================

-- Criar banco principal
CREATE DATABASE IF NOT EXISTS `nicebeec_admin` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nicebeec_admin`;

-- =====================================================
-- TABELA: usuarios_admin
-- Usuários administradores do sistema
-- =====================================================
CREATE TABLE `usuarios_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL UNIQUE,
  `senha_hash` varchar(255) NOT NULL,
  `tipo` enum('super_admin','admin','operador') NOT NULL DEFAULT 'admin',
  `status` enum('ativo','inativo','bloqueado') NOT NULL DEFAULT 'ativo',
  `ultimo_login` datetime NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: planos
-- Planos de assinatura disponíveis
-- =====================================================
CREATE TABLE `planos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text NULL,
  `limite_mb` int(11) NOT NULL DEFAULT 1000,
  `usuarios_max` int(11) NOT NULL DEFAULT 5,
  `valor_mensal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `recursos` json NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_valor` (`valor_mensal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: clientes
-- Clientes do sistema multi-tenant
-- =====================================================
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_cliente` varchar(20) NOT NULL UNIQUE,
  `nome_fantasia` varchar(150) NOT NULL,
  `razao_social` varchar(200) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefone` varchar(20) NULL,
  `endereco` text NULL,
  `plano_id` int(11) NOT NULL,
  `status` enum('ativo','inativo','bloqueado','suspenso') NOT NULL DEFAULT 'ativo',
  `data_ativacao` date NULL,
  `data_vencimento` date NULL,
  `uso_mb` int(11) NOT NULL DEFAULT 0,
  `usuarios_criados` int(11) NOT NULL DEFAULT 0,
  `banco_nome` varchar(100) NULL,
  `banco_usuario` varchar(100) NULL,
  `banco_senha_encrypted` text NULL,
  `configuracoes` json NULL,
  `observacoes` text NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo_cliente` (`codigo_cliente`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_plano` (`plano_id`),
  KEY `idx_documento` (`documento`),
  CONSTRAINT `fk_clientes_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: faturas
-- Controle de faturamento dos clientes
-- =====================================================
CREATE TABLE `faturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `numero_fatura` varchar(50) NOT NULL UNIQUE,
  `descricao` varchar(200) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` datetime NULL,
  `status` enum('pendente','pago','vencido','cancelado') NOT NULL DEFAULT 'pendente',
  `metodo_pagamento` varchar(50) NULL,
  `referencia_pagamento` varchar(100) NULL,
  `observacoes` text NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero_fatura` (`numero_fatura`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_status` (`status`),
  KEY `idx_vencimento` (`data_vencimento`),
  KEY `idx_pagamento` (`data_pagamento`),
  CONSTRAINT `fk_faturas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: backups
-- Controle de backups dos bancos dos clientes
-- =====================================================
CREATE TABLE `backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `nome_arquivo` varchar(200) NOT NULL,
  `caminho_arquivo` varchar(500) NOT NULL,
  `tamanho_mb` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tipo` enum('manual','automatico','agendado') NOT NULL DEFAULT 'manual',
  `status` enum('processando','concluido','erro','cancelado') NOT NULL DEFAULT 'processando',
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime NULL,
  `erro_detalhes` text NULL,
  `observacoes` text NULL,
  `criado_por` int(11) NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_status` (`status`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_data_inicio` (`data_inicio`),
  KEY `idx_criado_por` (`criado_por`),
  CONSTRAINT `fk_backups_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_backups_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios_admin` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: logs_admin
-- Logs de auditoria do sistema
-- =====================================================
CREATE TABLE `logs_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NULL,
  `acao` varchar(100) NOT NULL,
  `tabela_afetada` varchar(50) NULL,
  `registro_id` int(11) NULL,
  `detalhes` text NULL,
  `dados_anteriores` json NULL,
  `dados_novos` json NULL,
  `ip` varchar(45) NULL,
  `user_agent` text NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_acao` (`acao`),
  KEY `idx_tabela` (`tabela_afetada`),
  KEY `idx_data` (`criado_em`),
  KEY `idx_ip` (`ip`),
  CONSTRAINT `fk_logs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios_admin` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: configuracoes_sistema
-- Configurações globais do sistema
-- =====================================================
CREATE TABLE `configuracoes_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) NOT NULL UNIQUE,
  `valor` text NULL,
  `tipo` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `descricao` varchar(200) NULL,
  `categoria` varchar(50) NOT NULL DEFAULT 'geral',
  `editavel` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_chave` (`chave`),
  KEY `idx_categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: notificacoes
-- Sistema de notificações
-- =====================================================
CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NULL,
  `cliente_id` int(11) NULL,
  `titulo` varchar(200) NOT NULL,
  `mensagem` text NOT NULL,
  `tipo` enum('info','sucesso','aviso','erro') NOT NULL DEFAULT 'info',
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  `data_leitura` datetime NULL,
  `acao_url` varchar(500) NULL,
  `acao_texto` varchar(100) NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_lida` (`lida`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_data` (`criado_em`),
  CONSTRAINT `fk_notificacoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios_admin` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notificacoes_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERIR DADOS INICIAIS
-- =====================================================

-- Usuário administrador padrão
INSERT INTO `usuarios_admin` (`nome`, `email`, `senha_hash`, `tipo`, `status`) VALUES
('Administrador', 'admin@nicebee.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'ativo');
-- Senha: 123456

-- Planos padrão
INSERT INTO `planos` (`nome`, `descricao`, `limite_mb`, `usuarios_max`, `valor_mensal`, `status`) VALUES
('Plano Básico', 'Ideal para pequenas empresas', 1000, 5, 99.90, 'ativo'),
('Plano Profissional', 'Para empresas em crescimento', 5000, 15, 199.90, 'ativo'),
('Plano Enterprise', 'Para grandes corporações', 20000, 50, 499.90, 'ativo'),
('Plano Starter', 'Para testes e desenvolvimento', 500, 2, 49.90, 'ativo');

-- Configurações do sistema
INSERT INTO `configuracoes_sistema` (`chave`, `valor`, `tipo`, `descricao`, `categoria`) VALUES
('sistema_nome', 'NiceBee Admin', 'string', 'Nome do sistema', 'geral'),
('sistema_versao', '1.0.0', 'string', 'Versão atual do sistema', 'geral'),
('backup_automatico', '1', 'boolean', 'Ativar backup automático', 'backup'),
('backup_horario', '02:00', 'string', 'Horário do backup automático', 'backup'),
('backup_retencao_dias', '30', 'number', 'Dias de retenção dos backups', 'backup'),
('email_smtp_host', 'smtp.gmail.com', 'string', 'Servidor SMTP', 'email'),
('email_smtp_porta', '587', 'number', 'Porta SMTP', 'email'),
('email_remetente', 'noreply@nicebee.com.br', 'string', 'Email remetente', 'email'),
('manutencao_modo', '0', 'boolean', 'Modo manutenção ativo', 'sistema'),
('max_tentativas_login', '5', 'number', 'Máximo de tentativas de login', 'seguranca'),
('sessao_timeout', '3600', 'number', 'Timeout da sessão em segundos', 'seguranca');

-- =====================================================
-- VIEWS ÚTEIS
-- =====================================================

-- View: Resumo de clientes
CREATE VIEW `view_clientes_resumo` AS
SELECT 
    c.id,
    c.codigo_cliente,
    c.nome_fantasia,
    c.email,
    c.status,
    p.nome as plano_nome,
    p.valor_mensal,
    c.uso_mb,
    p.limite_mb,
    ROUND((c.uso_mb / p.limite_mb) * 100, 2) as percentual_uso,
    c.criado_em
FROM clientes c
LEFT JOIN planos p ON c.plano_id = p.id;

-- View: Estatísticas do dashboard
CREATE VIEW `view_dashboard_stats` AS
SELECT 
    (SELECT COUNT(*) FROM clientes WHERE status = 'ativo') as clientes_ativos,
    (SELECT COUNT(*) FROM clientes) as total_clientes,
    (SELECT COALESCE(SUM(valor), 0) FROM faturas WHERE status = 'pago' AND MONTH(data_pagamento) = MONTH(NOW()) AND YEAR(data_pagamento) = YEAR(NOW())) as receita_mensal,
    (SELECT COALESCE(SUM(uso_mb), 0) FROM clientes) as uso_total_mb,
    (SELECT COUNT(*) FROM faturas WHERE status IN ('pendente', 'vencido')) as faturas_pendentes,
    (SELECT COUNT(*) FROM backups WHERE status = 'concluido' AND DATE(criado_em) = CURDATE()) as backups_hoje;

-- =====================================================
-- TRIGGERS PARA AUDITORIA
-- =====================================================

-- Trigger: Log de alterações em clientes
DELIMITER $$
CREATE TRIGGER `tr_clientes_update` 
AFTER UPDATE ON `clientes`
FOR EACH ROW
BEGIN
    INSERT INTO logs_admin (usuario_id, acao, tabela_afetada, registro_id, detalhes, dados_anteriores, dados_novos, ip)
    VALUES (
        @current_user_id,
        'UPDATE',
        'clientes',
        NEW.id,
        CONCAT('Cliente ', NEW.nome_fantasia, ' atualizado'),
        JSON_OBJECT(
            'nome_fantasia', OLD.nome_fantasia,
            'email', OLD.email,
            'status', OLD.status,
            'plano_id', OLD.plano_id
        ),
        JSON_OBJECT(
            'nome_fantasia', NEW.nome_fantasia,
            'email', NEW.email,
            'status', NEW.status,
            'plano_id', NEW.plano_id
        ),
        @current_user_ip
    );
END$$

-- Trigger: Log de criação de clientes
CREATE TRIGGER `tr_clientes_insert` 
AFTER INSERT ON `clientes`
FOR EACH ROW
BEGIN
    INSERT INTO logs_admin (usuario_id, acao, tabela_afetada, registro_id, detalhes, dados_novos, ip)
    VALUES (
        @current_user_id,
        'INSERT',
        'clientes',
        NEW.id,
        CONCAT('Cliente ', NEW.nome_fantasia, ' criado'),
        JSON_OBJECT(
            'nome_fantasia', NEW.nome_fantasia,
            'email', NEW.email,
            'status', NEW.status,
            'plano_id', NEW.plano_id
        ),
        @current_user_ip
    );
END$$

-- Trigger: Log de exclusão de clientes
CREATE TRIGGER `tr_clientes_delete` 
BEFORE DELETE ON `clientes`
FOR EACH ROW
BEGIN
    INSERT INTO logs_admin (usuario_id, acao, tabela_afetada, registro_id, detalhes, dados_anteriores, ip)
    VALUES (
        @current_user_id,
        'DELETE',
        'clientes',
        OLD.id,
        CONCAT('Cliente ', OLD.nome_fantasia, ' excluído'),
        JSON_OBJECT(
            'nome_fantasia', OLD.nome_fantasia,
            'email', OLD.email,
            'status', OLD.status,
            'plano_id', OLD.plano_id
        ),
        @current_user_ip
    );
END$$

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

-- Índices compostos para consultas frequentes
CREATE INDEX `idx_clientes_status_plano` ON `clientes` (`status`, `plano_id`);
CREATE INDEX `idx_faturas_cliente_status` ON `faturas` (`cliente_id`, `status`);
CREATE INDEX `idx_backups_cliente_data` ON `backups` (`cliente_id`, `data_inicio`);
CREATE INDEX `idx_logs_usuario_data` ON `logs_admin` (`usuario_id`, `criado_em`);

-- =====================================================
-- PROCEDIMENTOS ARMAZENADOS ÚTEIS
-- =====================================================

DELIMITER $$

-- Procedure: Gerar relatório de uso por cliente
CREATE PROCEDURE `sp_relatorio_uso_clientes`()
BEGIN
    SELECT 
        c.codigo_cliente,
        c.nome_fantasia,
        p.nome as plano,
        c.uso_mb,
        p.limite_mb,
        ROUND((c.uso_mb / p.limite_mb) * 100, 2) as percentual_uso,
        CASE 
            WHEN (c.uso_mb / p.limite_mb) * 100 > 90 THEN 'CRÍTICO'
            WHEN (c.uso_mb / p.limite_mb) * 100 > 70 THEN 'ATENÇÃO'
            ELSE 'NORMAL'
        END as status_uso
    FROM clientes c
    LEFT JOIN planos p ON c.plano_id = p.id
    WHERE c.status = 'ativo'
    ORDER BY percentual_uso DESC;
END$$

-- Procedure: Atualizar uso de disco de um cliente
CREATE PROCEDURE `sp_atualizar_uso_cliente`(
    IN p_cliente_id INT,
    IN p_novo_uso_mb INT
)
BEGIN
    DECLARE v_limite_mb INT;
    
    -- Buscar limite do plano
    SELECT p.limite_mb INTO v_limite_mb
    FROM clientes c
    JOIN planos p ON c.plano_id = p.id
    WHERE c.id = p_cliente_id;
    
    -- Atualizar uso
    UPDATE clientes 
    SET uso_mb = p_novo_uso_mb,
        atualizado_em = NOW()
    WHERE id = p_cliente_id;
    
    -- Criar notificação se uso > 90%
    IF (p_novo_uso_mb / v_limite_mb) * 100 > 90 THEN
        INSERT INTO notificacoes (cliente_id, titulo, mensagem, tipo)
        VALUES (
            p_cliente_id,
            'Uso de disco crítico',
            CONCAT('O uso de disco atingiu ', ROUND((p_novo_uso_mb / v_limite_mb) * 100, 1), '% do limite.'),
            'aviso'
        );
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- EVENTOS AGENDADOS
-- =====================================================

-- Evento: Limpeza de logs antigos (executar diariamente)
CREATE EVENT IF NOT EXISTS `ev_limpeza_logs`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
  DELETE FROM logs_admin WHERE criado_em < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Evento: Atualização de faturas vencidas (executar diariamente)
CREATE EVENT IF NOT EXISTS `ev_atualizar_faturas_vencidas`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
  UPDATE faturas 
  SET status = 'vencido' 
  WHERE status = 'pendente' 
  AND data_vencimento < CURDATE();

-- =====================================================
-- COMENTÁRIOS FINAIS
-- =====================================================

/*
INSTRUÇÕES DE INSTALAÇÃO:

1. Execute este script em seu servidor MySQL
2. Ajuste as configurações de conexão em config/database.php
3. O usuário padrão é: admin@nicebee.com.br / 123456
4. Certifique-se de que o MySQL tenha permissões para criar bancos e usuários
5. Para produção, altere a senha padrão imediatamente

RECURSOS IMPLEMENTADOS:
- ✅ Estrutura completa de tabelas
- ✅ Relacionamentos com integridade referencial
- ✅ Triggers para auditoria automática
- ✅ Views para consultas otimizadas
- ✅ Procedimentos armazenados úteis
- ✅ Eventos agendados para manutenção
- ✅ Índices para performance
- ✅ Dados iniciais para teste

PRÓXIMOS PASSOS:
- Configure backup automático
- Implemente monitoramento de performance
- Configure alertas por email
- Adicione mais planos conforme necessário
*/