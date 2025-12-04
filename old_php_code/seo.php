<?php
session_start();
require_once 'includes/auth.php';
require_once '../models/SEO.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$seoModel = new SEO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add_seo':
            $errors = $seoModel->validateSEOData($_POST);
            if (empty($errors)) {
                $result = $seoModel->createSEOSetting($_POST);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'SEO setting created successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create SEO setting']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            }
            break;
            
        case 'get_seo':
            $id = $_POST['id'];
            $seo = $seoModel->getSEOByPage($_POST['page_name'] ?? '');
            if (!$seo) {
                $query = "SELECT * FROM seo_settings WHERE id = ?";
                $seo = $seoModel->db->fetchOne($query, [$id]);
            }
            if ($seo) {
                echo json_encode(['success' => true, 'data' => $seo]);
            } else {
                echo json_encode(['success' => false, 'message' => 'SEO setting not found']);
            }
            break;
            
        case 'edit_seo':
            $id = $_POST['id'];
            $errors = $seoModel->validateSEOData($_POST);
            if (empty($errors)) {
                $result = $seoModel->updateSEOSetting($id, $_POST);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'SEO setting updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update SEO setting']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            }
            break;
            
        case 'delete_seo':
            $id = $_POST['id'];
            $result = $seoModel->deleteSEOSetting($id);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'SEO setting deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete SEO setting']);
            }
            break;
    }
    exit;
}

$seoSettings = $seoModel->getAllSEOSettings();
$pagesList = $seoModel->getPagesList();
            
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>
    
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>SEO Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">SEO Management</li>
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
                                <h3 class="card-title">SEO Settings</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addSEOModal">
                                        <i class="fas fa-plus"></i> Add SEO Setting
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="seoTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Page Name</th>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Keywords</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($seoSettings as $seo): ?>
                                        <tr>
                                            <td><?= $seo['id'] ?></td>
                                            <td><span class="badge badge-info"><?= ucfirst($seo['page_name']) ?></span></td>
                                            <td><?= htmlspecialchars(substr($seo['title'], 0, 50)) ?><?= strlen($seo['title']) > 50 ? '...' : '' ?></td>
                                            <td><?= htmlspecialchars(substr($seo['description'], 0, 60)) ?><?= strlen($seo['description']) > 60 ? '...' : '' ?></td>
                                            <td><?= htmlspecialchars(substr($seo['keywords'], 0, 30)) ?><?= strlen($seo['keywords']) > 30 ? '...' : '' ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="editSEO(<?= $seo['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteSEO(<?= $seo['id'] ?>)">
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

<!-- Add SEO Modal -->
<div class="modal fade" id="addSEOModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add SEO Setting</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addSEOForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_page_name">Page Name *</label>
                                <select class="form-control" id="add_page_name" name="page_name" required>
                                    <option value="">Select Page</option>
                                    <?php foreach ($pagesList as $key => $value): ?>
                                    <option value="<?= $key ?>"><?= $value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_title">Title * <small>(Max 60 chars)</small></label>
                                <input type="text" class="form-control" id="add_title" name="title" maxlength="60" required>
                                <small class="text-muted">Characters: <span id="add_title_count">0</span>/60</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add_description">Description * <small>(Max 160 chars)</small></label>
                        <textarea class="form-control" id="add_description" name="description" rows="3" maxlength="160" required></textarea>
                        <small class="text-muted">Characters: <span id="add_desc_count">0</span>/160</small>
                    </div>
                    <div class="form-group">
                        <label for="add_keywords">Keywords <small>(Comma separated)</small></label>
                        <input type="text" class="form-control" id="add_keywords" name="keywords" placeholder="keyword1, keyword2, keyword3">
                    </div>
                    <hr>
                    <h5>Open Graph (Social Media)</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_og_title">OG Title</label>
                                <input type="text" class="form-control" id="add_og_title" name="og_title">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_og_image">OG Image URL</label>
                                <input type="url" class="form-control" id="add_og_image" name="og_image">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add_og_description">OG Description</label>
                        <textarea class="form-control" id="add_og_description" name="og_description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add SEO Setting</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit SEO Modal -->
