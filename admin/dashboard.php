<?php
session_start();
require_once 'includes/auth.php';
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';
require_once 'includes/header.php';

$db = new Database();

$articlesCount = $db->count('articles');
$projectsCount = $db->count('projects');
$skillsCount = $db->count('skills');
$messagesCount = $db->count('contact_messages');
$unreadMessages = $db->count('contact_messages', 'is_read = 0');
$seoSettingsCount = $db->count('seo_settings');
$settingsCount = $db->count('settings');

$recentArticles = $db->fetchAll('SELECT id, title, created_at FROM articles ORDER BY created_at DESC LIMIT 5');
$recentProjects = $db->fetchAll('SELECT id, title, created_at FROM projects ORDER BY created_at DESC LIMIT 5');
$recentMessages = $db->fetchAll('SELECT id, name, email, subject, created_at, is_read FROM contact_messages ORDER BY created_at DESC LIMIT 5');
$recentSEOSettings = $db->fetchAll('SELECT page, title, description FROM seo_settings ORDER BY page LIMIT 5');
$colorSettings = $db->fetchAll('SELECT setting_key, setting_value FROM settings WHERE setting_type = "color" ORDER BY setting_key LIMIT 5');
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        
        <?php if (isset($error)): ?>
            <?= showErrorMessage($error) ?>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $articlesCount; ?></h3>
                        <p>Articles</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <a href="articles.php" class="small-box-footer">
                        More info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $projectsCount; ?></h3>
                        <p>Projects</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <a href="projects.php" class="small-box-footer">
                        More info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $unreadMessages; ?></h3>
                        <p>Unread Messages</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <a href="contacts.php" class="small-box-footer">
                        More info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo $skillsCount; ?></h3>
                        <p>Skills</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <a href="about.php" class="small-box-footer">
                        More info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Additional Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?php echo $seoSettingsCount; ?></h3>
                        <p>SEO Pages</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <a href="seo.php" class="small-box-footer">
                        More info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3><?php echo $settingsCount; ?></h3>
                        <p>Settings</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <a href="settings.php" class="small-box-footer">
                        More info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-gradient-info">
                    <div class="inner">
                        <h3><?php echo count($colorSettings); ?></h3>
                        <p>Color Themes</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <a href="settings.php#colors" class="small-box-footer">
                        More info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-gradient-success">
                    <div class="inner">
                        <h3><?php echo $messagesCount; ?></h3>
                        <p>Total Messages</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <a href="contacts.php" class="small-box-footer">
                        More info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Content -->
        <div class="row">
            <!-- Recent Articles -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-newspaper mr-1"></i>
                            Recent Articles
                        </h3>
                        <div class="card-tools">
                            <a href="articles.php" class="btn btn-tool btn-sm">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recentArticles)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recentArticles as $article): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($article['title']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= date('M j, Y', strtotime($article['created_at'])) ?></small>
                                            </div>
                                            <a href="articles.php?edit=<?= $article['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-newspaper fa-2x mb-2"></i>
                                <p>No articles yet</p>
                                <a href="articles.php" class="btn btn-primary btn-sm">Create First Article</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Projects -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-project-diagram mr-1"></i>
                            Recent Projects
                        </h3>
                        <div class="card-tools">
                            <a href="projects.php" class="btn btn-tool btn-sm">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recentProjects)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recentProjects as $project): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($project['title']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= date('M j, Y', strtotime($project['created_at'])) ?></small>
                                            </div>
                                            <a href="projects.php?edit=<?= $project['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-project-diagram fa-2x mb-2"></i>
                                <p>No projects yet</p>
                                <a href="projects.php" class="btn btn-success btn-sm">Create First Project</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Messages -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-envelope mr-1"></i>
                            Recent Contact Messages
                        </h3>
                        <div class="card-tools">
                            <a href="contacts.php" class="btn btn-tool btn-sm">
                                <i class="fas fa-eye"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recentMessages)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Subject</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentMessages as $message): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($message['name']) ?></td>
                                                <td><?= htmlspecialchars($message['email']) ?></td>
                                                <td><?= htmlspecialchars(substr($message['subject'], 0, 30)) ?><?= strlen($message['subject']) > 30 ? '...' : '' ?></td>
                                                <td><?= date('M j, Y', strtotime($message['created_at'])) ?></td>
                                                <td>
                                                    <?php if ($message['is_read'] == 0): ?>
                                                        <span class="badge badge-warning">Unread</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Read</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="contacts.php?view=<?= $message['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-envelope fa-2x mb-2"></i>
                                <p>No messages yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bolt mr-1"></i>
                            Quick Actions
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6 col-12">
                                <a href="articles.php?action=add" class="btn btn-block btn-outline-primary">
                                    <i class="fas fa-plus"></i> New Article
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 col-12">
                                <a href="projects.php?action=add" class="btn btn-block btn-outline-success">
                                    <i class="fas fa-plus"></i> New Project
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 col-12">
                                <a href="seo.php" class="btn btn-block btn-outline-info">
                                    <i class="fas fa-search"></i> SEO Settings
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 col-12">
                                <a href="settings.php" class="btn btn-block btn-outline-warning">
                                    <i class="fas fa-cogs"></i> Site Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SEO Settings & Color Themes -->
        <div class="row">
            <!-- SEO Settings -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-search mr-1"></i>
                            SEO Settings
                        </h3>
                        <div class="card-tools">
                            <a href="seo.php" class="btn btn-tool btn-sm">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recentSEOSettings)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recentSEOSettings as $seo): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars(ucfirst($seo['page'])) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars(substr($seo['title'], 0, 40)) ?><?= strlen($seo['title']) > 40 ? '...' : '' ?></small>
                                            </div>
                                            <a href="seo.php?page=<?= urlencode($seo['page']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-search fa-2x mb-2"></i>
                                <p>No SEO settings yet</p>
                                <a href="seo.php" class="btn btn-primary btn-sm">Add SEO Settings</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Color Settings -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-palette mr-1"></i>
                            Color Themes
                        </h3>
                        <div class="card-tools">
                            <a href="settings.php" class="btn btn-tool btn-sm">
                                <i class="fas fa-cog"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($colorSettings)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($colorSettings as $color): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="color-preview" style="width: 20px; height: 20px; background-color: <?= htmlspecialchars($color['setting_value']) ?>; border-radius: 3px; margin-right: 10px; border: 1px solid #ddd;"></div>
                                                <div>
                                                    <strong><?= htmlspecialchars(str_replace('_', ' ', ucwords($color['setting_key'], '_'))) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= htmlspecialchars($color['setting_value']) ?></small>
                                                </div>
                                            </div>
                                            <a href="settings.php#colors" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-palette fa-2x mb-2"></i>
                                <p>No color settings yet</p>
                                <a href="settings.php" class="btn btn-secondary btn-sm">Configure Colors</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>