<?php
/**
 * Configuração do Banco de Dados
 */

class Database {
    private $host = "localhost";
    private $db_name = "nicebeec_admin";
    private $username = "nicebeec_admin";
    private $password = "123@Elektro";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Definir variáveis de sessão para triggers de auditoria
            if (isset($_SESSION['user']['id'])) {
                $this->conn->exec("SET @current_user_id = " . (int)$_SESSION['user']['id']);
            }
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $this->conn->exec("SET @current_user_ip = '" . $_SERVER['REMOTE_ADDR'] . "'");
            }
            
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            die("Erro de conexão com o banco de dados");
        }
        
        return $this->conn;
    }

    public function createClientDatabase($codigo_cliente) {
        $banco_nome = "nicebeec_cliente_" . $codigo_cliente;
        $banco_usuario = "nicebeec_usr_" . $codigo_cliente;
        $banco_senha = $this->generateSecurePassword();

        try {
            $root_conn = new PDO(
                "mysql:host=" . $this->host,
                $this->username,
                $this->password
            );
            $root_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Criar banco
            $sql = "CREATE DATABASE `$banco_nome` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $root_conn->exec($sql);

            // Criar usuário
            $sql = "CREATE USER '$banco_usuario'@'localhost' IDENTIFIED BY '$banco_senha'";
            $root_conn->exec($sql);

            // Conceder permissões
            $sql = "GRANT ALL PRIVILEGES ON `$banco_nome`.* TO '$banco_usuario'@'localhost'";
            $root_conn->exec($sql);
            $root_conn->exec("FLUSH PRIVILEGES");

            return [
                'banco_nome' => $banco_nome,
                'banco_usuario' => $banco_usuario,
                'banco_senha' => $banco_senha,
                'success' => true
            ];

        } catch(PDOException $exception) {
            error_log("Database creation error: " . $exception->getMessage());
            return ['success' => false, 'error' => $exception->getMessage()];
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

// Instância global
$database = new Database();
$pdo = $database->getConnection();