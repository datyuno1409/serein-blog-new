<?php

require_once 'Logger.php';

class RateLimit {
    private static $limits = [
        'default' => ['requests' => 60, 'window' => 60],
        'login' => ['requests' => 5, 'window' => 300],
        'api' => ['requests' => 100, 'window' => 60],
        'upload' => ['requests' => 10, 'window' => 60]
    ];
    
    public static function check($identifier, $type = 'default') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $limit = self::$limits[$type] ?? self::$limits['default'];
        $key = 'rate_limit_' . $type . '_' . $identifier;
        
        $now = time();
        $windowStart = $now - $limit['window'];
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        if (count($_SESSION[$key]) >= $limit['requests']) {
            return false;
        }
        
        $_SESSION[$key][] = $now;
        return true;
    }
    
    public static function enforce($identifier, $type = 'default') {
        if (!self::check($identifier, $type)) {
            Logger::logSecurityEvent('RATE_LIMIT_EXCEEDED', [
                'identifier' => substr($identifier, 0, 8) . '...',
                'type' => $type,
                'limit' => self::$limits[$type]
            ]);
            http_response_code(429);
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => self::$limits[$type]['window']
            ]));
        }
    }
    
    public static function getClientIdentifier() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return hash('sha256', $ip . $userAgent);
    }
    
    public static function getRemainingRequests($identifier, $type = 'default') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $limit = self::$limits[$type] ?? self::$limits['default'];
        $key = 'rate_limit_' . $type . '_' . $identifier;
        
        if (!isset($_SESSION[$key])) {
            return $limit['requests'];
        }
        
        $now = time();
        $windowStart = $now - $limit['window'];
        
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        return max(0, $limit['requests'] - count($_SESSION[$key]));
    }
}
?>