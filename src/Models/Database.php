<?php
class Database {
    private static $instance = null; //靜態屬性，用來儲存以建立過的連線
    private $connection; //儲存實際連線方式

    private function __construct() {
        $config = Config::get('database');
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";

        try {
            //建立資料庫連線並存到$connection
            $this->connection = new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION, //PDO錯誤處理模式，會拋出異常讓我麼可以捕捉處理錯誤
                    PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC //決定回傳值回關聯陣列，欄位名稱為key，欄位的值為value
                ]
            );
        } catch (PDOException $e) {
            die("資料庫連線失敗: {$e->getMessage()}");
        }
    }

    /**
     * 為了在其他地方使用不建立實例的情況下，獲取資料庫連線實例(單例模式)
     *
     * 如果靜態屬性$instance回空值
     * 則建立新實例(呼叫並執行建構子)
     * 回傳實例
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 從該實例中取得實際的 PDO 連線物件
     *
     */
    public function getConnection() {
        return $this->connection;
    }
}
