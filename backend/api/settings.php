<?php
require_once '../config/database.php';
require_once '../models/Setting.php';

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
$setting = new Setting();

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $result = $setting->find($_GET['id']);
                if ($result) {
                    echo json_encode(['success' => true, 'data' => $result]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Setting not found']);
                }
            } elseif (isset($_GET['key'])) {
                $result = $setting->getByKey($_GET['key']);
                if ($result) {
                    echo json_encode(['success' => true, 'data' => $result]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Setting not found']);
                }
            } elseif (isset($_GET['type'])) {
                $result = $setting->getByType($_GET['type']);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                $result = $setting->all();
                echo json_encode(['success' => true, 'data' => $result]);
            }
            break;

        case 'POST':
            if (isset($input['key']) && isset($input['value'])) {
                $result = $setting->setValue($input['key'], $input['value']);
                if ($result) {
                    http_response_code(201);
                    echo json_encode(['success' => true, 'message' => 'Setting saved successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to save setting']);
                }
                break;
            }
            
            $validation = $setting->validate($input);
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'errors' => $validation['errors']]);
                break;
            }
            
            $result = $setting->create($input);
            if ($result) {
                http_response_code(201);
                echo json_encode(['success' => true, 'data' => $result, 'message' => 'Setting created successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to create setting']);
            }
            break;

        case 'PUT':
            if (isset($_GET['key'])) {
                if (!isset($input['value'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Value is required']);
                    break;
                }
                
                $result = $setting->setValue($_GET['key'], $input['value']);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Setting updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to update setting']);
                }
            } elseif (isset($_GET['id'])) {
                $validation = $setting->validate($input);
                if (!$validation['valid']) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'errors' => $validation['errors']]);
                    break;
                }
                
                $result = $setting->update($_GET['id'], $input);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Setting updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to update setting']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID or key parameter is required']);
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                break;
            }
            
            $result = $setting->delete($_GET['id']);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Setting deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete setting']);
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