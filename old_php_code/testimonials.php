<?php
require_once '../config/database.php';
require_once '../models/Testimonial.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$testimonial = new Testimonial();

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $result = $testimonial->find($_GET['id']);
                if ($result) {
                    echo json_encode(['success' => true, 'data' => $result]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Testimonial not found']);
                }
            } elseif (isset($_GET['about_id'])) {
                $result = $testimonial->getByAboutId($_GET['about_id']);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                $result = $testimonial->all();
                echo json_encode(['success' => true, 'data' => $result]);
            }
            break;

        case 'POST':
            $validation = $testimonial->validate($input);
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'errors' => $validation['errors']]);
                break;
            }
            
            $result = $testimonial->create($input);
            if ($result) {
                http_response_code(201);
                echo json_encode(['success' => true, 'data' => $result, 'message' => 'Testimonial created successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to create testimonial']);
            }
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                break;
            }
            
            if (isset($input['order'])) {
                $result = $testimonial->updateOrder($_GET['id'], $input['order']);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Testimonial order updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to update testimonial order']);
                }
                break;
            }
            
            $validation = $testimonial->validate($input);
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'errors' => $validation['errors']]);
                break;
            }
            
            $result = $testimonial->update($_GET['id'], $input);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Testimonial updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update testimonial']);
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                break;
            }
            
            $result = $testimonial->delete($_GET['id']);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Testimonial deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete testimonial']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>