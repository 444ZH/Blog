# 使用 PHP 實作的 RESTful API 專案，實作功能有JWT認證機制及文章管理功能

# 認證流程
```mermaid
sequenceDiagram
    participant Client as 用戶端
    participant Router as 路由器
    participant AuthController
    participant UserModel
    participant Database
    
    Client->>Router: POST /api/auth/login
    Note over Client,Router: 包含使用者名稱及密碼的請求
    
    Router->>AuthController: 調用登入方法login()，在控制器驗證輸入資料是否空白
    
    AuthController->>UserModel: User模型查詢使用者資料
    UserModel->>Database: SELECT * FROM users WHERE username = :username
    Database->>UserModel: 回傳使用者資料
    UserModel->>AuthController: 回傳使用者資料
    
    AuthController->>AuthController: 驗證是否有回傳使用者資料，驗證密碼
    Note over AuthController: 使用password_verify()驗證
    
    AuthController->>AuthController: 產生 JWT token
    Note over AuthController: 包含使用者id及權限資訊
    
    AuthController->>Client: 回傳token
    Note over Client,AuthController: HTTP 200 OK + JWT token

# 受保護路徑
    ```mermaid
sequenceDiagram
    participant Client as 用戶端
    participant Router
    participant AuthMiddleware
    participant ArticleController
    participant ArticleModel
    participant Database

    Client->>Router: API 請求 + JWT Token
    Note over Client,Router: 受保護路由及方法

    Router->>AuthMiddleware: 驗證 Token
    AuthMiddleware->>Router: 驗證成功
    Router->>ArticleController: 調用對應方法
    ArticleController->>ArticleModel: 資料操作請求
    ArticleModel->>Database: SQL 查詢
    Database->>ArticleModel: 回傳資料
    ArticleModel->>ArticleController: 處理後的資料
    ArticleController->>Client: JSON 回應