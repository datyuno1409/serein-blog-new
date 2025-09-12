<?php
require_once 'includes/auth.php';
require_once '../models/Project.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once '../config/CSRF.php';
require_once '../config/RateLimit.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$projectModel = new Project();
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
                $errors = $projectModel->validate($_POST);
                if (empty($errors)) {
                    $sanitizedData = $projectModel->sanitizeData($_POST);
                    $result = $projectModel->create($sanitizedData);
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Project created successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to create project']);
                    }
                } else {
                    echo json_encode(['success' => false, 'errors' => $errors]);
                }
                break;
                
            case 'update':
                $id = $_POST['id'] ?? null;
                if ($id) {
                    $errors = $projectModel->validate($_POST, true);
                    if (empty($errors)) {
                        $sanitizedData = $projectModel->sanitizeData($_POST);
                        $result = $projectModel->update($id, $sanitizedData);
                        if ($result) {
                            echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to update project']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'errors' => $errors]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Project ID is required']);
                }
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Invalid project ID');
                }
                
                $result = $projectModel->delete($id);
                echo json_encode(['success' => $result, 'message' => 'Project deleted successfully']);
                break;
                
            case 'get':
                $id = (int)($_POST['id'] ?? 0);
                $project = $projectModel->find($id);
                if (!$project) {
                    throw new Exception('Project not found');
                }
                echo json_encode(['success' => true, 'data' => $project]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Load data
$projects = $projectModel->all();
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Projects Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Projects</li>
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
                            <h3 class="card-title">Projects List</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#projectModal">
                                    <i class="fas fa-plus"></i> Add Project
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="projectsTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Link</th>
                                        <th>Status</th>
                                        <th>Featured</th>
                                        <th>Order</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($project['id']) ?></td>
                                        <td><?= htmlspecialchars($project['title']) ?></td>
                                        <td><?= htmlspecialchars(substr($project['description'], 0, 100)) ?>...</td>
                                        <td><a href="<?= htmlspecialchars($project['link']) ?>" target="_blank">View</a></td>
                                        <td>
                                            <span class="badge badge-<?= $project['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($project['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $project['featured'] ? 'warning' : 'light' ?>">
                                                <?= $project['featured'] ? 'Yes' : 'No' ?>
                                            </span>
                                        </td>
                                        <td><?= $project['order_index'] ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info edit-btn" data-id="<?= $project['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $project['id'] ?>">
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

<!-- Project Modal -->
<div class="modal fade" id="projectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Add Project</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="projectForm">
                <div class="modal-body">
                    <input type="hidden" id="projectId" name="id">
                    <?php echo CSRF::getTokenField(); ?>
                    
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="link">Project Link</label>
                        <input type="url" class="form-control" id="link" name="link">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="featured">Featured</label>
                                <select class="form-control" id="featured" name="featured">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="order_index">Order</label>
                                <input type="number" class="form-control" id="order_index" name="order_index" value="0">
                            </div>
                        </div>
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
    $('#projectsTable').DataTable({
        responsive: true,
        order: [[6, 'asc']]
    });
    
    // Add Project
    $('#projectModal').on('show.bs.modal', function(e) {
        if (!$(e.relatedTarget).hasClass('edit-btn')) {
            $('#modalTitle').text('Add Project');
            $('#projectForm')[0].reset();
            $('#projectId').val('');
        }
    });
    
    // Edit Project
    $('.edit-btn').click(function() {
        const id = $(this).data('id');
        
        $.post('', {
            action: 'get',
            id: id
        }, function(response) {
            if (response.success) {
                const project = response.data;
                $('#modalTitle').text('Edit Project');
                $('#projectId').val(project.id);
                $('#title').val(project.title);
                $('#description').val(project.description);
                $('#link').val(project.link);
                $('#status').val(project.status);
                $('#featured').val(project.featured);
                $('#order_index').val(project.order_index);
                $('#projectModal').modal('show');
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json');
    });
    
    // Save Project
    $('#projectForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const id = $('#projectId').val();
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
    
    // Delete Project
    $('.delete-btn').click(function() {
        if (confirm('Are you sure you want to delete this project?')) {
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