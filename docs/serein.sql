-- Serein Blog Database Structure
-- Updated for CRUD-based Admin Panel
-- Compatible with XAMPP MySQL

CREATE DATABASE IF NOT EXISTS `serein` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `serein`;

-- ============================
-- Users Table (Authentication)
-- ============================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','editor','viewer') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- About Section
-- ============================
CREATE TABLE `about` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- Skills Table
-- ============================
CREATE TABLE `skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `level` int(11) NOT NULL CHECK (`level` >= 0 AND `level` <= 100),
  `about_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_skills_about` (`about_id`),
  CONSTRAINT `fk_skills_about` FOREIGN KEY (`about_id`) REFERENCES `about` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- Social Links Table
-- ============================
CREATE TABLE `social_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `about_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_social_links_about` (`about_id`),
  CONSTRAINT `fk_social_links_about` FOREIGN KEY (`about_id`) REFERENCES `about` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- Testimonials Table
-- ============================
CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `text` text NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `about_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_testimonials_about` (`about_id`),
  CONSTRAINT `fk_testimonials_about` FOREIGN KEY (`about_id`) REFERENCES `about` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- Articles Table
-- ============================
CREATE TABLE `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `content` text NOT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- Projects Table
-- ============================
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `technologies` text DEFAULT NULL,
  `project_url` varchar(255) DEFAULT NULL,
  `github_url` varchar(255) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `gallery_images` text DEFAULT NULL,
  `status` enum('active','completed','archived') NOT NULL DEFAULT 'active',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- SEO Settings Table
-- ============================
CREATE TABLE `seo_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page` varchar(100) NOT NULL UNIQUE,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `og_title` varchar(255) DEFAULT NULL,
  `og_description` text DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_page` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- Settings Table (General)
-- ============================
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json','color') NOT NULL DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- Contact Messages Table
-- ============================
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `replied` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- Analytics Table
-- ============================
CREATE TABLE `analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referer` varchar(500) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_page` (`page`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `github_link` varchar(255) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `technologies` text DEFAULT NULL,
  `status` enum('active','completed','archived') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- SEO Table
-- ============================
CREATE TABLE `seo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page` varchar(50) NOT NULL UNIQUE,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_page` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- Settings Table
-- ============================
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `theme_color` varchar(7) NOT NULL DEFAULT '#007bff',
  `layout_order` json DEFAULT NULL,
  `site_name` varchar(100) DEFAULT 'Serein Blog',
  `site_logo` varchar(255) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `social_links_display` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- Contact Messages Table
-- ============================
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- Insert Default Data
-- ============================

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `password_hash`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert default about content
INSERT INTO `about` (`content`) VALUES
('<p>I am a passionate cybersecurity professional with expertise in penetration testing, vulnerability assessment, and security consulting. With years of experience in the field, I help organizations strengthen their security posture and protect against cyber threats.</p>');

-- Insert default skills
INSERT INTO `skills` (`name`, `level`, `about_id`) VALUES
('Penetration Testing', 95, 1),
('Vulnerability Assessment', 90, 1),
('Network Security', 85, 1),
('Web Application Security', 88, 1),
('Python Programming', 80, 1),
('Linux Administration', 85, 1);

-- Insert default social links
INSERT INTO `social_links` (`platform`, `url`, `about_id`) VALUES
('GitHub', 'https://github.com/serein', 1),
('LinkedIn', 'https://linkedin.com/in/serein', 1),
('Twitter', 'https://twitter.com/serein', 1);

-- Insert default testimonials
INSERT INTO `testimonials` (`name`, `text`, `company`, `about_id`) VALUES
('John Doe', 'Excellent security assessment and professional service. Highly recommended!', 'TechCorp Inc.', 1),
('Jane Smith', 'Outstanding penetration testing skills and detailed reporting.', 'SecureNet Ltd.', 1);

-- Insert default SEO settings
INSERT INTO `seo_settings` (`page`, `title`, `description`, `keywords`) VALUES
('home', 'Serein - Cybersecurity Professional', 'Professional cybersecurity services including penetration testing and vulnerability assessment', 'cybersecurity, penetration testing, vulnerability assessment, security consultant'),
('about', 'About - Serein', 'Learn more about my cybersecurity expertise and professional background', 'about, cybersecurity expert, security professional'),
('portfolio', 'Portfolio - Serein', 'View my cybersecurity projects and case studies', 'portfolio, projects, cybersecurity work, case studies'),
('blog', 'Blog - Serein', 'Latest insights and articles on cybersecurity topics', 'blog, cybersecurity articles, security insights'),
('contact', 'Contact - Serein', 'Get in touch for cybersecurity consulting and services', 'contact, cybersecurity services, security consulting');

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_title', 'Serein - Cybersecurity Professional', 'text', 'Main site title'),
('site_description', 'Professional cybersecurity services and consulting', 'text', 'Site description'),
('primary_color', '#00ff41', 'color', 'Primary theme color'),
('secondary_color', '#0a0a0a', 'color', 'Secondary theme color'),
('dark_mode_enabled', '1', 'boolean', 'Enable dark mode toggle'),
('analytics_enabled', '1', 'boolean', 'Enable basic analytics tracking');

