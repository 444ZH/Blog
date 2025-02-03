<?php
require_once __DIR__ . "/../Models/User.php";

use Firebase\JWT\JWT;

class AuthController {
    private $user;
    private $secretKey;
    private $expireTime;

    public function __construct() {
        $this->user = new User();
        $this->secretKey = Config::get('jwt.secret_key');
        $this->expireTime = Config::get('jwt.expire_time');
    }

    public function register() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            //驗證必要欄位
            if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
                $this->sendError('使用者名稱、密碼、電子信箱皆不得空白', 400);
                return;
            }

            //驗證電子信箱格式
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->sendError('電子信箱格式有誤', 400);
            }

            //密碼長度檢查
            if (strlen($data['password']) < 6 && strlen($data['password']) > 20) {
                $this->sendError('密碼需包含6-20位以內大小寫英文及數字', 400);
            }

            //檢查使用者名稱是否已存在
            if ($this->user->findByUsername($data['username'])) {
                $this->sendError('使用者名稱已被使用', 400);
            }

            //建立新使用者
            $userId = $this->user->create(
                $data['username'],
                $data['password'],
                $data['email']
            );

            $this->sendResponse([
                'status' => 'success',
                'message' => '會員註冊成功',
                'data' => ['user_id' => $userId]
            ], 201);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function login() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            //驗證輸入
            if (!isset($data['username']) || !isset($data['password'])) {
                $this->sendError('請輸入使用者名稱及密碼', 400); //Bad Request
                return;
            }

            //查詢使用者、驗證密碼
            $user = $this->user->findByUsername($data['username']);
            if (!$user || !password_verify($data['password'], $user['password'])) {
                $this->sendError('使用者名稱或密碼錯誤', 401); //Unauthorized 需要身分驗證
                return;
            }

            //產生JWT
            $token = JWT::encode([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'exp' => time() + $this->expireTime //一小時候過期
            ], $this->secretKey, 'HS256');

            $this->sendResponse([
                'status' => 'success',
                'data' => ['token' => $token],
                'message' => '登入成功'
            ]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function sendResponse(mixed $data, int $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    private function sendError(string $message, int $statusCode = 500) {
        $this->sendResponse([
            'status' => 'error',
            'message' => $message
        ], $statusCode);
    }
}
