<?php
session_start();
require_once '../config/database.php';
require_once '../models/Settings.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$settings = new Settings($database);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_setting':
                $validation = $settings->validateSettingData($_POST);
                if ($validation === true) {
                    $result = $settings->createSetting($_POST);
                    echo json_encode(['success' => $result, 'message' => $result ? 'Setting created successfully' : 'Failed to create setting']);
                } else {
                    echo json_encode(['success' => false, 'message' => implode(', ', $validation)]);
                }
                break;
                
            case 'get_setting':
                $setting = $settings->getSettingByKey($_POST['key']);
                echo json_encode(['success' => true, 'data' => $setting]);
                break;
                
            case 'edit_setting':
                $result = $settings->updateSetting($_POST['setting_key'], $_POST['setting_value']);
                echo json_encode(['success' => $result, 'message' => $result ? 'Setting updated successfully' : 'Failed to update setting']);
                break;
                
            case 'delete_setting':
                $result = $settings->deleteSetting($_POST['key']);
                echo json_encode(['success' => $result, 'message' => $result ? 'Setting deleted successfully' : 'Failed to delete setting']);
                break;
                
            case 'update_sortable':
                $result = $settings->updateSortableOrder($_POST['setting_key'], json_decode($_POST['order'], true));
                echo json_encode(['success' => $result, 'message' => $result ? 'Order updated successfully' : 'Failed to update order']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$allSettings = $settings->getAllSettings();
$colorSettings = $settings->getColorSettings();
$sortableSettings = $settings->getSortableSettings();
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

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
                            <h3 class="card-title">Settings Management</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#settingModal">
                                    <i class="fas fa-plus"></i> Add Setting
                                </button>
                            </div>
                        </div>
                        <div class="card-body">

                            <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all" role="tab">
                                        <i class="fas fa-list"></i> All Settings
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="colors-tab" data-toggle="tab" href="#colors" role="tab">
                                        <i class="fas fa-palette"></i> Colors
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="sortable-tab" data-toggle="tab" href="#sortable" role="tab">
                                        <i class="fas fa-sort"></i> Sortable Lists
                                    </a>
                                </li>
                            </ul>

                <div class="tab-content" id="settingsTabContent">
                    <!-- All Settings Tab -->
                    <div class="tab-pane fade show active" id="all" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-body">
                                <table id="settingsTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Key</th>
                                            <th>Value</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allSettings as $setting): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($setting['setting_key']) ?></td>
                                            <td>
                                                <?php if ($setting['setting_type'] === 'color'): ?>
                                                    <div class="color-preview" style="background-color: <?= htmlspecialchars($setting['setting_value']) ?>"></div>
                                                    <?= htmlspecialchars($setting['setting_value']) ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars(strlen($setting['setting_value']) > 50 ? substr($setting['setting_value'], 0, 50) . '...' : $setting['setting_value']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($setting['setting_type']) ?></span></td>
                                            <td><?= htmlspecialchars($setting['description'] ?? '') ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-setting" data-key="<?= htmlspecialchars($setting['setting_key']) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-setting" data-key="<?= htmlspecialchars($setting['setting_key']) ?>">
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

                    <!-- Colors Tab -->
                    <div class="tab-pane fade" id="colors" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5>Color Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($colorSettings as $colorSetting): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title"><?= ucwords(str_replace('_', ' ', $colorSetting['setting_key'])) ?></h6>
                                                <div class="color-input-group">
                                                    <input type="color" 
                                                           class="form-control color-picker" 
                                                           value="<?= htmlspecialchars($colorSetting['setting_value']) ?>"
                                                           data-key="<?= htmlspecialchars($colorSetting['setting_key']) ?>">
                                                    <input type="text" 
                                                           class="form-control mt-2 color-text" 
                                                           value="<?= htmlspecialchars($colorSetting['setting_value']) ?>"
                                                           data-key="<?= htmlspecialchars($colorSetting['setting_key']) ?>">
                                                </div>
                                                <small class="text-muted"><?= htmlspecialchars($colorSetting['description'] ?? '') ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sortable Lists Tab -->
                    <div class="tab-pane fade" id="sortable" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5>Sortable Lists</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($sortableSettings as $sortableSetting): ?>
                                <div class="mb-4">
                                    <h6><?= ucwords(str_replace('_', ' ', $sortableSetting['setting_key'])) ?></h6>
                                    <p class="text-muted"><?= htmlspecialchars($sortableSetting['description'] ?? '') ?></p>
                                    <div class="sortable-list" data-key="<?= htmlspecialchars($sortableSetting['setting_key']) ?>">
                                        <?php 
                                        $items = json_decode($sortableSetting['setting_value'], true);
                                        if ($items && is_array($items)):
                                            foreach ($items as $item): 
                                        ?>
                                        <div class="sortable-item" data-value="<?= htmlspecialchars($item) ?>">
                                            <i class="fas fa-grip-vertical me-2"></i>
                                            <?= ucwords(str_replace('_', ' ', $item)) ?>
                                        </div>
                                        <?php 
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

    <!-- Add/Edit Setting Modal -->
    <div class="modal fade" id="settingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="settingModalTitle">Add Setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="settingForm">
                    <div class="modal-body">
                        <input type="hidden" id="settingAction" name="action" value="add_setting">
                        <input type="hidden" id="originalKey" name="original_key">
                        
                        <div class="mb-3">
                            <label for="settingKey" class="form-label">Setting Key</label>
                            <input type="text" class="form-control" id="settingKey" name="setting_key" required>
                            <div class="form-text">Use lowercase letters, numbers, and underscores only</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="settingValue" class="form-label">Setting Value</label>
                            <textarea class="form-control" id="settingValue" name="setting_value" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="settingType" class="form-label">Setting Type</label>
                            <select class="form-select" id="settingType" name="setting_type" required>
                                <option value="text">Text</option>
                                <option value="color">Color</option>
                                <option value="sortable">Sortable</option>
                                <option value="number">Number</option>
                                <option value="boolean">Boolean</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="settingDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="settingDescription" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Setting</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    function showLoading() {
        Swal.fire({
            title: 'Loading...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading()
            }
        });
    }
    
    function hideLoading() {
        Swal.close();
    }
    
    $(document).ready(function() {
        // Initialize DataTable
        $('#settingsTable').DataTable({
            responsive: true,
            pageLength: 25
        });
        
        // Initialize Sortable lists
        $('.sortable-list').each(function() {
            const key = $(this).data('key');
            new Sortable(this, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    const items = [];
                    $(evt.to).find('.sortable-item').each(function() {
                        items.push($(this).data('value'));
                    });
                    
                    // Update sortable order
                    showLoading();
                    $.post('settings.php', {
                        action: 'update_sortable',
                        setting_key: key,
                        order: JSON.stringify(items)
                    }, function(response) {
                        hideLoading();
                        if (response.success) {
                            showAlert('success', response.message);
                        } else {
                            showAlert('danger', response.message);
                        }
                    }, 'json').fail(function() {
                        hideLoading();
                        showAlert('danger', 'An error occurred while updating sort order.');
                    });
                }
            });
        });
        
        // Color picker change handler
        $('.color-picker').on('change', function() {
            const key = $(this).data('key');
            const value = $(this).val();
            const textInput = $(this).siblings('.color-text');
            
            textInput.val(value);
            updateColorSetting(key, value);
        });
        
        // Color text input change handler
        $('.color-text').on('change', function() {
            const key = $(this).data('key');
            const value = $(this).val();
            const colorInput = $(this).siblings('.color-picker');
            
            if (/^#[0-9A-F]{6}$/i.test(value)) {
                colorInput.val(value);
                updateColorSetting(key, value);
            }
        });
        
        function updateColorSetting(key, value) {
            showLoading();
            $.post('settings.php', {
                action: 'edit_setting',
                setting_key: key,
                setting_value: value
            }, function(response) {
                hideLoading();
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', response.message);
                }
            }, 'json').fail(function() {
                hideLoading();
                showAlert('danger', 'An error occurred while updating color setting.');
            });
        }
        
        // Add setting form submission
        $('#settingForm').on('submit', function(e) {
            e.preventDefault();
            
            showLoading();
            $.post('settings.php', $(this).serialize(), function(response) {
                hideLoading();
                if (response.success) {
                    showAlert('success', response.message);
                    $('#settingModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', response.message);
                }
            }, 'json').fail(function() {
                hideLoading();
                showAlert('danger', 'An error occurred while processing your request.');
            });
        });
        
        // Edit setting
        $('.edit-setting').on('click', function() {
            const key = $(this).data('key');
            
            $.post('settings.php', {
                action: 'get_setting',
                key: key
            }, function(response) {
                if (response.success && response.data) {
                    const setting = response.data;
                    $('#settingModalTitle').text('Edit Setting');
                    $('#settingAction').val('edit_setting');
                    $('#originalKey').val(setting.setting_key);
                    $('#settingKey').val(setting.setting_key);
                    $('#settingValue').val(setting.setting_value);
                    $('#settingType').val(setting.setting_type);
                    $('#settingDescription').val(setting.description || '');
                    $('#settingModal').modal('show');
                }
            }, 'json');
        });
        
        // Delete setting
        $('.delete-setting').on('click', function() {
            const key = $(this).data('key');
            
            Swal.fire({
                title: 'Are you sure?',
                text: 'You won\'t be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    $.post('settings.php', {
                        action: 'delete_setting',
                        key: key
                    }, function(response) {
                        hideLoading();
                        if (response.success) {
                             showAlert('success', response.message);
                             setTimeout(() => location.reload(), 1500);
                         } else {
                             showAlert('danger', response.message);
                         }
                    }, 'json');
                }
            });
        });
        
        // Reset modal when closed
        $('#settingModal').on('hidden.bs.modal', function() {
            $('#settingModalTitle').text('Add Setting');
            $('#settingAction').val('add_setting');
            $('#settingForm')[0].reset();
        });
        
        function showAlert(type, message) {
            const icon = type === 'success' ? 'success' : type === 'danger' ? 'error' : 'info';
            Swal.fire({
                icon: icon,
                title: type === 'success' ? 'Success!' : 'Error!',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        }
    });
     </script>

<?php include 'includes/footer.php'; ?>