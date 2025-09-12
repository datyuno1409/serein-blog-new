<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link">
        <img src="../assets/images/logo.png" alt="Serein Logo" class="brand-image img-circle elevation-3" style="opacity: .8" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM2NjdlZWEiLz4KPHRleHQgeD0iMjAiIHk9IjI1IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSJ3aGl0ZSIgZm9udC1zaXplPSIxNiIgZm9udC13ZWlnaHQ9ImJvbGQiPkE8L3RleHQ+Cjwvc3ZnPg=='">
        <span class="brand-text font-weight-light">Serein Admin</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="../assets/images/user.png" class="img-circle elevation-2" alt="User Image" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM2Yzc1N2QiLz4KPHN2ZyB4PSI4IiB5PSI4IiB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIGZpbGw9IndoaXRlIj4KPHA+VXNlcjwvcD4KPC9zdmc+Cjwvc3ZnPg=='">
            </div>
            <div class="info">
                <a href="#" class="d-block"><?= $_SESSION['admin_name'] ?? 'Administrator' ?></a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                
                <li class="nav-header">CONTENT MANAGEMENT</li>
                
                <li class="nav-item">
                    <a href="about.php" class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user"></i>
                        <p>About Page</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="articles.php" class="nav-link <?php echo ($current_page == 'articles.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-newspaper"></i>
                        <p>Articles</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="projects.php" class="nav-link <?php echo ($current_page == 'projects.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-project-diagram"></i>
                        <p>Projects</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="contacts.php" class="nav-link <?php echo ($current_page == 'contacts.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-envelope"></i>
                        <p>Contacts</p>
                    </a>
                </li>
                
                <li class="nav-header">SETTINGS</li>
                
                <li class="nav-item">
                    <a href="seo.php" class="nav-link <?php echo ($current_page == 'seo.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-search"></i>
                        <p>SEO Settings</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>General Settings</p>
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