<div class="modal fade" id="editSEOModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit SEO Setting</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editSEOForm">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_page_name">Page Name *</label>
                                <select class="form-control" id="edit_page_name" name="page_name" required>
                                    <?php foreach ($pagesList as $key => $value): ?>
                                    <option value="<?= $key ?>"><?= $value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_title">Title * <small>(Max 60 chars)</small></label>
                                <input type="text" class="form-control" id="edit_title" name="title" maxlength="60" required>
                                <small class="text-muted">Characters: <span id="edit_title_count">0</span>/60</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description * <small>(Max 160 chars)</small></label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" maxlength="160" required></textarea>
                        <small class="text-muted">Characters: <span id="edit_desc_count">0</span>/160</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_keywords">Keywords <small>(Comma separated)</small></label>
                        <input type="text" class="form-control" id="edit_keywords" name="keywords" placeholder="keyword1, keyword2, keyword3">
                    </div>
                    <hr>
                    <h5>Open Graph (Social Media)</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_og_title">OG Title</label>
                                <input type="text" class="form-control" id="edit_og_title" name="og_title">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_og_image">OG Image URL</label>
                                <input type="url" class="form-control" id="edit_og_image" name="og_image">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_og_description">OG Description</label>
                        <textarea class="form-control" id="edit_og_description" name="og_description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update SEO Setting</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    $('#seoTable').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "pageLength": 10
    });
    
    // Character counters
    $('#add_title').on('input', function() {
        $('#add_title_count').text($(this).val().length);
    });
    
    $('#add_description').on('input', function() {
        $('#add_desc_count').text($(this).val().length);
    });
    
    $('#edit_title').on('input', function() {
        $('#edit_title_count').text($(this).val().length);
    });
    
    $('#edit_description').on('input', function() {
        $('#edit_desc_count').text($(this).val().length);
    });
});

// Loading functions
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

// Add SEO Setting
$('#addSEOForm').on('submit', function(e) {
    e.preventDefault();
    
    showLoading();
    $.ajax({
        url: '',
        method: 'POST',
        data: $(this).serialize() + '&action=add_seo',
        dataType: 'json',
        success: function(response) {
            hideLoading();
            if (response.success) {
                Swal.fire('Thành công!', response.message, 'success').then(() => {
                    $('#addSEOModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                });
            } else {
                Swal.fire('Lỗi!', response.message, 'error');
            }
        },
        error: function() {
            hideLoading();
            Swal.fire('Lỗi!', 'Đã xảy ra lỗi khi thêm SEO setting', 'error');
        }
    });
});

// Edit SEO Setting
function editSEO(id) {
    $.ajax({
        url: '',
        method: 'POST',
        data: { action: 'get_seo', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const seo = response.data;
                $('#edit_id').val(seo.id);
                $('#edit_page_name').val(seo.page_name);
                $('#edit_title').val(seo.title);
                $('#edit_description').val(seo.description);
                $('#edit_keywords').val(seo.keywords);
                $('#edit_og_title').val(seo.og_title);
                $('#edit_og_description').val(seo.og_description);
                $('#edit_og_image').val(seo.og_image);
                
                // Update character counters
                $('#edit_title_count').text(seo.title ? seo.title.length : 0);
                $('#edit_desc_count').text(seo.description ? seo.description.length : 0);
                
                $('#editSEOModal').modal('show');
            } else {
                Swal.fire('Lỗi!', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Lỗi!', 'Đã xảy ra lỗi khi tải SEO setting', 'error');
        }
    });
}

$('#editSEOForm').on('submit', function(e) {
    e.preventDefault();
    
    showLoading();
    $.ajax({
        url: '',
        method: 'POST',
        data: $(this).serialize() + '&action=edit_seo',
        dataType: 'json',
        success: function(response) {
            hideLoading();
            if (response.success) {
                Swal.fire('Thành công!', response.message, 'success').then(() => {
                    $('#editSEOModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                });
            } else {
                Swal.fire('Lỗi!', response.message, 'error');
            }
        },
        error: function() {
            hideLoading();
            Swal.fire('Lỗi!', 'Đã xảy ra lỗi khi cập nhật SEO setting', 'error');
        }
    });
});

// Delete SEO Setting
function deleteSEO(id) {
    Swal.fire({
        title: 'Bạn có chắc chắn?',
        text: 'Bạn sẽ không thể hoàn tác hành động này!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Có, xóa nó!',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading();
            $.ajax({
                url: '',
                method: 'POST',
                data: { action: 'delete_seo', id: id },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        Swal.fire('Đã xóa!', response.message, 'success').then(() => {
                            setTimeout(() => location.reload(), 1500);
                        });
                    } else {
                        Swal.fire('Lỗi!', response.message, 'error');
                    }
                },
                error: function() {
                    hideLoading();
                    Swal.fire('Lỗi!', 'Đã xảy ra lỗi khi xóa SEO setting', 'error');
                }
            });
        }
    });
}

// Show alert function
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('.content-wrapper .content').prepend(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>

<?php include 'includes/footer.php'; ?>