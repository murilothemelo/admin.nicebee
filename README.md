# Painel Administrativo Multi-Tenant - admin.nicebee.com.br

Sistema completo de administração multi-tenant desenvolvido em React + TypeScript para gerenciar clientes, planos, faturas e backups do sistema NiceBee.

## 🚀 Funcionalidades Implementadas

### ✅ Sistema de Autenticação
- Login seguro para administradores
- Gerenciamento de sessão com JWT
- Timeout automático de sessão
- Diferentes níveis de acesso (admin/operador)

### ✅ Dashboard Executivo
- Métricas em tempo real
- Gráficos de crescimento de clientes
- Alertas e notificações
- Atividade recente do sistema

### ✅ Gestão de Clientes
- Listagem completa com filtros avançados
- Criação automática de bancos de dados
- Geração de códigos únicos (nicebeec_cliente_xxx)
- Controle de uso de armazenamento
- Status de clientes (ativo/inativo/bloqueado)

### ✅ Gestão de Planos
- Criação e edição de planos
- Controle de limites (MB e usuários)
- Valores mensais configuráveis
- Status ativo/inativo

### ✅ Sistema Financeiro
- Controle de faturas
- Status de pagamento
- Filtros por data e status
- Marcação manual de pagamentos
- Relatórios de receita

### 🔄 Em Desenvolvimento
- Sistema de Backups
- Logs de Auditoria
- Configurações do Sistema

## 🛠️ Tecnologias Utilizadas

- **Frontend**: React 18 + TypeScript
- **Styling**: Tailwind CSS
- **Icons**: Lucide React
- **Build Tool**: Vite
- **State Management**: React Context + useReducer

## 📋 Credenciais de Teste

Para acessar o painel administrativo:
- **Email**: admin@nicebee.com.br
- **Senha**: 123456

## 🏗️ Estrutura do Projeto

```
src/
├── components/
│   ├── auth/           # Autenticação e proteção de rotas
│   ├── common/         # Componentes reutilizáveis
│   ├── dashboard/      # Dashboard e métricas
│   ├── clientes/       # Gestão de clientes
│   ├── planos/         # Gestão de planos
│   ├── faturas/        # Sistema financeiro
│   └── layout/         # Layout e navegação
├── contexts/           # Context API para estado global
├── hooks/              # Custom hooks
├── types/              # Definições TypeScript
├── utils/              # Utilitários e dados mock
└── styles/             # Estilos globais
```

## 🎨 Design System

### Cores Principais
- **Azul**: #2563eb (Primary)
- **Verde**: #10b981 (Success)
- **Laranja**: #f59e0b (Warning)
- **Vermelho**: #ef4444 (Error)
- **Cinza**: #6b7280 (Neutral)

### Componentes
- Cards com shadow suave
- Botões com estados de loading
- Modais responsivos
- Tabelas com filtros
- Formulários validados
- Indicadores de progresso

## 🔧 Próximos Passos para Produção

### 1. Backend PHP + MySQL
```php
// Estrutura sugerida da API
/api/
├── auth/
│   ├── login.php
│   └── refresh.php
├── clientes/
│   ├── index.php      # GET /api/clientes
│   ├── create.php     # POST /api/clientes
│   └── [id].php       # PUT/DELETE /api/clientes/{id}
├── planos/
├── faturas/
└── backups/
```

### 2. Banco de Dados Admin
```sql
-- Banco: admin_nicebee
CREATE TABLE usuarios_admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'operador') DEFAULT 'operador',
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    ultimo_login TIMESTAMP NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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
    FOREIGN KEY (plano_id) REFERENCES planos(id)
);

-- Outras tabelas: planos, faturas, backups, logs_admin
```

### 3. Criação Automática de Bancos
```php
// Exemplo de criação automática
function criarClienteCompleto($dadosCliente) {
    $codigoCliente = gerarCodigoUnico();
    $nomeBanco = "nicebeec_cliente_" . $codigoCliente;
    $usuarioBanco = "nicebeec_usr_" . $codigoCliente;
    $senhaBanco = gerarSenhaSegura();
    
    // Criar banco de dados
    $sql = "CREATE DATABASE `$nomeBanco`";
    mysqli_query($conexao, $sql);
    
    // Criar usuário
    $sql = "CREATE USER '$usuarioBanco'@'localhost' IDENTIFIED BY '$senhaBanco'";
    mysqli_query($conexao, $sql);
    
    // Conceder permissões
    $sql = "GRANT ALL PRIVILEGES ON `$nomeBanco`.* TO '$usuarioBanco'@'localhost'";
    mysqli_query($conexao, $sql);
    
    // Salvar no sistema admin
    salvarCliente($dadosCliente, $nomeBanco, $usuarioBanco, $senhaBanco);
}
```

### 4. Configuração do cPanel
- Upload dos arquivos para public_html/admin/
- Configuração do banco admin_nicebee
- Configuração de SSL
- Configuração de .htaccess para SPA

### 5. Variáveis de Ambiente
```env
# .env para produção
VITE_API_URL=https://admin.nicebee.com.br/api
VITE_APP_NAME=NiceBee Admin
VITE_APP_VERSION=1.0.0
```

## 📱 Responsividade

O sistema é totalmente responsivo e funciona perfeitamente em:
- Desktop (1920px+)
- Laptop (1024px+)
- Tablet (768px+)
- Mobile (320px+)

## 🔒 Segurança Implementada

- Autenticação JWT
- Proteção de rotas
- Timeout de sessão
- Validação de formulários
- Sanitização de dados
- Context isolado por funcionalidade

## 📊 Métricas e Analytics

O dashboard fornece:
- Total de clientes ativos
- Receita mensal
- Uso de armazenamento
- Alertas de sistema
- Gráficos de crescimento
- Atividade recente

---

**Sistema desenvolvido para admin.nicebee.com.br**
*Painel Multi-Tenant com React + TypeScript*