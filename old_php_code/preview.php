<?php
$currentPage = 'preview';
require_once 'includes/auth.php';
require_once '../config/database.php';
require_once 'includes/header.php';

$db = new Database();
$conn = $db->getConnection();

// Get site settings for preview
$stmt = $conn->prepare("SELECT * FROM settings WHERE setting_type = 'general'");
$stmt->execute();
$generalSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$siteSettings = [];
foreach ($generalSettings as $setting) {
    $siteSettings[$setting['setting_key']] = $setting['setting_value'];
}

// Get color settings
$stmt = $conn->prepare("SELECT * FROM settings WHERE setting_type = 'color'");
$stmt->execute();
$colorSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$colors = [];
foreach ($colorSettings as $setting) {
    $colors[$setting['setting_key']] = $setting['setting_value'];
}

// Get recent articles for preview
$stmt = $conn->prepare("SELECT * FROM articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$recentArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent projects for preview
$stmt = $conn->prepare("SELECT * FROM projects WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$recentProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-eye"></i> Live Preview</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Preview</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        
        <!-- Preview Controls -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-desktop mr-1"></i>
                            Preview Controls
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Device View:</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary active" onclick="setPreviewSize('100%', 'auto')">
                                            <i class="fas fa-desktop"></i> Desktop
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" onclick="setPreviewSize('768px', '1024px')">
                                            <i class="fas fa-tablet-alt"></i> Tablet
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" onclick="setPreviewSize('375px', '667px')">
                                            <i class="fas fa-mobile-alt"></i> Mobile
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Preview URL:</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="previewUrl" value="<?= $_SERVER['HTTP_HOST'] ?>" readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-success" type="button" onclick="openInNewTab()">
                                                <i class="fas fa-external-link-alt"></i> Open
                                            </button>
                                            <button class="btn btn-info" type="button" onclick="refreshPreview()">
                                                <i class="fas fa-sync-alt"></i> Refresh
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Live Preview Frame -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-globe mr-1"></i>
                            Live Website Preview
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-success">Live</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="preview-container" style="text-align: center; background: #f4f4f4; padding: 20px;">
                            <div class="preview-frame" style="margin: 0 auto; transition: all 0.3s ease;">
                                <iframe 
                                    id="previewFrame" 
                                    src="http://<?= $_SERVER['HTTP_HOST'] ?>" 
                                    style="width: 100%; height: 800px; border: 1px solid #ddd; border-radius: 5px; background: white;"
                                    frameborder="0"
                                    scrolling="yes">
                                </iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-newspaper mr-1"></i>
                            Recent Articles
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentArticles)): ?>
                            <ul class="list-unstyled">
                                <?php foreach ($recentArticles as $article): ?>
                                    <li class="mb-2">
                                        <strong><?= htmlspecialchars($article['title']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($article['created_at'])) ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">No articles published yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-project-diagram mr-1"></i>
                            Recent Projects
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentProjects)): ?>
                            <ul class="list-unstyled">
                                <?php foreach ($recentProjects as $project): ?>
                                    <li class="mb-2">
                                        <strong><?= htmlspecialchars($project['title']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($project['created_at'])) ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">No projects published yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-palette mr-1"></i>
                            Current Theme
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($colors)): ?>
                            <div class="color-palette">
                                <?php foreach ($colors as $key => $color): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <div style="width: 20px; height: 20px; background-color: <?= htmlspecialchars($color) ?>; border-radius: 3px; margin-right: 10px; border: 1px solid #ddd;"></div>
                                        <small><?= htmlspecialchars(str_replace('_', ' ', ucwords($key, '_'))) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No color theme configured</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</section>

<script>
function setPreviewSize(width, height) {
    const frame = document.getElementById('previewFrame');
    const container = frame.parentElement;
    
    // Remove active class from all buttons
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Add active class to clicked button
    event.target.classList.add('active');
    
    // Set frame size
    frame.style.width = width;
    if (height !== 'auto') {
        frame.style.height = height;
    }
    
    // Center the frame if it's smaller than container
    if (width !== '100%') {
        container.style.maxWidth = width;
    } else {
        container.style.maxWidth = '100%';
    }
}

function refreshPreview() {
    const frame = document.getElementById('previewFrame');
    frame.src = frame.src;
}

function openInNewTab() {
    const url = document.getElementById('previewUrl').value;
    window.open('http://' + url, '_blank');
}

// Auto-refresh every 30 seconds
setInterval(function() {
    const frame = document.getElementById('previewFrame');
    if (frame) {
        frame.contentWindow.location.reload();
    }
}, 30000);
</script>

<?php require_once 'includes/footer.php'; ?>