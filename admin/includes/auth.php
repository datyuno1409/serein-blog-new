<?php

function requireAuth() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: index.php');
        exit();
    }
}

function getCurrentUser() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_user_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'role' => $_SESSION['admin_role'] ?? null
    ];
}

function hasRole($requiredRole) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    $roleHierarchy = [
        'viewer' => 1,
        'editor' => 2,
        'admin' => 3
    ];
    
    $userLevel = $roleHierarchy[$user['role']] ?? 0;
    $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
    
    return $userLevel >= $requiredLevel;
}

function isAdmin() {
    return hasRole('admin');
}

function isLoggedIn() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function isEditor() {
    return hasRole('editor');
}

function logout() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    session_unset();
    session_destroy();
    
    header('Location: index.php');
    exit();
}

function getUsername() {
    $user = getCurrentUser();
    return $user ? $user['username'] : 'Guest';
}

function getUserRole() {
    $user = getCurrentUser();
    return $user ? $user['role'] : 'guest';
}

function getUserId() {
    $user = getCurrentUser();
    return $user ? $user['id'] : null;
}

function includeAdminHeader($title = 'Admin Panel', $page = '') {
    echo "<!DOCTYPE html>\n";
    echo "<html lang='en'>\n";
    echo "<head>\n";
    echo "    <meta charset='UTF-8'>\n";
    echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
    echo "    <title>$title - Admin Panel</title>\n";
    echo "    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css'>\n";
    echo "    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>\n";
    echo "</head>\n";
    echo "<body class='hold-transition sidebar-mini'>\n";
    echo "<div class='wrapper'>\n";
}

function includeAdminFooter() {
    echo "</div>\n";
    echo "<script src='https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js'></script>\n";
    echo "<script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>\n";
    echo "</body>\n";
    echo "</html>\n";
}
?>