<?php
/**
 * Configuração do Banco de Dados
 * admin.nicebee.com.br
 */

class Database {
    private $host = "localhost";
    private $db_name = "nicebeec_admin";
    private $username = "nicebeec_admin"; // Alterar para produção
    private $password = "123@Elektro";     // Alterar para produção
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4")
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new Exception("Erro de conexão com o banco de dados");
        }
        
        return $this->conn;
    }

    // Conexão dinâmica para bancos de clientes
    public function getClientConnection($banco_nome, $banco_usuario, $banco_senha) {
        try {
            $conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $banco_nome,
                $banco_usuario,
                $banco_senha,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4")
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch(PDOException $exception) {
            error_log("Client database connection error: " . $exception->getMessage());
            return false;
        }
    }

    // Criar banco e usuário para novo cliente
    public function createClientDatabase($codigo_cliente) {
        $banco_nome = "nicebeec_cliente_" . $codigo_cliente;
        $banco_usuario = "nicebeec_usr_" . $codigo_cliente;
        $banco_senha = $this->generateSecurePassword();

        try {
            // Usar conexão root para criar banco e usuário
            $root_conn = new PDO(
                "mysql:host=" . $this->host,
                $this->username,
                $this->password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4")
            );
            $root_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Verificar se banco já existe
            $check_db = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :banco_nome";
            $stmt = $root_conn->prepare($check_db);
            $stmt->bindParam(':banco_nome', $banco_nome);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("Banco de dados já existe: " . $banco_nome);
            }

            // Criar banco de dados
            $sql = "CREATE DATABASE `$banco_nome` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $root_conn->exec($sql);

            // Verificar se usuário já existe
            $check_user = "SELECT User FROM mysql.user WHERE User = :banco_usuario AND Host = 'localhost'";
            $stmt = $root_conn->prepare($check_user);
            $stmt->bindParam(':banco_usuario', $banco_usuario);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Remover usuário existente
                $sql = "DROP USER '$banco_usuario'@'localhost'";
                $root_conn->exec($sql);
            }

            // Criar usuário
            $sql = "CREATE USER '$banco_usuario'@'localhost' IDENTIFIED BY '$banco_senha'";
            $root_conn->exec($sql);

            // Conceder permissões apenas ao banco específico
            $sql = "GRANT ALL PRIVILEGES ON `$banco_nome`.* TO '$banco_usuario'@'localhost'";
            $root_conn->exec($sql);

            // Aplicar mudanças
            $root_conn->exec("FLUSH PRIVILEGES");

            // Criar estrutura básica no banco do cliente
            $this->createClientTables($banco_nome, $banco_usuario, $banco_senha);

            return [
                'banco_nome' => $banco_nome,
                'banco_usuario' => $banco_usuario,
                'banco_senha' => $banco_senha,
                'success' => true
            ];

        } catch(PDOException $exception) {
            error_log("Database creation error: " . $exception->getMessage());
            return [
                'success' => false,
                'error' => $exception->getMessage()
            ];
        } catch(Exception $e) {
            error_log("General error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Criar tabelas básicas no banco do cliente
    private function createClientTables($banco_nome, $banco_usuario, $banco_senha) {
        try {
            $client_conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $banco_nome,
                $banco_usuario,
                $banco_senha,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4")
            );
            $client_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Criar tabela de usuários do cliente
            $sql = "CREATE TABLE IF NOT EXISTS usuarios (
                id INT PRIMARY KEY AUTO_INCREMENT,
                nome VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                senha_hash VARCHAR(255) NOT NULL,
                status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $client_conn->exec($sql);

            // Criar usuário admin padrão para o cliente
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nome, email, senha_hash) VALUES ('Administrador', 'admin@cliente.com', :senha)";
            $stmt = $client_conn->prepare($sql);
            $stmt->bindParam(':senha', $admin_password);
            $stmt->execute();

        } catch(PDOException $exception) {
            error_log("Client tables creation error: " . $exception->getMessage());
            throw new Exception("Erro ao criar estrutura do banco do cliente");
        }
    }

    // Remover banco e usuário do cliente
    public function dropClientDatabase($banco_nome, $banco_usuario) {
        try {
            $root_conn = new PDO(
                "mysql:host=" . $this->host,
                $this->username,
                $this->password
            );
            $root_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Remover banco
            $sql = "DROP DATABASE IF EXISTS `$banco_nome`";
            $root_conn->exec($sql);

            // Remover usuário
            $sql = "DROP USER IF EXISTS '$banco_usuario'@'localhost'";
            $root_conn->exec($sql);

            $root_conn->exec("FLUSH PRIVILEGES");

            return true;
        } catch(PDOException $exception) {
            error_log("Database drop error: " . $exception->getMessage());
            return false;
        }
    }

    private function generateSecurePassword($length = 16) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}
?>