<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/src/Config/Config.php";
require_once __DIR__ . "/src/Models/Database.php";
require_once __DIR__ . "/src/Middleware/AuthMiddleware.php";

//獲取請求路徑
$path = $_GET['path'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if (empty($_GET['path'])) {
    // 如果是直接訪問，返回錯誤
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => '不允許直接訪問此檔案',
        'debug' => [
            'request_uri' => $_SERVER['REQUEST_URI'],
            'query_string' => $_SERVER['QUERY_STRING']
        ]
    ]);
    exit;
}

//將路徑分割成段落
$segments = explode('/', trim($path, '/'));

//路由對應表
$routes = [
    'articles' => 'ArticleController',
    'auth' => 'AuthController'
];

//需要驗證的路由
$protectedRoutes = [
    'articles' => ['POST', 'PUT', 'DELETE'] //新增、更新、刪除需要驗證
];


//基本路由邏輯
if (!empty($segments[0]) && isset($routes[$segments[0]])) {
    $controllerName = $routes[$segments[0]];

    //驗證檢查
    if (isset($protectedRoutes[$segments[0]]) && in_array($method, $protectedRoutes[$segments[0]])) {
        $auth = new AuthMiddleware();
        $auth->verifyToken(); //驗證失敗會直接終止返回錯誤
    }

    //引入Controller
    require_once __DIR__ . "/src/Controllers/{$controllerName}.php";

    $controller = new $controllerName();

    //處理認證相關路由
    if ($segments[0] === 'auth') {
        if (count($segments) === 2) {
            if ($segments[1] === 'login' && $method === 'POST') {
                $controller->login();
                return;
            } elseif ($segments[1] === 'register' && $method === 'POST') {
                $controller->register();
                return;
            }
        }
    } elseif ($segments[0] === 'articles') { //根據路徑和方法決定要呼叫的動作
        if (count($segments) === 1) {
            //處理集合資源
            switch ($method) {
                case 'GET':
                    $controller->index(); //取得文章列表
                    break;

                case 'POST':
                    $controller->store(); //新增文章
                    break;
            }
        } elseif (count($segments) === 2) {
            //處理單一資源
            $id = $segments[1];
            if (!is_numeric($id)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => '無效id'
                ]);
                return;
            }
            switch ($method) {
                case 'GET':
                    $controller->show($id); //取得單一指定文章
                    break;

                case 'PUT':
                    $controller->update($id); //更新文章
                    break;

                case 'DELETE':
                    $controller->delete($id); //刪除文章
                    break;
            }
        }
    }
} else {
    //找不到對應路由
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => '找不到請求的資源'
    ]);
}
