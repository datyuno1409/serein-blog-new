<?php
// Admin logout handler

require_once '../config/db.php';
require_once 'auth.php';

// Log the logout activity
if (AdminAuth::isLoggedIn()) {
    AdminAuth::logActivity('logout', 'User logged out');
}

// Perform logout
AdminAuth::logout();
?>