-- Default about content
INSERT INTO `about` (`content`) VALUES
('<div class="about-content">
<h3>Nguyen Thanh Dat</h3>
<h4>Technical Support Engineer & Full-Stack Developer</h4>
<p>Experienced Technical Support Engineer with over 3 years of expertise in troubleshooting, system administration, and customer support. Passionate about web development and creating innovative solutions that bridge technical complexity with user-friendly experiences.</p>

<h5>Professional Experience</h5>
<ul>
<li><strong>Technical Support Engineer</strong> - Providing comprehensive technical assistance and system maintenance</li>
<li><strong>Full-Stack Development</strong> - Building responsive web applications using modern technologies</li>
<li><strong>System Administration</strong> - Managing server infrastructure and database optimization</li>
<li><strong>Customer Relations</strong> - Delivering exceptional technical support and training</li>
</ul>

<h5>Education & Certifications</h5>
<p>Continuous learning in computer science, web technologies, and system administration. Committed to staying updated with the latest industry trends and best practices.</p>

<h5>Core Competencies</h5>
<p>Technical troubleshooting, web development, database management, server administration, customer support, problem-solving, and team collaboration.</p>
</div>');

-- Default skills
INSERT INTO `skills` (`name`, `level`, `about_id`) VALUES
('Technical Support', 95, 1),
('System Administration', 90, 1),
('PHP', 85, 1),
('JavaScript', 80, 1),
('MySQL', 85, 1),
('HTML/CSS', 90, 1),
('Problem Solving', 95, 1),
('Customer Service', 90, 1),
('Server Management', 85, 1),
('Database Optimization', 80, 1);

-- Default social links
INSERT INTO `social_links` (`platform`, `url`, `about_id`) VALUES
('GitHub', 'https://github.com/nguyenthanhdat', 1),
('LinkedIn', 'https://linkedin.com/in/nguyen-thanh-dat-tech', 1),
('Email', 'mailto:dat.nguyen.tech@gmail.com', 1);

-- Default testimonials
INSERT INTO `testimonials` (`name`, `text`, `company`, `about_id`) VALUES
('Technical Team Lead', 'Dat consistently delivers exceptional technical support and demonstrates strong problem-solving skills in complex system environments.', 'Enterprise Solutions Inc', 1),
('Project Manager', 'Outstanding technical expertise and customer service. Dat\'s ability to troubleshoot and resolve issues quickly has been invaluable to our operations.', 'Tech Innovation Ltd', 1);

-- Default SEO settings
INSERT INTO `seo` (`page`, `title`, `description`, `keywords`) VALUES
('home', 'Nguyen Thanh Dat - Technical Support Engineer & Developer', 'Technical Support Engineer with 3+ years experience in system administration, troubleshooting, and full-stack development.', 'technical support, system administration, web development, troubleshooting, Nguyen Thanh Dat'),
('about', 'About Nguyen Thanh Dat - Technical Expert', 'Learn about my background as a Technical Support Engineer and Full-Stack Developer with expertise in system administration.', 'about, technical support engineer, system administrator, developer, experience'),
('articles', 'Tech Articles - Nguyen Thanh Dat', 'Read my latest articles about technical support, system administration, and web development.', 'articles, technical support, system administration, web development, tutorials'),
('projects', 'Projects - Nguyen Thanh Dat Portfolio', 'Explore my technical projects including system solutions and web development work.', 'projects, portfolio, technical solutions, web development, system administration'),
('contact', 'Contact Nguyen Thanh Dat - Technical Consultant', 'Get in touch for technical support, system administration, or development collaboration opportunities.', 'contact, technical support, system administration, collaboration, consultant');

-- Default settings
INSERT INTO `settings` (`theme_color`, `layout_order`, `site_name`, `contact_email`) VALUES
('#00ff00', '["about", "skills", "projects", "articles", "testimonials", "contact"]', 'Nguyen Thanh Dat - Technical Portfolio', 'dat.nguyen.tech@gmail.com');

-- Sample articles
INSERT INTO `articles` (`title`, `slug`, `content`, `excerpt`, `status`) VALUES
('Technical Support Best Practices', 'technical-support-best-practices', '<p>In my 3+ years as a Technical Support Engineer, I have learned valuable lessons about effective troubleshooting and customer service. This article shares key strategies for providing exceptional technical support.</p>', 'Essential strategies for effective technical support and customer service.', 'published'),
('System Administration Tips for Beginners', 'system-administration-tips-beginners', '<p>System administration can be challenging for newcomers. Here are practical tips and best practices I have learned from managing server infrastructure and database optimization.</p>', 'Practical system administration tips from real-world experience.', 'published'),
('Building Responsive Web Applications', 'building-responsive-web-applications', '<p>As a full-stack developer, creating responsive and user-friendly web applications is crucial. This guide covers modern development techniques and best practices.</p>', 'Modern techniques for building responsive web applications.', 'published');

-- Sample projects
INSERT INTO `projects` (`title`, `description`, `link`, `github_link`, `technologies`, `status`) VALUES
('Technical Support Dashboard', 'A comprehensive dashboard for tracking and managing technical support tickets with real-time monitoring and analytics.', 'https://support-dashboard.demo', 'https://github.com/nguyenthanhdat/support-dashboard', 'PHP, MySQL, JavaScript, Bootstrap, Chart.js', 'completed'),
('System Monitoring Tool', 'An automated system monitoring solution for server health checks, performance tracking, and alert management.', 'https://sysmon.demo', 'https://github.com/nguyenthanhdat/system-monitor', 'PHP, MySQL, Python, Linux Shell Scripts', 'active'),
('Customer Portal Platform', 'A user-friendly customer portal for technical support requests, knowledge base access, and service management.', 'https://customer-portal.demo', 'https://github.com/nguyenthanhdat/customer-portal', 'PHP, MySQL, HTML5, CSS3, JavaScript, AJAX', 'completed'),
('Database Optimization Suite', 'A collection of tools and scripts for MySQL database performance optimization and maintenance automation.', null, 'https://github.com/nguyenthanhdat/db-optimization', 'MySQL, PHP, Bash Scripts, Performance Tuning', 'active');