<?php

require_once 'Logger.php';

class CSRF {
    private static $tokenName = 'csrf_token';
    private static $sessionKey = 'csrf_tokens';
    
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        
        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = [];
        }
        
        $_SESSION[self::$sessionKey][$token] = time();
        
        self::cleanExpiredTokens();
        
        return $token;
    }
    
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($token) || !isset($_SESSION[self::$sessionKey][$token])) {
            return false;
        }
        
        $tokenTime = $_SESSION[self::$sessionKey][$token];
        $currentTime = time();
        
        if ($currentTime - $tokenTime > 3600) {
            unset($_SESSION[self::$sessionKey][$token]);
            return false;
        }
        
        unset($_SESSION[self::$sessionKey][$token]);
        return true;
    }
    
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    public static function validateRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST[self::$tokenName] ?? '';
            if (!self::validateToken($token)) {
            Logger::logSecurityEvent('CSRF_TOKEN_INVALID', [
                'token' => substr($token, 0, 8) . '...',
                'request_method' => $_SERVER['REQUEST_METHOD'],
                'post_data_keys' => array_keys($_POST)
            ]);
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
        }
        }
    }
    
    private static function cleanExpiredTokens() {
        if (!isset($_SESSION[self::$sessionKey])) {
            return;
        }
        
        $currentTime = time();
        foreach ($_SESSION[self::$sessionKey] as $token => $time) {
            if ($currentTime - $time > 3600) {
                unset($_SESSION[self::$sessionKey][$token]);
            }
        }
    }
}
?>