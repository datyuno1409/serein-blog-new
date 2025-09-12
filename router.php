<?php
// Router for PHP built-in server to handle clean URLs

$request_uri = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($request_uri);
$path = $parsed_url['path'];

// Remove query string for processing
$clean_path = strtok($path, '?');

// Handle root path
if ($clean_path === '/') {
    $clean_path = '/index';
}

// Handle special routes
if ($clean_path === '/home') {
    $clean_path = '/index';
} elseif ($clean_path === '/portfolio') {
    $clean_path = '/services';
}

// Remove trailing slash
$clean_path = rtrim($clean_path, '/');

// Skip if it's already a file that exists
if (file_exists(__DIR__ . $clean_path)) {
    return false; // Let PHP serve the actual file
}

// Skip admin area and API
if (strpos($clean_path, '/admin/') === 0 || $clean_path === '/api.php') {
    return false;
}

// Handle .html extension redirect (301) - Check original REQUEST_URI
if (preg_match('/\.html(\?.*)?$/', $_SERVER['REQUEST_URI'])) {
    $clean_url = preg_replace('/\.html(\?.*)?$/', '$1', $_SERVER['REQUEST_URI']);
    $redirect_url = preg_replace('/\.html/', '', $path);
    $query_string = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect_url . $query_string);
    exit;
}

// Try to serve .html file for clean URLs
$html_file = __DIR__ . $clean_path . '.html';
if (file_exists($html_file)) {
    // Set proper content type
    header('Content-Type: text/html; charset=UTF-8');
    include $html_file;
    exit;
}

// Handle subdirectory structure if needed
if (preg_match('#^/([^/]+)/([^/]+)$#', $clean_path, $matches)) {
    $subdir_file = __DIR__ . '/' . $matches[1] . '/' . $matches[2] . '.html';
    if (file_exists($subdir_file)) {
        header('Content-Type: text/html; charset=UTF-8');
        include $subdir_file;
        exit;
    }
}

// If no file found, serve custom 404 page
http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
include __DIR__ . '/404.html';
exit;
?>