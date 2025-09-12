<?php
require_once '../config/database.php';
require_once '../models/Project.php';

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
$project = new Project();

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $result = $project->find($_GET['id']);
                if ($result) {
                    echo json_encode(['success' => true, 'data' => $result]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Project not found']);
                }
            } elseif (isset($_GET['featured'])) {
                $result = $project->getFeaturedProjects();
                echo json_encode(['success' => true, 'data' => $result]);
            } elseif (isset($_GET['status'])) {
                $result = $project->getByStatus($_GET['status']);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $offset = ($page - 1) * $limit;
                
                $result = $project->all($limit, $offset);
                $total = $project->count();
                
                echo json_encode([
                    'success' => true, 
                    'data' => $result,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
            }
            break;

        case 'POST':
            $validation = $project->validate($input);
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'errors' => $validation['errors']]);
                break;
            }
            
            $result = $project->create($input);
            if ($result) {
                http_response_code(201);
                echo json_encode(['success' => true, 'data' => $result, 'message' => 'Project created successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to create project']);
            }
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                break;
            }
            
            if (isset($input['order'])) {
                $result = $project->updateOrder($_GET['id'], $input['order']);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Project order updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to update project order']);
                }
                break;
            }
            
            $validation = $project->validate($input);
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'errors' => $validation['errors']]);
                break;
            }
            
            $result = $project->update($_GET['id'], $input);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update project']);
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                break;
            }
            
            $result = $project->delete($_GET['id']);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Project deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete project']);
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