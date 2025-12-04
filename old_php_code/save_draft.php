<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $draftData = [];
    
    // Collect all form data
    $fields = [
        'title', 'subtitle', 'content', 'skills', 'profile_image_url',
        'years_experience', 'location', 'email', 'phone', 'website',
        'social_links', 'certifications', 'education', 'languages',
        'hobbies', 'achievements', 'testimonials', 'meta_title',
        'meta_description', 'show_contact_form', 'show_social_links'
    ];
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $draftData[$field] = $_POST[$field];
        }
    }
    
    // Save draft to session
    $_SESSION['about_draft'] = $draftData;
    $_SESSION['about_draft_timestamp'] = time();
    
    echo json_encode(['success' => true, 'message' => 'Draft saved']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>