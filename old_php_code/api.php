<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config/db.php';

function sendResponse($success, $data = null, $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

try {
    $endpoint = $_GET['endpoint'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($endpoint) {
        case 'about':
            if ($method === 'GET') {
                $about = fetchOne("SELECT * FROM about ORDER BY id DESC LIMIT 1");
                if ($about) {
                    sendResponse(true, $about);
                } else {
                    sendResponse(false, null, 'About data not found');
                }
            }
            break;
            
        case 'articles':
            if ($method === 'GET') {
                $articles = fetchAll("SELECT id, title, slug, LEFT(content, 200) as excerpt, created_at FROM articles ORDER BY created_at DESC");
                sendResponse(true, $articles);
            }
            break;
            
        case 'article':
            if ($method === 'GET') {
                $slug = $_GET['slug'] ?? '';
                if (empty($slug)) {
                    sendResponse(false, null, 'Slug is required');
                }
                
                $article = fetchOne("SELECT * FROM articles WHERE slug = ?", [$slug]);
                if ($article) {
                    sendResponse(true, $article);
                } else {
                    sendResponse(false, null, 'Article not found');
                }
            }
            break;
            
        case 'projects':
            if ($method === 'GET') {
                $projects = fetchAll("SELECT * FROM projects ORDER BY created_at DESC");
                sendResponse(true, $projects);
            }
            break;
            
        case 'seo':
            if ($method === 'GET') {
                $page = $_GET['page'] ?? 'home';
                $seo = fetchOne("SELECT * FROM seo WHERE page = ?", [$page]);
                if ($seo) {
                    sendResponse(true, $seo);
                } else {
                    // Return default SEO data if not found
                    $defaultSeo = [
                        'title' => 'Serein - Elite Cybersecurity Specialist',
                        'description' => 'Professional cybersecurity services including penetration testing, vulnerability assessment, and security architecture design.',
                        'keywords' => 'cybersecurity, penetration testing, security consultant'
                    ];
                    sendResponse(true, $defaultSeo);
                }
            }
            break;
            
        case 'contact':
            if ($method === 'POST') {
                $name = sanitizeInput($_POST['name'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $subject = sanitizeInput($_POST['subject'] ?? '');
                $message = sanitizeInput($_POST['message'] ?? '');
                
                if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                    sendResponse(false, null, 'All fields are required');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    sendResponse(false, null, 'Invalid email format');
                }
                
                try {
                    executeQuery(
                        "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)",
                        [$name, $email, $subject, $message]
                    );
                    sendResponse(true, null, 'Message sent successfully');
                } catch (Exception $e) {
                    sendResponse(false, null, 'Failed to send message');
                }
            }
            break;
            
        case 'settings':
            if ($method === 'GET') {
                $settings = fetchOne("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
                if ($settings) {
                    sendResponse(true, $settings);
                } else {
                    // Return default settings
                    $defaultSettings = [
                        'theme_color' => '#00ff00',
                        'layout' => json_encode([
                            'header_style' => 'console',
                            'animation_speed' => 'medium',
                            'show_terminal' => true,
                            'typewriter_effect' => true
                        ])
                    ];
                    sendResponse(true, $defaultSettings);
                }
            }
            break;
            
        case 'stats':
            if ($method === 'GET') {
                $stats = [];
                
                // Count articles
                $articleCount = fetchOne("SELECT COUNT(*) as count FROM articles");
                $stats['articles'] = $articleCount['count'] ?? 0;
                
                // Count projects
                $projectCount = fetchOne("SELECT COUNT(*) as count FROM projects");
                $stats['projects'] = $projectCount['count'] ?? 0;
                
                // Count contact messages
                $messageCount = fetchOne("SELECT COUNT(*) as count FROM contact_messages");
                $stats['messages'] = $messageCount['count'] ?? 0;
                
                // Recent articles
                $recentArticles = fetchAll("SELECT title, created_at FROM articles ORDER BY created_at DESC LIMIT 5");
                $stats['recent_articles'] = $recentArticles;
                
                sendResponse(true, $stats);
            }
            break;
            
        default:
            sendResponse(false, null, 'Invalid endpoint');
    }
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    sendResponse(false, null, 'Internal server error');
}

// Handle direct POST requests (for contact form)
if ($method === 'POST' && empty($endpoint)) {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        sendResponse(false, null, 'All fields are required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, null, 'Invalid email format');
    }
    
    try {
        executeQuery(
            "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)",
            [$name, $email, $subject, $message]
        );
        sendResponse(true, null, 'Message sent successfully');
    } catch (Exception $e) {
        sendResponse(false, null, 'Failed to send message');
    }
}

?>