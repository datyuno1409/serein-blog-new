<?php
require_once 'includes/auth.php';
require_once '../models/SEOSetting.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$seoModel = new SEOSetting();
$message = '';
$messageType = '';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'page' => trim($_POST['page'] ?? ''),
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'keywords' => trim($_POST['keywords'] ?? ''),
                    'og_title' => trim($_POST['og_title'] ?? ''),
                    'og_description' => trim($_POST['og_description'] ?? ''),
                    'og_image' => trim($_POST['og_image'] ?? '')
                ];
                
                $errors = $seoModel->validate($data);
                if (!empty($errors)) {
                    throw new Exception(implode(', ', $errors));
                }
                
                $result = $seoModel->create($data);
                echo json_encode(['success' => $result, 'message' => 'SEO setting created successfully']);
                break;
                
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $data = [
                    'page' => trim($_POST['page'] ?? ''),
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'keywords' => trim($_POST['keywords'] ?? ''),
                    'og_title' => trim($_POST['og_title'] ?? ''),
                    'og_description' => trim($_POST['og_description'] ?? ''),
                    'og_image' => trim($_POST['og_image'] ?? '')
                ];
                
                $errors = $seoModel->validate($data);
                if (!empty($errors)) {
                    throw new Exception(implode(', ', $errors));
                }
                
                $result = $seoModel->update($id, $data);
                echo json_encode(['success' => $result, 'message' => 'SEO setting updated successfully']);
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Invalid SEO setting ID');
                }
                
                $result = $seoModel->delete($id);
                echo json_encode(['success' => $result, 'message' => 'SEO setting deleted successfully']);
                break;
                
            case 'get':
                $id = (int)($_POST['id'] ?? 0);
                $seoSetting = $seoModel->find($id);
                if (!$seoSetting) {
                    throw new Exception('SEO setting not found');
                }
                echo json_encode(['success' => true, 'data' => $seoSetting]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Load data
$seoSettings = $seoModel->all();
$pages = ['home', 'about', 'projects', 'contact', 'blog'];
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>SEO Settings Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">SEO Settings</li>
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
                            <h3 class="card-title">SEO Settings List</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#seoModal">
                                    <i class="fas fa-plus"></i> Add SEO Setting
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="seoTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Page</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Keywords</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($seoSettings as $seo): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($seo['id']) ?></td>
                                        <td>
                                            <span class="badge badge-primary"><?= ucfirst($seo['page']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars(substr($seo['title'], 0, 50)) ?>...</td>
                                        <td><?= htmlspecialchars(substr($seo['description'], 0, 80)) ?>...</td>
                                        <td><?= htmlspecialchars(substr($seo['keywords'], 0, 50)) ?>...</td>
                                        <td>
                                            <button class="btn btn-sm btn-info edit-btn" data-id="<?= $seo['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $seo['id'] ?>">
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

<!-- SEO Modal -->
<div class="modal fade" id="seoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Add SEO Setting</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="seoForm">
                <div class="modal-body">
                    <input type="hidden" id="seoId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="page">Page *</label>
                                <select class="form-control" id="page" name="page" required>
                                    <option value="">Select Page</option>
                                    <?php foreach ($pages as $page): ?>
                                    <option value="<?= $page ?>"><?= ucfirst($page) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="title">Meta Title *</label>
                                <input type="text" class="form-control" id="title" name="title" required maxlength="60">
                                <small class="form-text text-muted">Recommended: 50-60 characters</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Meta Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required maxlength="160"></textarea>
                        <small class="form-text text-muted">Recommended: 150-160 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="keywords">Keywords</label>
                        <input type="text" class="form-control" id="keywords" name="keywords">
                        <small class="form-text text-muted">Separate keywords with commas</small>
                    </div>
                    
                    <hr>
                    <h5>Open Graph Settings</h5>
                    
                    <div class="form-group">
                        <label for="og_title">OG Title</label>
                        <input type="text" class="form-control" id="og_title" name="og_title" maxlength="60">
                        <small class="form-text text-muted">Leave empty to use Meta Title</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="og_description">OG Description</label>
                        <textarea class="form-control" id="og_description" name="og_description" rows="2" maxlength="160"></textarea>
                        <small class="form-text text-muted">Leave empty to use Meta Description</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="og_image">OG Image URL</label>
                        <input type="url" class="form-control" id="og_image" name="og_image">
                        <small class="form-text text-muted">Recommended size: 1200x630px</small>
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
    $('#seoTable').DataTable({
        responsive: true,
        order: [[1, 'asc']]
    });
    
    // Character counters
    $('#title, #og_title').on('input', function() {
        const maxLength = 60;
        const currentLength = $(this).val().length;
        const color = currentLength > maxLength ? 'text-danger' : (currentLength > 50 ? 'text-warning' : 'text-success');
        $(this).next('.form-text').removeClass('text-muted text-success text-warning text-danger').addClass(color)
            .text(`${currentLength}/${maxLength} characters`);
    });
    
    $('#description, #og_description').on('input', function() {
        const maxLength = 160;
        const currentLength = $(this).val().length;
        const color = currentLength > maxLength ? 'text-danger' : (currentLength > 150 ? 'text-warning' : 'text-success');
        $(this).next('.form-text').removeClass('text-muted text-success text-warning text-danger').addClass(color)
            .text(`${currentLength}/${maxLength} characters`);
    });
    
    // Add SEO Setting
    $('#seoModal').on('show.bs.modal', function(e) {
        if (!$(e.relatedTarget).hasClass('edit-btn')) {
            $('#modalTitle').text('Add SEO Setting');
            $('#seoForm')[0].reset();
            $('#seoId').val('');
        }
    });
    
    // Edit SEO Setting
    $('.edit-btn').click(function() {
        const id = $(this).data('id');
        
        $.post('', {
            action: 'get',
            id: id
        }, function(response) {
            if (response.success) {
                const seo = response.data;
                $('#modalTitle').text('Edit SEO Setting');
                $('#seoId').val(seo.id);
                $('#page').val(seo.page);
                $('#title').val(seo.title).trigger('input');
                $('#description').val(seo.description).trigger('input');
                $('#keywords').val(seo.keywords);
                $('#og_title').val(seo.og_title).trigger('input');
                $('#og_description').val(seo.og_description).trigger('input');
                $('#og_image').val(seo.og_image);
                $('#seoModal').modal('show');
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json');
    });
    
    // Save SEO Setting
    $('#seoForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const id = $('#seoId').val();
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
    
    // Delete SEO Setting
    $('.delete-btn').click(function() {
        if (confirm('Are you sure you want to delete this SEO setting?')) {
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