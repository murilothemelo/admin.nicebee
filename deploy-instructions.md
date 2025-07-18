# 🚀 INSTRUÇÕES COMPLETAS DE DEPLOY
## Painel Administrativo Multi-Tenant admin.nicebee.com.br

### 📋 PRÉ-REQUISITOS

**Servidor/Hospedagem:**
- cPanel com PHP 7.4+ e MySQL 5.7+
- Domínio: admin.nicebee.com.br
- SSL configurado
- Acesso SSH (opcional, mas recomendado)

**Ferramentas Locais:**
- Node.js 16+
- Git
- Editor de código

---

## 🔧 PASSO 1: PREPARAR O PROJETO LOCALMENTE

### 1.1 Build do Frontend React
```bash
# No diretório do projeto
npm run build

# Isso criará a pasta 'dist' com os arquivos otimizados
```

### 1.2 Configurar Variáveis de Ambiente
Crie o arquivo `.env.production`:
```env
VITE_API_URL=https://admin.nicebee.com.br/api
VITE_APP_NAME=NiceBee Admin
VITE_APP_VERSION=1.0.0
```

---

## 🗄️ PASSO 2: CONFIGURAR BANCO DE DADOS

### 2.1 Criar Banco no cPanel
1. Acesse **MySQL Databases** no cPanel
2. Crie o banco: `admin_nicebee`
3. Crie usuário: `admin_nicebee_user`
4. Defina senha forte
5. Associe usuário ao banco com **ALL PRIVILEGES**

### 2.2 Importar Schema
1. Acesse **phpMyAdmin**
2. Selecione o banco `admin_nicebee`
3. Importe o arquivo `database/schema.sql`
4. Verifique se todas as tabelas foram criadas

### 2.3 Configurar Usuário Admin
```sql
-- Alterar senha do admin padrão
UPDATE usuarios_admin 
SET senha_hash = '$2y$10$SEU_HASH_AQUI' 
WHERE email = 'admin@nicebee.com.br';

-- Para gerar hash da senha:
-- Use: password_hash('sua_senha_segura', PASSWORD_DEFAULT)
```

---

## 📁 PASSO 3: UPLOAD DOS ARQUIVOS

### 3.1 Estrutura no Servidor
```
public_html/admin/
├── index.html (do build)
├── assets/ (do build)
├── .htaccess
├── api/
│   ├── config/
│   ├── auth/
│   ├── clientes/
│   ├── planos/
│   ├── faturas/
│   ├── dashboard/
│   ├── backups/
│   ├── logs/
│   └── .htaccess
├── backups/ (criar pasta)
└── logs/ (criar pasta)
```

### 3.2 Upload via cPanel File Manager
1. **Frontend**: Upload do conteúdo da pasta `dist/` para `public_html/admin/`
2. **Backend**: Upload da pasta `api/` completa
3. **Configurações**: Upload dos arquivos `.htaccess`

### 3.3 Criar Diretórios Necessários
```bash
# Via SSH ou File Manager
mkdir public_html/admin/backups
mkdir public_html/admin/logs
chmod 755 public_html/admin/backups
chmod 755 public_html/admin/logs
```

---

## ⚙️ PASSO 4: CONFIGURAR A API

### 4.1 Editar Configurações do Banco
Arquivo: `api/config/database.php`
```php
private $host = "localhost";
private $db_name = "admin_nicebee";
private $username = "admin_nicebee_user";
private $password = "SUA_SENHA_AQUI";
```

### 4.2 Configurar JWT
Arquivo: `api/config/jwt.php`
```php
private $secret_key = "SUA_CHAVE_SECRETA_UNICA_AQUI_2024";
```

### 4.3 Configurar Criptografia
Nos arquivos que usam criptografia, altere:
```php
$encryption_key = "SUA_CHAVE_DE_CRIPTOGRAFIA_32_CHARS";
$iv = "SUA_IV_16_CHARS_"; // 16 caracteres exatos
```

### 4.4 Testar Conexão
Acesse: `https://admin.nicebee.com.br/api/dashboard/stats`
- Deve retornar erro de token (esperado)
- Se retornar erro de conexão, revisar configurações

---

## 🔐 PASSO 5: CONFIGURAR SEGURANÇA

