<?php
require_once 'includes/auth.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once '../models/About.php';
require_once '../models/Skill.php';
require_once '../models/SocialLink.php';
require_once '../models/Testimonial.php';
require_once '../config/CSRF.php';
require_once '../config/RateLimit.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$aboutModel = new About();
$skillModel = new Skill();
$socialLinkModel = new SocialLink();
$testimonialModel = new Testimonial();
$message = '';
$messageType = '';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        RateLimit::enforce(RateLimit::getClientIdentifier(), 'api');
        CSRF::validateRequest();
        switch ($_POST['action']) {
            case 'update_content':
                $errors = $aboutModel->validate($_POST);
                if (empty($errors)) {
                    $sanitizedData = $aboutModel->sanitizeData($_POST);
                    $aboutData = $aboutModel->all();
                    if (!empty($aboutData)) {
                        $result = $aboutModel->update($aboutData[0]['id'], $sanitizedData);
                    } else {
                        $result = $aboutModel->create($sanitizedData);
                    }
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Content updated successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update content']);
                    }
                } else {
                    echo json_encode(['success' => false, 'errors' => $errors]);
                }
                break;
                
            case 'add_skill':
                $errors = $skillModel->validate($_POST);
                if (empty($errors)) {
                    $sanitizedData = $skillModel->sanitizeData($_POST);
                    $aboutData = $aboutModel->all();
                    $aboutId = !empty($aboutData) ? $aboutData[0]['id'] : 1;
                    $sanitizedData['about_id'] = $aboutId;
                    $result = $skillModel->create($sanitizedData);
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Skill added successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to add skill']);
                    }
                } else {
                    echo json_encode(['success' => false, 'errors' => $errors]);
                }
                break;
                
            case 'update_skill':
                $id = $_POST['id'] ?? null;
                if ($id) {
                    $errors = $skillModel->validate($_POST, true);
                    if (empty($errors)) {
                        $sanitizedData = $skillModel->sanitizeData($_POST);
                        $result = $skillModel->update($id, $sanitizedData);
                        if ($result) {
                            echo json_encode(['success' => true, 'message' => 'Skill updated successfully']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to update skill']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'errors' => $errors]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Skill ID is required']);
                }
                break;
                
            case 'delete_skill':
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Invalid skill ID');
                }
                $result = $skillModel->delete($id);
                echo json_encode(['success' => $result, 'message' => 'Skill deleted successfully']);
                break;
                
            case 'add_social_link':
                $errors = $socialLinkModel->validate($_POST);
                if (empty($errors)) {
                    $sanitizedData = $socialLinkModel->sanitizeData($_POST);
                    $aboutData = $aboutModel->all();
                    $aboutId = !empty($aboutData) ? $aboutData[0]['id'] : 1;
                    $sanitizedData['about_id'] = $aboutId;
                    $result = $socialLinkModel->create($sanitizedData);
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Social link added successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to add social link']);
                    }
                } else {
                    echo json_encode(['success' => false, 'errors' => $errors]);
                }
                break;
                
            case 'update_social_link':
                $id = $_POST['id'] ?? null;
                if ($id) {
                    $errors = $socialLinkModel->validate($_POST, true);
                    if (empty($errors)) {
                        $sanitizedData = $socialLinkModel->sanitizeData($_POST);
                        $result = $socialLinkModel->update($id, $sanitizedData);
                        if ($result) {
                            echo json_encode(['success' => true, 'message' => 'Social link updated successfully']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to update social link']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'errors' => $errors]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Social link ID is required']);
                }
                break;
                
            case 'delete_social_link':
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Invalid social link ID');
                }
                $result = $socialLinkModel->delete($id);
                echo json_encode(['success' => $result, 'message' => 'Social link deleted successfully']);
                break;
                
            case 'add_testimonial':
                $errors = $testimonialModel->validate($_POST);
                if (empty($errors)) {
                    $sanitizedData = $testimonialModel->sanitizeData($_POST);
                    $aboutData = $aboutModel->all();
                    $aboutId = !empty($aboutData) ? $aboutData[0]['id'] : 1;
                    $sanitizedData['about_id'] = $aboutId;
                    $result = $testimonialModel->create($sanitizedData);
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Testimonial added successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to add testimonial']);
                    }
                } else {
                    echo json_encode(['success' => false, 'errors' => $errors]);
                }
                break;
                
            case 'update_testimonial':
                $id = $_POST['id'] ?? null;
                if ($id) {
                    $errors = $testimonialModel->validate($_POST, true);
                    if (empty($errors)) {
                        $sanitizedData = $testimonialModel->sanitizeData($_POST);
                        $result = $testimonialModel->update($id, $sanitizedData);
                        if ($result) {
                            echo json_encode(['success' => true, 'message' => 'Testimonial updated successfully']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to update testimonial']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'errors' => $errors]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Testimonial ID is required']);
                }
                break;
                
            case 'delete_testimonial':
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Invalid testimonial ID');
                }
                $result = $testimonialModel->delete($id);
                echo json_encode(['success' => $result, 'message' => 'Testimonial deleted successfully']);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Load data
