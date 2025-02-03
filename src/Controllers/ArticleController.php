<?php
//引入Model
require_once __DIR__ . "/../Models/Article.php";

class ArticleController { //處理請求邏輯、回應格式
    private $article;
    private $auth;

    public function __construct() {
        //建立Model實例
        $this->article = new Article();
        $this->auth = new AuthMiddleware();
    }

    //取得文章列表 
    public function index() {
        try {
            if (isset($_GET['page'])) {
                $page = (int)$_GET['page'];
                $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10;

                $result = $this->article->getArticlesWithPagination($page, $perPage);
            } else {
                $result = $this->article->getAllArticles();
            }

            $this->sendResponse([
                'status' => 'success',
                'data' => $result,
                'message' => '成功取得文章列表'
            ]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function store() {
        try {
            //從請求中獲得數據
            $data = json_decode(file_get_contents('php://input'), true);

            //從JWT token中獲取使用者id
            $user = $this->auth->verifyToken();

            //驗證輸入數據
            if (!isset($data['title']) || !isset($data['content'])) {
                $this->sendError('標題與內容不得空白', 400);
                return;
            }

            //調用模型方法新增文章
            $artcileId = $this->article->createArticle($data['title'], $data['content'], $user->user_id);

            //回傳成功訊息，包含新文章id
            $this->sendResponse([
                'status' => 'success',
                'data' => ['id' => $artcileId],
                'message' => '已新增文章'
            ], 201);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    //取得指定id文章
    /**
     * 取得指定 ID 的文章
     * 
     * @param int $id 文章ID
     * @return void
     * 
     * 運作流程：
     * 1. 嘗試從資料庫取得指定ID的文章
     * 2. 如果找到文章，回傳成功回應
     * 3. 如果找不到文章，回傳 404 錯誤
     * 4. 如果發生其他錯誤，回傳 500 錯誤
     */
    public function show(int $id) {
        try {
            $result = $this->article->getArticle($id);

            if ($result) {
                //找到文章，回傳成功回應
                $this->sendResponse([
                    'status' => 'success',
                    'data' => $result,
                    'message' => '已找到指定文章'
                ]);
            } else {
                //找不到指定文章
                $this->sendError('找不到指定文章', 404);
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    /**
     * 更新文章
     *
     * @param integer $id 文章id
     * @return void
     * 
     * 1. 檢查指定$id文章是否存在，未找到回傳錯誤訊息
     * 2. 取得欲更新資料並驗證資料是否存在，不符合格式回傳錯誤訊息
     * 3. 使用模型方法進行資料更新
     * 4. 如果成功回傳成功訊息，失敗則反之
     */
    public function update(int $id) {
        try {
            //檢查文章是否存在
            $article = $this->article->getArticle($id);

            if (!$article) {
                $this->sendError('找不到此文章', 404);
                return; //錯誤後立刻返回
            }

            //取得並驗證更新資料
            $data = json_decode(file_get_contents('php://input'), true);

            $user = $this->auth->verifyToken();

            if (!isset($data['title']) || !isset($data['content'])) {
                $this->sendError('標題及內容不得空白', 400);
                return;
            }

            //進行更新
            $success = $this->article->updateArticle($id, $data['title'], $data['content'], $user->user_id);

            if ($success) {
                $this->sendResponse([
                    'status' => 'success',
                    'message' => '文章已更新'
                ]);
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function delete(int $id) {
        try {
            //檢查文章是否存在
            $article = $this->article->getArticle($id);
            if (!$article) {
                $this->sendError('找不到要刪除的文章', 404);
                return;
            }

            $user = $this->auth->verifyToken();

            //執行刪除並檢查結果
            $success = $this->article->deleteArticle($id, $user->user_id);
            if ($success) {
                $this->sendResponse([
                    'status' => 'success',
                    'message' => '已刪除文章'
                ], 204); //204 No Content
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    //處理回應
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
