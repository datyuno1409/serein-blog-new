<?php
require_once 'includes/auth.php';
require_once '../models/Setting.php';
require_once '../config/CSRF.php';
require_once '../config/RateLimit.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$settingModel = new Setting();
$message = '';
$messageType = '';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        RateLimit::enforce(RateLimit::getClientIdentifier(), 'api');
        CSRF::validateRequest();
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'key' => trim($_POST['key'] ?? ''),
                    'value' => trim($_POST['value'] ?? ''),
                    'type' => $_POST['type'] ?? 'text',
                    'description' => trim($_POST['description'] ?? '')
                ];
                
                $errors = $settingModel->validate($data);
                if (!empty($errors)) {
                    throw new Exception(implode(', ', $errors));
                }
                
                $sanitizedData = $settingModel->sanitizeData($data);
                $result = $settingModel->create($sanitizedData);
                echo json_encode(['success' => $result, 'message' => 'Setting created successfully']);
                break;
                
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $data = [
                    'key' => trim($_POST['key'] ?? ''),
                    'value' => trim($_POST['value'] ?? ''),
                    'type' => $_POST['type'] ?? 'text',
                    'description' => trim($_POST['description'] ?? '')
                ];
                
                $errors = $settingModel->validate($data);
                if (!empty($errors)) {
                    throw new Exception(implode(', ', $errors));
                }
                
                $sanitizedData = $settingModel->sanitizeData($data);
                $result = $settingModel->update($id, $sanitizedData);
                echo json_encode(['success' => $result, 'message' => 'Setting updated successfully']);
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Invalid setting ID');
                }
                
                $result = $settingModel->delete($id);
                echo json_encode(['success' => $result, 'message' => 'Setting deleted successfully']);
                break;
                
            case 'get':
                $id = (int)($_POST['id'] ?? 0);
                $setting = $settingModel->find($id);
                if (!$setting) {
                    throw new Exception('Setting not found');
                }
                echo json_encode(['success' => true, 'data' => $setting]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Load data
$settings = $settingModel->all();
$settingTypes = ['text', 'textarea', 'number', 'boolean', 'json', 'url', 'email'];
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Settings Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Settings</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Settings List</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#settingModal">
                                    <i class="fas fa-plus"></i> Add Setting
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="settingsTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Key</th>
                                        <th>Value</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($settings as $setting): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($setting['id']) ?></td>
                                        <td><code><?= htmlspecialchars($setting['key']) ?></code></td>
                                        <td>
                                            <?php 
                                            $value = htmlspecialchars($setting['value']);
                                            echo strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?= ucfirst($setting['type']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($setting['description']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info edit-btn" data-id="<?= $setting['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $setting['id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Setting Modal -->
<div class="modal fade" id="settingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Add Setting</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="settingForm">
                <div class="modal-body">
                    <input type="hidden" id="settingId" name="id">
                    <?php echo CSRF::getTokenField(); ?>
                    
                    <div class="form-group">
                        <label for="key">Key *</label>
                        <input type="text" class="form-control" id="key" name="key" required>
                        <small class="form-text text-muted">Use lowercase with underscores (e.g., site_title)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Type *</label>
                        <select class="form-control" id="type" name="type" required>
                            <?php foreach ($settingTypes as $type): ?>
                            <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="value">Value</label>
                        <div id="valueContainer">
                            <input type="text" class="form-control" id="value" name="value">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#settingsTable').DataTable({
        responsive: true,
        order: [[1, 'asc']]
    });
    
    // Handle type change
    $('#type').change(function() {
        const type = $(this).val();
        const container = $('#valueContainer');
        let input = '';
        
        switch(type) {
            case 'textarea':
            case 'json':
                input = '<textarea class="form-control" id="value" name="value" rows="4"></textarea>';
                break;
            case 'boolean':
                input = '<select class="form-control" id="value" name="value"><option value="true">True</option><option value="false">False</option></select>';
                break;
            case 'number':
                input = '<input type="number" class="form-control" id="value" name="value">';
                break;
            case 'url':
                input = '<input type="url" class="form-control" id="value" name="value">';
                break;
            case 'email':
                input = '<input type="email" class="form-control" id="value" name="value">';
                break;
            default:
                input = '<input type="text" class="form-control" id="value" name="value">';
        }
        
        container.html(input);
    });
    
    // Add Setting
    $('#settingModal').on('show.bs.modal', function(e) {
        if (!$(e.relatedTarget).hasClass('edit-btn')) {
            $('#modalTitle').text('Add Setting');
            $('#settingForm')[0].reset();
            $('#settingId').val('');
            $('#type').trigger('change');
        }
    });
    
    // Edit Setting
    $('.edit-btn').click(function() {
        const id = $(this).data('id');
        
        $.post('', {
            action: 'get',
            id: id
        }, function(response) {
            if (response.success) {
                const setting = response.data;
                $('#modalTitle').text('Edit Setting');
                $('#settingId').val(setting.id);
                $('#key').val(setting.key);
                $('#type').val(setting.type).trigger('change');
                setTimeout(function() {
                    $('#value').val(setting.value);
                }, 100);
                $('#description').val(setting.description);
                $('#settingModal').modal('show');
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json');
    });
    
    // Save Setting
    $('#settingForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const id = $('#settingId').val();
        const action = id ? 'update' : 'create';
        
        $.post('', formData + '&action=' + action, function(response) {
            if (response.success) {
                alert(response.message);
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json');
    });
    
    // Delete Setting
    $('.delete-btn').click(function() {
        if (confirm('Are you sure you want to delete this setting?')) {
            const id = $(this).data('id');
            
            $.post('', {
                action: 'delete',
                id: id
            }, function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>