<?php
require_once 'includes/auth.php';
require_once '../models/Project.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$projectModel = new Project();
$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$editId = $_GET['edit'] ?? null;

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'add_project':
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'link' => trim($_POST['link'] ?? ''),
                'status' => 'active',
                'featured' => 0,
                'order_index' => 0
            ];
            
            $errors = $projectModel->validate($data);
            
            if (empty($errors)) {
                if ($projectModel->create($data)) {
                    echo json_encode(['success' => true, 'message' => 'Project added successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add project.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            }
            exit;
            
        case 'get_project':
            $id = (int)($_POST['id'] ?? 0);
            $project = $projectModel->find($id);
            
            if ($project) {
                echo json_encode(['success' => true, 'project' => $project]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Project not found.']);
            }
            exit;
            
        case 'edit_project':
            $id = (int)($_POST['id'] ?? 0);
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'link' => trim($_POST['link'] ?? '')
            ];
            
            $errors = $projectModel->validate($data);
            
            if (empty($errors)) {
                if ($projectModel->update($id, $data)) {
                    echo json_encode(['success' => true, 'message' => 'Project updated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update project.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            }
            exit;
            
        case 'delete_project':
            $id = (int)($_POST['id'] ?? 0);
            
            if ($projectModel->delete($id)) {
                echo json_encode(['success' => true, 'message' => 'Project deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete project.']);
            }
            exit;
    }
}

// Get all projects for display
$projects = $projectModel->all();
$action = $_GET['action'] ?? 'list';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <?php if ($action === 'add'): ?>
                        Add New Project
                    <?php elseif ($action === 'edit' || $editId): ?>
                        Edit Project
                    <?php else: ?>
                        Projects Management
                    <?php endif; ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Projects</li>
                </ol>
            </div>
        </div>
    </section>

    <section class="content">
    <div class="container-fluid">
        
        <?php if ($message): ?>
            <?= showSuccessMessage($message) ?>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <?= showErrorMessage($error) ?>
        <?php endif; ?>
        
        <?php if ($action === 'list'): ?>
            <!-- Projects List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Projects</h3>
                    <div class="card-tools">
                        <a href="?action=add" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Project
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($projects)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="projectsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Technologies</th>
                                        <th>Status</th>
                                        <th>Featured</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects as $project): ?>
                                        <tr>
                                            <td><?= $project['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($project['title']) ?></strong>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($project['description']) ?>">
                                                    <?= htmlspecialchars(substr($project['description'], 0, 100)) ?><?= strlen($project['description']) > 100 ? '...' : '' ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($project['technologies']): ?>
                                                    <?php $techs = explode(',', $project['technologies']); ?>
                                                    <?php foreach (array_slice($techs, 0, 3) as $tech): ?>
                                                        <span class="badge badge-secondary"><?= htmlspecialchars(trim($tech)) ?></span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($techs) > 3): ?>
                                                        <span class="badge badge-light">+<?= count($techs) - 3 ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No technologies</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($project['status'] === 'completed'): ?>
                                                    <span class="badge badge-success">Completed</span>
                                                <?php elseif ($project['status'] === 'in-progress'): ?>
                                                    <span class="badge badge-warning">In Progress</span>
                                                <?php else: ?>
                                                    <span class="badge badge-info">Planning</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($project['featured']): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($project['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?edit=<?= $project['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($project['link']): ?>
                                                        <a href="<?= htmlspecialchars($project['link']) ?>" class="btn btn-sm btn-outline-info" title="View Live" target="_blank">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($project['github_link']): ?>
                                                        <a href="<?= htmlspecialchars($project['github_link']) ?>" class="btn btn-sm btn-outline-dark" title="GitHub" target="_blank">
                                                            <i class="fab fa-github"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteProject(<?= $project['id'] ?>, '<?= htmlspecialchars($project['title'], ENT_QUOTES) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                            <h4>No Projects Yet</h4>
                            <p class="text-muted">Start by creating your first project.</p>
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create First Project
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Add/Edit Form -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <?= ($action === 'edit' || $editId) ? 'Edit Project' : 'Add New Project' ?>
                            </h3>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= md5(session_id() . time()) ?>">
                            
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="title">Project Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($project['title'] ?? '') ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Description *</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="link">Live Demo URL</label>
                                            <input type="url" class="form-control" id="link" name="link" value="<?= htmlspecialchars($project['link'] ?? '') ?>" placeholder="https://example.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="github_link">GitHub Repository</label>
                                            <input type="url" class="form-control" id="github_link" name="github_link" value="<?= htmlspecialchars($project['github_link'] ?? '') ?>" placeholder="https://github.com/username/repo">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="technologies">Technologies Used</label>
                                    <input type="text" class="form-control" id="technologies" name="technologies" value="<?= htmlspecialchars($project['technologies'] ?? '') ?>" placeholder="PHP, MySQL, JavaScript, HTML, CSS">
                                    <small class="form-text text-muted">Separate multiple technologies with commas</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="image_url">Project Image URL</label>
                                    <input type="url" class="form-control" id="image_url" name="image_url" value="<?= htmlspecialchars($project['image_url'] ?? '') ?>" placeholder="https://example.com/image.jpg">
                                    <small class="form-text text-muted">Optional: URL to project screenshot or logo</small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="status">Project Status</label>
                                            <select class="form-control" id="status" name="status">
                                                <option value="planning" <?= ($project['status'] ?? '') === 'planning' ? 'selected' : '' ?>>Planning</option>
                                                <option value="in-progress" <?= ($project['status'] ?? '') === 'in-progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="completed" <?= ($project['status'] ?? 'completed') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mt-4">
                                                <input type="checkbox" class="custom-control-input" id="featured" name="featured" <?= ($project['featured'] ?? 0) ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="featured">Featured Project</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?= ($action === 'edit' || $editId) ? 'Update Project' : 'Create Project' ?>
                                </button>
                                <a href="projects.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <?php if (($action === 'edit' || $editId) && !empty($project['link'])): ?>
                                    <a href="<?= htmlspecialchars($project['link']) ?>" class="btn btn-outline-info" target="_blank">
                                        <i class="fas fa-external-link-alt"></i> View Live
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Help Card -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Project Guidelines</h3>
                        </div>
                        <div class="card-body">
                            <h6><i class="fas fa-lightbulb"></i> Best Practices:</h6>
                            <ul class="list-unstyled small">
                                <li>• Use clear, descriptive titles</li>
                                <li>• Write detailed descriptions</li>
                                <li>• Include live demo links</li>
                                <li>• Add GitHub repositories</li>
                                <li>• List all technologies used</li>
                            </ul>
                            
                            <h6 class="mt-3"><i class="fas fa-star"></i> Featured Projects:</h6>
                            <ul class="list-unstyled small">
                                <li>• Shown on homepage</li>
                                <li>• Highlighted in portfolio</li>
                                <li>• Should be your best work</li>
                                <li>• Include screenshots</li>
                            </ul>
                            
                            <h6 class="mt-3"><i class="fas fa-code"></i> Technologies:</h6>
                            <p class="small text-muted">Common examples: PHP, MySQL, JavaScript, React, Vue.js, Node.js, Python, HTML5, CSS3, Bootstrap, Laravel, WordPress</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    </section>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirm Delete</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the project "<span id="deleteTitle"></span>"?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?= md5(session_id() . time()) ?>">
                    <input type="hidden" name="delete_id" id="deleteId">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Project</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#projectsTable').DataTable({
        "responsive": true,
        "pageLength": 25,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json"
        }
    });
});

function showLoading() {
    Swal.fire({
        title: 'Đang xử lý...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

function hideLoading() {
    Swal.close();
}

function editProject(id) {
    showLoading();
    
    $.ajax({
        url: 'projects.php',
        type: 'POST',
        data: {
            action: 'get_project',
            id: id,
            csrf_token: $('input[name="csrf_token"]').val()
        },
        success: function(response) {
            hideLoading();
            const data = JSON.parse(response);
            if (data.success) {
                const project = data.project;
                $('#edit_id').val(project.id);
                $('#edit_title').val(project.title);
                $('#edit_description').val(project.description);
                $('#edit_link').val(project.link);
                $('#editProjectModal').modal('show');
            } else {
                Swal.fire('Lỗi!', data.message || 'Không thể tải thông tin dự án', 'error');
            }
        },
        fail: function() {
            hideLoading();
            Swal.fire('Lỗi!', 'Có lỗi xảy ra khi tải thông tin dự án', 'error');
        }
    });
}

function deleteProject(id, title) {
    Swal.fire({
        title: 'Xác nhận xóa',
        text: `Bạn có chắc chắn muốn xóa dự án "${title}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading();
            
            $.ajax({
                url: 'projects.php',
                type: 'POST',
                data: {
                    action: 'delete_project',
                    id: id,
                    csrf_token: $('input[name="csrf_token"]').val()
                },
                success: function(response) {
                    hideLoading();
                    const data = JSON.parse(response);
                    if (data.success) {
                        Swal.fire('Thành công!', 'Dự án đã được xóa', 'success').then(() => {
                            setTimeout(() => location.reload(), 500);
                        });
                    } else {
                        Swal.fire('Lỗi!', data.message || 'Không thể xóa dự án', 'error');
                    }
                },
                fail: function() {
                    hideLoading();
                    Swal.fire('Lỗi!', 'Có lỗi xảy ra khi xóa dự án', 'error');
                }
            });
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>