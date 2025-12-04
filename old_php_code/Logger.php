<?php

class Logger {
    private static $logDir = '../logs/';
    private static $maxFileSize = 10485760; // 10MB
    private static $maxFiles = 5;
    
    public static function init() {
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }
    
    public static function log($level, $message, $context = []) {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $logFile = self::$logDir . date('Y-m-d') . '.log';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        
        self::rotateLogIfNeeded($logFile);
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    public static function error($message, $context = []) {
        self::log('error', $message, $context);
    }
    
    public static function warning($message, $context = []) {
        self::log('warning', $message, $context);
    }
    
    public static function info($message, $context = []) {
        self::log('info', $message, $context);
    }
    
    public static function debug($message, $context = []) {
        self::log('debug', $message, $context);
    }
    
    public static function security($message, $context = []) {
        self::log('security', $message, $context);
    }
    
    private static function rotateLogIfNeeded($logFile) {
        if (!file_exists($logFile) || filesize($logFile) < self::$maxFileSize) {
            return;
        }
        
        for ($i = self::$maxFiles - 1; $i > 0; $i--) {
            $oldFile = $logFile . '.' . $i;
            $newFile = $logFile . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i == self::$maxFiles - 1) {
                    unlink($oldFile);
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        rename($logFile, $logFile . '.1');
    }
    
    public static function logException($exception, $context = []) {
        $errorContext = array_merge($context, [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        self::error($exception->getMessage(), $errorContext);
    }
    
    public static function logSecurityEvent($event, $details = []) {
        $securityContext = array_merge($details, [
            'event_type' => $event,
            'session_id' => session_id(),
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
        ]);
        
        self::security($event, $securityContext);
    }
}
?>