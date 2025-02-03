<?php
require_once __DIR__ . "/../Config/Config.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {
    private $secretKey;

    public function __construct() {
        $this->secretKey = Config::get('jwt.secret_key');
    }

    public function verifyToken() {
        try {
            $headers = getallheaders();
            if (!isset($headers['Authorization'])) {
                throw new Exception('未提供認證token');
            }

            $token = str_replace('Bearer ', '', $headers['Authorization']);

            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));

            return $decoded;
        } catch (Exception $e) {
            header('HTTP/1.0 401 Unauthorized');
            echo json_encode([
                'status' => 'error',
                'message' => "無效token: {$e->getMessage()}"
            ]);
            exit;
        }
    }
}
