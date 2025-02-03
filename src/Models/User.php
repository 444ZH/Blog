<?php
require_once __DIR__ . "/Database.php";

class User {
    private $database;

    public function __construct() {
        $this->database = Database::getInstance()->getConnection();
    }

    public function findByUsername(string $username): ?array {
        try {
            $stmt = $this->database->prepare("SELECT * FROM users WHERE username = :username");

            $stmt->execute([':username' => $username]);

            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            throw new Exception('查詢使用者失敗', $e->getMessage());
        }
    }

    public function create(string $username, string $password, string $email): int {
        try {
            $stmt = $this->database->prepare(
                "INSERT INTO users (username, password, email)
                VALUES (:username, :password, :email)"
            );

            $stmt->execute([
                ':username' => $username,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':email' => $email
            ]);

            return $this->database->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("建立帳號失敗: {$e->getMessage()}");
        }
    }
}
