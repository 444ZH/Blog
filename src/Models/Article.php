<?php
require_once __DIR__ . '/Database.php';

class Article { //處理資料庫操作
    private $database; //資料庫連線

    public function __construct() {
        //取得連線
        $this->database = Database::getInstance()->getConnection();
        if (!$this->database) {
            throw new Exception("無法連線資料庫");
        }
    }

    /**
     * 用途: 取得所有文章
     *
     * @return array 回傳articles資料表的所有資料
     * 
     */
    public function getAllArticles(): array {
        try {
            $stmt = $this->database->query(
                "SELECT * 
                FROM articles 
                ORDER BY created_at DESC"
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("無法取得文章列表: {$e->getMessage()}");
        }
    }

    /**
     * 用途: 取得指定 id 的文章
     * 
     * @param int $id 文章ID
     * @return array|null 回傳文章資料陣列，如果找不到則回傳 null
     * @throws Exception 如果查詢過程發生錯誤
     */
    public function getArticle(int $id): ?array {
        try {
            $stmt = $this->database->prepare("SELECT * FROM articles WHERE id = :id");

            $stmt->execute([
                ':id' => $id
            ]);

            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            throw new Exception("尋找指定文章失敗: {$e->getMessage()}");
        }
    }

    /**
     * 新增文章到資料庫
     * 
     * @param string $title 文章標題
     * @param string $content 文章內容
     * @return int 新增文章的 ID
     * @throws Exception 如果新增失敗
     */
    public function createArticle(string $title, string $content, int $userId): int {
        try {
            //準備SQL語句
            $sql = "INSERT INTO articles (title, content, user_id)
                    VALUES (:title, :content, :user_id)";
            $stmt = $this->database->prepare($sql);

            //執行SQL，使用參數綁訂避免SQL注入
            $stmt->execute([
                ":title" => $title,
                ":content" => $content,
                "user_id" => $userId
            ]);

            //回傳新增文章的id
            return $this->database->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("新增文章失敗: {$e->getMessage()}");
        }
    }

    /**
     * 更新文章內容
     * 
     * @param int $id 要更新的文章ID
     * @param string $title 新的標題
     * @param string $content 新的內容
     * @return bool 更新是否成功
     * @throws Exception 如果更新失敗
     */
    public function updateArticle(int $id, string $title, string $content, int $userId): bool {
        try {
            //檢查文章是否屬於該使用者
            $currentArticle = $this->getArticle($id);

            if (!$currentArticle || $currentArticle['user_id'] !== $userId) {
                throw new Exception("沒有權限編輯此文章");
            }

            $sql = "UPDATE articles
                    SET title = :title,
                        content = :content
                    WHERE id = :id AND user_id = :user_id";

            $stmt = $this->database->prepare($sql);

            return $stmt->execute([
                ':id' => $id,
                ':title' => $title,
                ':content' => $content,
                ':user_id' => $userId
            ]);
        } catch (PDOException $e) {
            throw new Exception("更新文章失敗: {$e->getMessage()}");
        }
    }

    /**
     * 刪除指定的文章
     * 
     * @param int $id 要刪除的文章ID
     * @return bool 刪除是否成功
     * @throws Exception 如果刪除過程發生錯誤
     * 
     * 運作原理：
     * 1. 接收文章ID
     * 2. 準備 DELETE SQL 語句
     * 3. 使用參數綁定避免 SQL 注入
     * 4. 執行刪除操作
     * 5. 回傳執行結果
     */
    public function deleteArticle(int $id, int $userId): bool {
        try {
            $currentArticle = $this->getArticle($id);

            if (!$currentArticle || $currentArticle['user_id'] !== $userId) {
                throw new Exception("沒有刪除此文章權限");
            }

            $sql = "DELETE FROM articles
                    WHERE id = :id AND user_id = :user_id";

            $stmt = $this->database->prepare($sql);

            return $stmt->execute([
                ':id' => $id,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            throw new Exception("刪除文章失敗: {$e->getMessage()}");
        }
    }

    /**
     * 取得分頁後的文章列表
     * 
     * @param int $page 目前的頁數（從1開始）
     * @param int $perPage 每頁顯示的文章數量
     * @return array 包含分頁資訊和文章列表的陣列
     * 
     * 運作原理：
     * 1. 計算要跳過的文章數量（OFFSET）
     * 2. 設定每頁要顯示的數量（LIMIT）
     * 3. 同時取得總文章數，用於計算總頁數
     * 4. 回傳完整的分頁資訊
     */
    public function getArticlesWithPagination(int $page = 1, int $perPage = 10): array {
        try {
            //確保頁數至少為 1
            $page = max(1, $page);

            //計算要跳過的頁數
            $offset = ($page - 1) * $perPage;

            //取的文章總數量
            $totalStmt = $this->database->query("SELECT COUNT(*) FROM articles");
            $total = $totalStmt->fetchColumn();

            //計算頁數總數
            $totalPage = ceil($total / $perPage);

            //取得目前頁面的文章
            $sql = "SELECT * 
                FROM articles
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";
            $stmt = $this->database->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $articles = $stmt->fetchAll();

            //回傳分頁資訊及文章列表
            return [
                'metadata' => [
                    'currentPage' => $page, //目前頁數
                    'perPage' => $perPage, //每頁文章數
                    'totalItems' => $total, //文章總數量
                    'totalPages' => $totalPage, //總頁數
                    'hasNextPage' => $page < $totalPage, //是否有下一頁
                    'hasPrevPage' => $page > 1 //是否有前一頁
                ],
                'data' => $articles
            ];
        } catch (PDOException $e) {
            throw new Exception("取得文章列表失敗: {$e->getMessage()}");
        }
    }
}