### 5.1 Permissões de Arquivos
```bash
# Via SSH
find public_html/admin -type f -exec chmod 644 {} \;
find public_html/admin -type d -exec chmod 755 {} \;
chmod 600 public_html/admin/api/config/*.php
```

### 5.2 SSL e HTTPS
1. Ative SSL no cPanel
2. Force HTTPS (descomente no .htaccess)
3. Teste: `https://admin.nicebee.com.br`

### 5.3 Backup de Segurança
1. Configure backup automático no cPanel
2. Teste backup manual do banco
3. Documente credenciais em local seguro

---

## 🧪 PASSO 6: TESTES FINAIS

### 6.1 Teste de Login
1. Acesse: `https://admin.nicebee.com.br`
2. Login: `admin@nicebee.com.br`
3. Senha: (a que você configurou)
4. Verifique se o dashboard carrega

### 6.2 Teste de Funcionalidades
- ✅ Dashboard com métricas
- ✅ Listagem de clientes
- ✅ Criação de novo cliente
- ✅ Gestão de planos
- ✅ Sistema de faturas
- ✅ Logs de auditoria

### 6.3 Teste de Criação de Cliente
1. Crie um cliente teste
2. Verifique se o banco foi criado no MySQL
3. Verifique se o usuário foi criado
4. Teste as permissões

---

## 📊 PASSO 7: MONITORAMENTO

### 7.1 Logs de Erro
- Monitore: `public_html/admin/logs/api_error.log`
- Configure alertas para erros críticos

### 7.2 Backup Automático
```bash
# Cron job para backup diário (via cPanel Cron Jobs)
0 2 * * * mysqldump -u admin_nicebee_user -p'SENHA' admin_nicebee > /home/usuario/backups/admin_$(date +\%Y\%m\%d).sql
```

### 7.3 Monitoramento de Uso
- Monitore uso de disco dos clientes
- Configure alertas para limites
- Acompanhe logs de acesso

---

## 🔧 CONFIGURAÇÕES ADICIONAIS

### Configurar Email (Opcional)
Para notificações automáticas:
```php
// Em api/config/email.php
$smtp_host = "mail.nicebee.com.br";
$smtp_user = "noreply@nicebee.com.br";
$smtp_pass = "senha_email";
```

### Configurar Cron Jobs
1. **Backup Automático**: Diário às 2h
2. **Verificação de Limites**: A cada hora
3. **Geração de Faturas**: Todo dia 1º do mês

### Otimizações de Performance
```sql
-- No MySQL
SET GLOBAL innodb_buffer_pool_size = 268435456;
SET GLOBAL query_cache_size = 67108864;
SET GLOBAL query_cache_type = 1;
```

---

## 🆘 TROUBLESHOOTING

### Erro 500 - Internal Server Error
- Verifique permissões dos arquivos
- Confira logs de erro do Apache
- Teste configurações PHP

### Erro de Conexão com Banco
- Verifique credenciais em `database.php`
- Teste conexão via phpMyAdmin
- Confirme nome do banco e usuário

### Problemas de CORS
- Verifique headers em `cors.php`
- Confirme configurações do .htaccess
- Teste com ferramentas de desenvolvedor

### Erro de Token JWT
- Verifique chave secreta em `jwt.php`
- Confirme headers de autorização
- Teste login manual

---

## ✅ CHECKLIST FINAL

- [ ] Banco de dados criado e configurado
- [ ] Arquivos enviados para servidor
- [ ] Configurações de API atualizadas
- [ ] SSL ativo e funcionando
- [ ] Login administrativo funcionando
- [ ] Dashboard carregando métricas
- [ ] Criação de cliente testada
- [ ] Backup automático configurado
- [ ] Logs de erro monitorados
- [ ] Documentação atualizada

---

## 📞 SUPORTE

**Em caso de problemas:**
1. Verifique logs de erro
2. Teste cada endpoint da API individualmente
3. Confirme permissões e configurações
4. Documente erros para análise

**Contatos:**
- Email: suporte@nicebee.com.br
- Documentação: Esta mesma pasta
- Logs: `public_html/admin/logs/`

---

🎉 **PARABÉNS!** Seu painel administrativo multi-tenant está funcionando!

Acesse: **https://admin.nicebee.com.br**