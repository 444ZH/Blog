<?php
class Config {
    private static $config = null;

    public static function get($key) {
        if (self::$config === null) {
            //載入所有設定檔
            self::$config = require_once __DIR__ . "/../../config/app.php";
        }

        //支援使用點符號存取多層設定
        $keys = explode('.', $key);

        $value = self::$config;

        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
