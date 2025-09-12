<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $pageTitle ?? 'Admin Panel'; ?></title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Bootstrap Color Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-colorpicker@3.4.0/dist/css/bootstrap-colorpicker.min.css">
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="css/admin.css">
    
    <!-- Custom Admin CSS -->
    <style>
        .main-header .navbar {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-bottom: 2px solid #00ff41;
        }
        
        .main-sidebar {
            background: linear-gradient(180deg, #1a1a1a 0%, #0d1117 100%);
        }
        
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active {
            background-color: #00ff41;
            color: #000;
        }
        
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link:hover {
            background-color: rgba(0, 255, 65, 0.1);
            color: #00ff41;
        }
        
        .content-wrapper {
            background-color: #f4f4f4;
        }
        
        .card {
            box-shadow: 0 0 20px rgba(0, 255, 65, 0.1);
            border: 1px solid rgba(0, 255, 65, 0.2);
        }
        
        .card-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #00ff41;
            border-bottom: 2px solid #00ff41;
        }
        
        .btn-primary {
            background-color: #00ff41;
            border-color: #00ff41;
            color: #000;
        }
        
        .btn-primary:hover {
            background-color: #00cc33;
            border-color: #00cc33;
            color: #000;
        }
        
        .small-box {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .nav-tabs .nav-link.active {
            background-color: #00ff41;
            color: #000;
            border-color: #00ff41;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 255, 65, 0.05);
        }
        
        .alert-success {
            background-color: rgba(0, 255, 65, 0.1);
            border-color: #00ff41;
            color: #155724;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: #dc3545;
        }
        
        .brand-link {
            background: linear-gradient(135deg, #00ff41 0%, #00cc33 100%);
            color: #000 !important;
            font-weight: bold;
        }
        
        .brand-link:hover {
            color: #000 !important;
        }
        
        .user-panel .info {
            color: #00ff41;
        }
        
        .terminal-text {
            font-family: 'Courier New', monospace;
            color: #00ff41;
            background-color: #000;
            padding: 10px;
            border-radius: 5px;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 255, 65, 0.3);
            border-radius: 50%;
            border-top-color: #00ff41;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .message-preview {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .status-badge {
            font-size: 0.75rem;
        }
        
        .stats-card {
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .sidebar-mini.sidebar-collapse .main-sidebar:hover {
            width: 250px;
        }
        
        .dark-mode .content-wrapper {
            background-color: #1a1a1a;
            color: #fff;
        }
        
        .dark-mode .card {
            background-color: #2d2d2d;
            color: #fff;
        }
        
        .navbar-badge {
            font-size: 0.6rem;
            padding: 0.25rem 0.4rem;
        }
    </style>
    
    <?php if (isset($additionalCSS)): ?>
        <?= $additionalCSS ?>
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    
    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
        <div class="loading-spinner"></div>
        <p class="mt-2">Loading...</p>
    </div>
    
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-dark">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="../index.html" class="nav-link" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View Site
                </a>
            </li>
        </ul>
        
        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Messages Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-comments"></i>
                    <span class="badge badge-danger navbar-badge" id="unreadCount" style="display: none;">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <div id="recentMessages">
                        <div class="dropdown-item text-center text-muted">Loading...</div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="contacts.php" class="dropdown-item dropdown-footer">See All Messages</a>
                </div>
            </li>
            
            <!-- User Account Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i>
                    <span class="d-none d-md-inline"><?= $_SESSION['admin_username'] ?? 'Admin' ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <div class="dropdown-item">
                        <div class="media">
                            <div class="media-body">
                                <h3 class="dropdown-item-title">
                                    <?= $_SESSION['admin_username'] ?? 'Admin' ?>
                                </h3>
                                <p class="text-sm text-muted">
                                    <i class="far fa-clock mr-1"></i> 
                                    Last login: <?= date('M j, Y g:i A', strtotime($_SESSION['admin_last_login'] ?? 'now')) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-cog mr-2"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item dropdown-footer">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>
    
    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="dashboard.php" class="brand-link">
            <i class="fas fa-terminal brand-image" style="font-size: 1.5rem; margin-left: 10px;"></i>
            <span class="brand-text font-weight-light">Serein Admin</span>
        </a>
        
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- User Panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <div class="img-circle elevation-2" style="width: 35px; height: 35px; background: #00ff41; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user" style="color: #000;"></i>
                    </div>
                </div>
                <div class="info">
                    <a href="#" class="d-block"><?= $_SESSION['admin_username'] ?? 'Admin' ?></a>
                </div>
            </div>
            
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    
                    <li class="nav-header">CONTENT MANAGEMENT</li>
                    
                    <li class="nav-item">
                        <a href="about.php" class="nav-link <?= ($currentPage ?? '') === 'about' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-user"></i>
                            <p>About Page</p>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="articles.php" class="nav-link <?= ($currentPage ?? '') === 'articles' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-newspaper"></i>
                            <p>
                                Articles
                                <span class="badge badge-info right" id="articlesCount">0</span>
                            </p>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="projects.php" class="nav-link <?= ($currentPage ?? '') === 'projects' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-project-diagram"></i>
                            <p>
                                Projects
                                <span class="badge badge-info right" id="projectsCount">0</span>
                            </p>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="contacts.php" class="nav-link <?= ($currentPage ?? '') === 'contacts' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>
                                Messages
                                <span class="badge badge-warning right" id="contactsCount" style="display: none;">0</span>
                            </p>
                        </a>
                    </li>
                    
                    <li class="nav-header">CONFIGURATION</li>
                    
                    <li class="nav-item">
                        <a href="seo.php" class="nav-link <?= ($currentPage ?? '') === 'seo' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-search"></i>
                            <p>SEO Settings</p>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link <?= ($currentPage ?? '') === 'settings' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>Site Settings</p>
                        </a>
                    </li>
                    
                    <li class="nav-header">SYSTEM</li>
                    
                    <li class="nav-item">
                        <a href="preview.php" class="nav-link <?= ($currentPage ?? '') === 'preview' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-eye"></i>
                            <p>Live Preview</p>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="../index.html" class="nav-link" target="_blank">
                            <i class="nav-icon fas fa-external-link-alt"></i>
                            <p>View Website</p>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Logout</p>
                        </a>
                    </li>
                    
                </ul>
            </nav>
        </div>
    </aside>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">