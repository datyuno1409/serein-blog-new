<?php
// Admin authentication and session management

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AdminAuth {
    
    public static function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: index.php');
            exit();
        }
    }
    
    public static function getAdminId() {
        return $_SESSION['admin_id'] ?? null;
    }
    
    public static function getAdminUsername() {
        return $_SESSION['admin_username'] ?? null;
    }
    
    public static function getAdminRole() {
        return $_SESSION['admin_role'] ?? null;
    }
    
    public static function logout() {
        // Clear all session data
        $_SESSION = array();
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Redirect to login
        header('Location: index.php');
        exit();
    }
    
    public static function updateLastActivity() {
        $_SESSION['last_activity'] = time();
    }
    
    public static function checkSessionTimeout($timeout = 3600) { // 1 hour default
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::logout();
        }
        self::updateLastActivity();
    }
    
    public static function hasPermission($permission) {
        $role = self::getAdminRole();
        
        // Define role permissions
        $permissions = [
            'admin' => ['all'], // Admin has all permissions
            'editor' => ['articles', 'projects', 'seo'],
            'moderator' => ['articles']
        ];
        
        if (!isset($permissions[$role])) {
            return false;
        }
        
        return in_array('all', $permissions[$role]) || in_array($permission, $permissions[$role]);
    }
    
    public static function requirePermission($permission) {
        if (!self::hasPermission($permission)) {
            header('HTTP/1.0 403 Forbidden');
            die('Access denied. Insufficient permissions.');
        }
    }
    
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function logActivity($action, $details = '') {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO admin_logs (admin_id, username, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                self::getAdminId(),
                self::getAdminUsername(),
                $action,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log('Failed to log admin activity: ' . $e->getMessage());
        }
    }
}

// Auto-check session timeout on every page load
if (AdminAuth::isLoggedIn()) {
    AdminAuth::checkSessionTimeout();
}

// Helper function to include header
function includeAdminHeader($title = 'Admin Panel', $activeMenu = '') {
    AdminAuth::requireLogin();
    $adminUsername = AdminAuth::getAdminUsername();
    $csrfToken = AdminAuth::generateCSRFToken();
    include 'includes/header.php';
}

// Helper function to include footer
function includeAdminFooter() {
    include 'includes/footer.php';
}

// Helper function for success messages
function showSuccessMessage($message) {
    return '<div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="fas fa-check"></i> ' . htmlspecialchars($message) . '
    </div>';
}

// Helper function for error messages
function showErrorMessage($message) {
    return '<div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($message) . '
    </div>';
}

// Helper function for info messages
function showInfoMessage($message) {
    return '<div class="alert alert-info alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="fas fa-info"></i> ' . htmlspecialchars($message) . '
    </div>';
}
?>