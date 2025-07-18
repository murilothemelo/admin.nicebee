<?php
/**
 * Configuração JWT
 * Gerenciamento de tokens de autenticação
 */

class JWT {
    private $secret_key = "nicebee_admin_secret_key_2024_production_ready"; // Chave mais segura
    private $issuer = "admin.nicebee.com.br";
    private $audience = "admin.nicebee.com.br";
    private $issuedAt;
    private $expire;

    public function __construct() {
        $this->issuedAt = time();
        $this->expire = $this->issuedAt + (60 * 60 * 24); // 24 horas
    }

    public function encode($data) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload = json_encode([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $this->issuedAt,
            'exp' => $this->expire,
            'data' => $data
        ]);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secret_key, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    public function decode($jwt) {
        if (empty($jwt) || strpos($jwt, 'mock_token') !== false) {
            return false; // Rejeitar tokens mock
        }
        
        $tokenParts = explode('.', $jwt);
        
        if (count($tokenParts) != 3) {
            return false;
        }

        try {
            $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
            $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
        } catch (Exception $e) {
            return false;
        }
        
        $signatureProvided = $tokenParts[2];

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secret_key, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        if ($base64Signature !== $signatureProvided) {
            return false;
        }

        $payloadData = json_decode($payload, true);
        
        if (!$payloadData || !isset($payloadData['exp'])) {
            return false;
        }
        
        if ($payloadData['exp'] < time()) {
            return false;
        }

        return $payloadData['data'];
    }

    public function validateToken() {
        $headers = getallheaders() ?: [];
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                     (isset($headers['authorization']) ? $headers['authorization'] : null);

        if (!$authHeader) {
            // Tentar pegar do $_SERVER se getallheaders() falhar
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        }
        
        if (!$authHeader) {
            return false;
        }

        $token = str_replace('Bearer ', '', $authHeader);
        
        if (empty($token) || strpos($token, 'mock_token') !== false) {
            return false; // Rejeitar tokens mock
        }
        
        return $this->decode($token);
    }
}
?>