$aboutData = $aboutModel->all();
$aboutContent = !empty($aboutData) ? $aboutData[0]['content'] : '';
$skills = $skillModel->all();
$socialLinks = $socialLinkModel->all();
$testimonials = $testimonialModel->all();

?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>About Page Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">About</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div id="alert-container"></div>
        
        <div class="row">
            <!-- About Content Section -->
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">About Content</h3>
                    </div>
                    <div class="card-body">
                        <form id="contentForm">
                            <div class="form-group">
                                <label for="content">About Content</label>
                                <textarea class="form-control" id="content" name="content" rows="10" placeholder="Enter about content..."><?php echo htmlspecialchars($aboutContent['content'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Content</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Skills Section -->
            <div class="col-md-6">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Skills Management</h3>
                    </div>
                    <div class="card-body">
                        <form id="skillForm" class="mb-3">
                            <div class="row">
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="skillName" name="name" placeholder="Skill name" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" id="skillLevel" name="level" placeholder="Level %" min="0" max="100" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-info btn-block">Add</button>
                                </div>
                            </div>
                        </form>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="skillsTable">
                                <thead>
                                    <tr>
                                        <th>Skill Name</th>
                                        <th>Level (%)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($skills as $skill): ?>
                                    <tr data-id="<?php echo $skill['id']; ?>">
                                        <td class="skill-name"><?php echo htmlspecialchars($skill['name']); ?></td>
                                        <td class="skill-level"><?php echo $skill['level']; ?>%</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning edit-skill" data-id="<?php echo $skill['id']; ?>">Edit</button>
                                            <button class="btn btn-sm btn-danger delete-skill" data-id="<?php echo $skill['id']; ?>">Delete</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Social Links Section -->
            <div class="col-md-6">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Social Links Management</h3>
                    </div>
                    <div class="card-body">
                        <form id="socialForm" class="mb-3">
                            <div class="row">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" id="socialPlatform" name="platform" placeholder="Platform" required>
                                </div>
                                <div class="col-md-5">
                                    <input type="url" class="form-control" id="socialUrl" name="url" placeholder="URL" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success btn-block">Add</button>
                                </div>
                            </div>
                        </form>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="socialTable">
                                <thead>
                                    <tr>
                                        <th>Platform</th>
                                        <th>URL</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($socialLinks as $link): ?>
                                    <tr data-id="<?php echo $link['id']; ?>">
                                        <td class="social-platform"><?php echo htmlspecialchars($link['platform']); ?></td>
                                        <td class="social-url"><a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"><?php echo htmlspecialchars($link['url']); ?></a></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning edit-social" data-id="<?php echo $link['id']; ?>">Edit</button>
                                            <button class="btn btn-sm btn-danger delete-social" data-id="<?php echo $link['id']; ?>">Delete</button>
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
        
        <div class="row">
            <!-- Testimonials Section -->
            <div class="col-md-12">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Testimonials Management</h3>
                    </div>
                    <div class="card-body">
                        <form id="testimonialForm" class="mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" class="form-control" id="testimonialName" name="name" placeholder="Client Name" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" id="testimonialCompany" name="company" placeholder="Company">
                                </div>
                                <div class="col-md-4">
                                    <textarea class="form-control" id="testimonialText" name="text" placeholder="Testimonial text" rows="2" required></textarea>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-warning btn-block">Add</button>
                                </div>
                            </div>
                        </form>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="testimonialsTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Company</th>
                                        <th>Testimonial</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($testimonials as $testimonial): ?>
                                    <tr data-id="<?php echo $testimonial['id']; ?>">
                                        <td class="testimonial-name"><?php echo htmlspecialchars($testimonial['name']); ?></td>
                                        <td class="testimonial-company"><?php echo htmlspecialchars($testimonial['company']); ?></td>
                                        <td class="testimonial-text"><?php echo htmlspecialchars(substr($testimonial['text'], 0, 100)) . (strlen($testimonial['text']) > 100 ? '...' : ''); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning edit-testimonial" data-id="<?php echo $testimonial['id']; ?>">Edit</button>
                                            <button class="btn btn-sm btn-danger delete-testimonial" data-id="<?php echo $testimonial['id']; ?>">Delete</button>
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
         </div>
     </section>
 </div>

<!-- Edit Modals -->
<!-- Edit Skill Modal -->
<div class="modal fade" id="editSkillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Skill</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editSkillForm">
                <div class="modal-body">
                    <input type="hidden" id="editSkillId">
                    <div class="form-group">
                        <label>Skill Name</label>
                        <input type="text" class="form-control" id="editSkillName" required>
                    </div>
                    <div class="form-group">
                        <label>Level (%)</label>
                        <input type="number" class="form-control" id="editSkillLevel" min="0" max="100" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Social Link Modal -->
<div class="modal fade" id="editSocialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Social Link</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editSocialForm">
                <div class="modal-body">
                    <input type="hidden" id="editSocialId">
                    <div class="form-group">
                        <label>Platform</label>
                        <input type="text" class="form-control" id="editSocialPlatform" required>
                    </div>
                    <div class="form-group">
                        <label>URL</label>
                        <input type="url" class="form-control" id="editSocialUrl" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Testimonial Modal -->
<div class="modal fade" id="editTestimonialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Testimonial</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editTestimonialForm">
                <div class="modal-body">
                    <input type="hidden" id="editTestimonialId">
                    <div class="form-group">
                        <label>Client Name</label>
                        <input type="text" class="form-control" id="editTestimonialName" required>
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" class="form-control" id="editTestimonialCompany">
                    </div>
                    <div class="form-group">
                        <label>Testimonial Text</label>
                        <textarea class="form-control" id="editTestimonialText" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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

function showAlert(message, type) {
    const icon = type === 'success' ? 'success' : 'error';
    Swal.fire({
        title: type === 'success' ? 'Thành công!' : 'Lỗi!',
        text: message,
        icon: icon,
        confirmButtonText: 'OK'
    });
}

$(document).ready(function() {
    // Initialize DataTables for all tables
    $('#skillsTable, #socialTable, #testimonialsTable').DataTable({
        responsive: true,
        pageLength: 10,
        language: {
            search: "Tìm kiếm:",
            lengthMenu: "Hiển thị _MENU_ mục",
            info: "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
            paginate: {
                first: "Đầu",
                last: "Cuối",
                next: "Tiếp",
                previous: "Trước"
            }
        }
    });

    // Initialize CKEditor for content textarea
    if (typeof CKEDITOR !== 'undefined') {
        CKEDITOR.replace('content', {
            height: 300,
            toolbar: [
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent'] },
                { name: 'links', items: ['Link', 'Unlink'] },
                { name: 'insert', items: ['Image', 'Table'] },
                { name: 'styles', items: ['Format'] },
                { name: 'tools', items: ['Maximize'] }
            ]
        });
    }

    // Content Form
    $('#contentForm').on('submit', function(e) {
        e.preventDefault();
        showLoading();
        
        let content = $('#content').val();
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.content) {
            content = CKEDITOR.instances.content.getData();
        }
        
        $.post('about.php', {
            action: 'update_content',
            content: content
        }, function(response) {
            hideLoading();
            if (response.success) {
                showAlert(response.message, 'success');
            } else {
                showAlert(response.message, 'error');
            }
        }, 'json').fail(function() {
            hideLoading();
            showAlert('Có lỗi xảy ra khi cập nhật nội dung', 'error');
        });
    });

    // Skills Management
    $('#skillForm').on('submit', function(e) {
        e.preventDefault();
        showLoading();
        $.post('about.php', {
            action: 'add_skill',
            name: $('#skillName').val(),
            level: $('#skillLevel').val()
        }, function(response) {
            hideLoading();
            if (response.success) {
                showAlert(response.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(response.message, 'error');
            }
        }, 'json').fail(function() {
            hideLoading();
            showAlert('Có lỗi xảy ra khi thêm kỹ năng', 'error');
        });
    });

    // Edit Skill
    $('.edit-skill').on('click', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        const name = row.find('.skill-name').text();
        const level = row.find('.skill-level').text().replace('%', '');
        
        $('#editSkillId').val(id);
        $('#editSkillName').val(name);
        $('#editSkillLevel').val(level);
        $('#editSkillModal').modal('show');
    });

    $('#editSkillForm').on('submit', function(e) {
        e.preventDefault();
        showLoading();
        $.post('about.php', {
            action: 'update_skill',
            id: $('#editSkillId').val(),
            name: $('#editSkillName').val(),
            level: $('#editSkillLevel').val()
        }, function(response) {
            hideLoading();
            if (response.success) {
                showAlert(response.message, 'success');
                $('#editSkillModal').modal('hide');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(response.message, 'error');
            }
        }, 'json').fail(function() {
            hideLoading();
            showAlert('Có lỗi xảy ra khi cập nhật kỹ năng', 'error');
        });
    });

    // Delete Skill
    $('.delete-skill').on('click', function() {
        const id = $(this).data('id');
        const skillName = $(this).closest('tr').find('.skill-name').text();
        
        Swal.fire({
            title: 'Xác nhận xóa',
            text: `Bạn có chắc chắn muốn xóa kỹ năng "${skillName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading();
                $.post('about.php', {
                    action: 'delete_skill',
                    id: id
                }, function(response) {
                    hideLoading();
                    if (response.success) {
                        showAlert(response.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(response.message, 'error');
                    }
                }, 'json').fail(function() {
                    hideLoading();
                    showAlert('Có lỗi xảy ra khi xóa kỹ năng', 'error');
                });
            }
        });
    });

    // Social Links Management
    $('#socialForm').on('submit', function(e) {
        e.preventDefault();
        showLoading();
        $.post('about.php', {
            action: 'add_social_link',
            platform: $('#socialPlatform').val(),
            url: $('#socialUrl').val()
        }, function(response) {
            hideLoading();
            if (response.success) {
                showAlert(response.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(response.message, 'error');
            }
        }, 'json').fail(function() {
            hideLoading();
            showAlert('Có lỗi xảy ra khi thêm liên kết', 'error');
        });
    });

    // Edit Social Link
    $('.edit-social').on('click', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        const platform = row.find('.social-platform').text();
        const url = row.find('.social-url a').attr('href');
        
        $('#editSocialId').val(id);
        $('#editSocialPlatform').val(platform);
        $('#editSocialUrl').val(url);
        $('#editSocialModal').modal('show');
    });

    $('#editSocialForm').on('submit', function(e) {
        e.preventDefault();
        showLoading();
        $.post('about.php', {
            action: 'update_social_link',
            id: $('#editSocialId').val(),
            platform: $('#editSocialPlatform').val(),
            url: $('#editSocialUrl').val()
        }, function(response) {
            hideLoading();
            if (response.success) {
                showAlert(response.message, 'success');
                $('#editSocialModal').modal('hide');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(response.message, 'error');
            }
        }, 'json').fail(function() {
            hideLoading();
            showAlert('Có lỗi xảy ra khi cập nhật liên kết', 'error');
        });
    });

    // Delete Social Link
    $('.delete-social').on('click', function() {
        const id = $(this).data('id');
        const platform = $(this).closest('tr').find('.social-platform').text();
        
        Swal.fire({
            title: 'Xác nhận xóa',
            text: `Bạn có chắc chắn muốn xóa liên kết "${platform}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading();
                $.post('about.php', {
                    action: 'delete_social_link',
                    id: id
                }, function(response) {
                    hideLoading();
                    if (response.success) {
                        showAlert(response.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(response.message, 'error');
                    }
                }, 'json').fail(function() {
                    hideLoading();
                    showAlert('Có lỗi xảy ra khi xóa liên kết', 'error');
                });
            }
        });
    });

    // Testimonials Management
    $('#testimonialForm').on('submit', function(e) {
        e.preventDefault();
        showLoading();
        $.post('about.php', {
            action: 'add_testimonial',
            name: $('#testimonialName').val(),
            company: $('#testimonialCompany').val(),
            text: $('#testimonialText').val()
        }, function(response) {
            hideLoading();
            if (response.success) {
                showAlert(response.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(response.message, 'error');
            }
        }, 'json').fail(function() {
            hideLoading();
            showAlert('Có lỗi xảy ra khi thêm đánh giá', 'error');
        });
    });

    // Edit Testimonial
    $('.edit-testimonial').on('click', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        const name = row.find('.testimonial-name').text();
        const company = row.find('.testimonial-company').text();
        
        // Get full text via AJAX or store in data attribute
        $('#editTestimonialId').val(id);
        $('#editTestimonialName').val(name);
        $('#editTestimonialCompany').val(company);
        $('#editTestimonialModal').modal('show');
    });

    $('#editTestimonialForm').on('submit', function(e) {
        e.preventDefault();
        showLoading();
        $.post('about.php', {
            action: 'update_testimonial',
            id: $('#editTestimonialId').val(),
            name: $('#editTestimonialName').val(),
            company: $('#editTestimonialCompany').val(),
            text: $('#editTestimonialText').val()
        }, function(response) {
            hideLoading();
            if (response.success) {
                showAlert(response.message, 'success');
                $('#editTestimonialModal').modal('hide');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(response.message, 'error');
            }
        }, 'json').fail(function() {
            hideLoading();
            showAlert('Có lỗi xảy ra khi cập nhật đánh giá', 'error');
        });
    });

    // Delete Testimonial
    $('.delete-testimonial').on('click', function() {
        const id = $(this).data('id');
        const clientName = $(this).closest('tr').find('.testimonial-name').text();
        
        Swal.fire({
            title: 'Xác nhận xóa',
            text: `Bạn có chắc chắn muốn xóa đánh giá của "${clientName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading();
                $.post('about.php', {
                    action: 'delete_testimonial',
                    id: id
                }, function(response) {
                    hideLoading();
                    if (response.success) {
                        showAlert(response.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(response.message, 'error');
                    }
                }, 'json').fail(function() {
                    hideLoading();
                    showAlert('Có lỗi xảy ra khi xóa đánh giá', 'error');
                });
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>