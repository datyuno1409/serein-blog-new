<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Check if draft exists and is not too old (24 hours)
    if (isset($_SESSION['about_draft']) && isset($_SESSION['about_draft_timestamp'])) {
        $draftAge = time() - $_SESSION['about_draft_timestamp'];
        
        // If draft is less than 24 hours old, return it
        if ($draftAge < 86400) {
            echo json_encode($_SESSION['about_draft']);
        } else {
            // Draft is too old, remove it
            unset($_SESSION['about_draft']);
            unset($_SESSION['about_draft_timestamp']);
            echo json_encode([]);
        }
    } else {
        echo json_encode([]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>