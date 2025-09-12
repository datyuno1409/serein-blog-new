<?php
require_once '../config/database.php';
require_once 'includes/auth.php';
require_once '../models/Article.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$articleModel = new Article();
$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$editId = $_GET['edit'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AdminAuth::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        try {
            $db = getDB();
            
            if (isset($_POST['delete_id'])) {
                // Delete article
                $deleteId = (int)$_POST['delete_id'];
                $stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
                $stmt->execute([$deleteId]);
                $message = 'Article deleted successfully!';
                AdminAuth::logActivity('article_delete', "Deleted article ID: $deleteId");
                
            } else {
                // Add or update article
                $title = trim($_POST['title'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $category = trim($_POST['category'] ?? '');
                $tags = trim($_POST['tags'] ?? '');
                $status = $_POST['status'] ?? 'draft';
                $featured = isset($_POST['featured']) ? 1 : 0;
                
                if (empty($title) || empty($content)) {
                    $error = 'Title and content are required';
                } else {
                    // Auto-generate slug if empty
                    if (empty($slug)) {
                        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
                    }
                    
                    // Ensure slug is unique
                    $originalSlug = $slug;
                    $counter = 1;
                    while (true) {
                        $checkStmt = $db->prepare("SELECT id FROM articles WHERE slug = ?" . ($editId ? " AND id != ?" : ""));
                        $params = [$slug];
                        if ($editId) $params[] = $editId;
                        $checkStmt->execute($params);
                        
                        if (!$checkStmt->fetch()) break;
                        
                        $slug = $originalSlug . '-' . $counter;
                        $counter++;
                    }
                    
                    if ($editId) {
                        // Update existing article
                        $stmt = $db->prepare("
                            UPDATE articles 
                            SET title = ?, slug = ?, content = ?, category = ?, tags = ?, status = ?, featured = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$title, $slug, $content, $category, $tags, $status, $featured, $editId]);
                        $message = 'Article updated successfully!';
                        AdminAuth::logActivity('article_update', "Updated article: $title");
                    } else {
                        // Insert new article
                        $stmt = $db->prepare("
                            INSERT INTO articles (title, slug, content, category, tags, status, featured, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                        ");
                        $stmt->execute([$title, $slug, $content, $category, $tags, $status, $featured]);
                        $message = 'Article created successfully!';
                        AdminAuth::logActivity('article_create', "Created article: $title");
                    }
                    
                    // Redirect to list after successful save
                    if (empty($error)) {
                        header('Location: articles.php?msg=' . urlencode($message));
                        exit;
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle URL message
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Load data based on action
if ($action === 'edit' || $editId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$editId]);
        $article = $stmt->fetch();
        
        if (!$article) {
            $error = 'Article not found';
            $action = 'list';
        }
    } catch (Exception $e) {
        $error = 'Failed to load article: ' . $e->getMessage();
        $action = 'list';
    }
}

if ($action === 'list') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        $articles = $db->query("
            SELECT id, title, slug, category, status, featured, created_at, updated_at 
            FROM articles 
            ORDER BY created_at DESC
        ")->fetchAll();
    } catch (Exception $e) {
        $error = 'Failed to load articles: ' . $e->getMessage();
        $articles = [];
    }
}

?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Articles Management</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Articles</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <section class="content">
            <div class="container-fluid">
                <div id="alert-container"></div>
        
            <!-- Articles Management -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Articles</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addArticleModal">
                            <i class="fas fa-plus"></i> Add New Article
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($articles)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No articles found. Click "Add New Article" to create your first article.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="articlesTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Slug</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($articles as $article): ?>
                                        <tr id="article-<?php echo $article['id']; ?>">
                                            <td><?php echo $article['id']; ?></td>
                                            <td><?php echo htmlspecialchars($article['title']); ?></td>
                                            <td>
                                                <code><?php echo htmlspecialchars($article['slug']); ?></code>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $article['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($article['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($article['created_at'])); ?></td>
                                            <td>
                                                <?php if ($article['updated_at']): ?>
                                                    <?php echo date('M j, Y', strtotime($article['updated_at'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Never</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-info edit-article" 
                                                            data-id="<?php echo $article['id']; ?>" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-<?php echo $article['status'] === 'published' ? 'warning' : 'success'; ?> toggle-status" 
                                                            data-id="<?php echo $article['id']; ?>" title="Toggle Status">
                                                        <i class="fas fa-<?php echo $article['status'] === 'published' ? 'eye-slash' : 'eye'; ?>"></i>
                                                    </button>
                                                    <a href="../article.php?slug=<?php echo $article['slug']; ?>" 
                                                       class="btn btn-sm btn-success" title="View" target="_blank">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger delete-article" 
                                                            data-id="<?php echo $article['id']; ?>" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
    </div>
</section>

<!-- Add Article Modal -->
<div class="modal fade" id="addArticleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add New Article</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addArticleForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="add_title">Title *</label>
                        <input type="text" class="form-control" id="add_title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_excerpt">Excerpt</label>
                        <textarea class="form-control" id="add_excerpt" name="excerpt" rows="3" placeholder="Brief description of the article"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_status">Status</label>
                        <select class="form-control" id="add_status" name="status">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_content">Content *</label>
                        <textarea class="form-control summernote" id="add_content" name="content" rows="10" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Article</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Article Modal -->
<div class="modal fade" id="editArticleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Article</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editArticleForm">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_title">Title *</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_excerpt">Excerpt</label>
                        <textarea class="form-control" id="edit_excerpt" name="excerpt" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select class="form-control" id="edit_status" name="status">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_content">Content *</label>
                        <textarea class="form-control summernote" id="edit_content" name="content" rows="10" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Article</button>
                </div>
            </form>
        </div>
    </div>
</div>
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
                <p>Are you sure you want to delete the article "<span id="deleteTitle"></span>"?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?= md5(session_id() . time()) ?>">
                    <input type="hidden" name="delete_id" id="deleteId">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Article</button>
                </form>
            </div>
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

function showAlert(type, message) {
    const icon = type === 'success' ? 'success' : 'error';
    Swal.fire({
        title: type === 'success' ? 'Thành công!' : 'Lỗi!',
        text: message,
        icon: icon,
        confirmButtonText: 'OK'
    });
}

$(document).ready(function() {
    // Initialize DataTables
    $('#articlesTable').DataTable({
        responsive: true,
        pageLength: 25,
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
    
    // Initialize Summernote
    $('.summernote').summernote({
        height: 300,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });
    
    // Add Article Form Submit
    $('#addArticleForm').on('submit', function(e) {
        e.preventDefault();
        
        showLoading();
        
        var formData = {
            'title': $('#add_title').val(),
            'excerpt': $('#add_excerpt').val(),
            'status': $('#add_status').val(),
            'content': $('#add_content').summernote('code'),
            'csrf_token': '<?= AdminAuth::generateCSRFToken() ?>'
        };
        
        $.ajax({
            url: 'articles.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                hideLoading();
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: 'Bài viết đã được thêm thành công!',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    $('#addArticleModal').modal('hide');
                    $('#addArticleForm')[0].reset();
                    $('#add_content').summernote('reset');
                    location.reload();
                });
            },
            error: function(xhr) {
                hideLoading();
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi thêm bài viết.'
                });
            }
        });
    });
    
    // Edit Article Button Click
    $('.edit-article').on('click', function() {
        var articleId = $(this).data('id');
        
        $.ajax({
            url: 'articles.php',
            type: 'POST',
            data: {
                action: 'get_article',
                id: articleId
            },
            success: function(response) {
                if (response.success) {
                    var article = response.article;
                    $('#edit_id').val(article.id);
                    $('#edit_title').val(article.title);
                    $('#edit_excerpt').val(article.excerpt || '');
                    $('#edit_status').val(article.status);
                    $('#edit_content').summernote('code', article.content);
                    $('#editArticleModal').modal('show');
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while loading the article.');
            }
        });
    });
    

    
    // Delete Article Button Click
    $('.delete-article').on('click', function() {
        var articleId = $(this).data('id');
        var articleTitle = $(this).closest('tr').find('td:nth-child(2)').text();
        
        Swal.fire({
            title: 'Xác nhận xóa',
            text: `Bạn có chắc chắn muốn xóa bài viết "${articleTitle}"?`,
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
                    url: 'articles.php',
                    type: 'POST',
                    data: {
                        'delete_id': articleId,
                        'csrf_token': '<?= AdminAuth::generateCSRFToken() ?>'
                    },
                    success: function(response) {
                        hideLoading();
                        Swal.fire({
                            icon: 'success',
                            title: 'Đã xóa!',
                            text: 'Bài viết đã được xóa thành công.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            $('#article-' + articleId).fadeOut(500, function() {
                                $(this).remove();
                            });
                        });
                    },
                    error: function(xhr) {
                        hideLoading();
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: 'Có lỗi xảy ra khi xóa bài viết.'
                        });
                    }
                });
            }
        });
    });
    
    // Toggle Status Button Click
    $('.toggle-status').on('click', function() {
        var articleId = $(this).data('id');
        var button = $(this);
        
        Swal.fire({
            title: 'Xác nhận thay đổi',
            text: 'Bạn có chắc chắn muốn thay đổi trạng thái bài viết này?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading();
                
                $.ajax({
                    url: 'articles.php',
                    type: 'POST',
                    data: {
                        action: 'toggle_status',
                        id: articleId
                    },
                    success: function(response) {
                        hideLoading();
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công!',
                                text: 'Trạng thái bài viết đã được cập nhật.',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi!',
                                text: response.message || 'Có lỗi xảy ra khi cập nhật trạng thái.'
                            });
                        }
                    },
                    error: function() {
                        hideLoading();
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: 'Có lỗi xảy ra khi cập nhật trạng thái.'
                        });
                    }
                });
            }
        });
    });
    
    // Edit Article Form Submit
    $('#editArticleForm').on('submit', function(e) {
        e.preventDefault();
        
        showLoading();
        
        var formData = {
            'title': $('#edit_title').val(),
            'excerpt': $('#edit_excerpt').val(),
            'status': $('#edit_status').val(),
            'content': $('#edit_content').summernote('code'),
            'edit': $('#edit_id').val(),
            'csrf_token': '<?= AdminAuth::generateCSRFToken() ?>'
        };
        
        $.ajax({
            url: 'articles.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                hideLoading();
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: 'Bài viết đã được cập nhật thành công!',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    $('#editArticleModal').modal('hide');
                    location.reload();
                });
            },
            error: function(xhr) {
                hideLoading();
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi cập nhật bài viết.'
                });
            }
        });
    });
    

});
</script>

        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>