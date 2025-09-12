# Serein Blog Platform

## Mô tả dự án
* lưu ý: Sản phẩm này hoàn toàn do AI tạo ra. nhằm mục đích thử nghiệm tính logic, học thuật,... 
Serein Blog Platform là một hệ thống blog cá nhân hiện đại được thiết kế dành cho các chuyên gia kỹ thuật và lập trình viên. Dự án cung cấp một nền tảng hoàn chỉnh để chia sẻ kiến thức, kinh nghiệm và dự án cá nhân với giao diện terminal-style độc đáo và hiệu ứng hacker-style ấn tượng.
<img width="1048" height="921" alt="image" src="https://github.com/user-attachments/assets/9553e648-d1af-4ab6-a3cd-bf63fe0ae20c" />
<img width="1870" height="923" alt="image" src="https://github.com/user-attachments/assets/11b15d7d-0af6-4053-9359-0491010ab57d" />
<img width="1875" height="924" alt="image" src="https://github.com/user-attachments/assets/274ce219-b00f-4496-90a2-aaf00d0221cd" />

## Tính năng nổi bật

### 🚀 Giao diện và Trải nghiệm
- **Terminal-style UI**: Giao diện mô phỏng terminal với hiệu ứng typewriter
- **Matrix Rain Effect**: Hiệu ứng mưa matrix và particle system
- **Responsive Design**: Tương thích hoàn hảo trên mọi thiết bị
- **Dark Theme**: Chủ đề tối chuyên nghiệp với màu sắc neon

### 📝 Quản lý Nội dung
- **Blog Management**: Hệ thống quản lý bài viết với editor WYSIWYG
- **Project Portfolio**: Showcase các dự án cá nhân
- **SEO Optimization**: Tối ưu hóa SEO tự động
- **Draft System**: Lưu bản nháp tự động

### 🔧 Tính năng Kỹ thuật
- **Admin Dashboard**: Bảng điều khiển quản trị viên đầy đủ
- **API RESTful**: API hoàn chỉnh cho tất cả chức năng
- **Database Management**: Quản lý cơ sở dữ liệu MySQL
- **Security Features**: Bảo mật với CSRF protection, rate limiting

### 📊 Analytics và Tối ưu
- **Performance Optimized**: File CSS/JS đã được nén
- **Clean Architecture**: Cấu trúc MVC rõ ràng
- **Modular Design**: Thiết kế module hóa dễ mở rộng

## Hướng dẫn cài đặt

### Yêu cầu hệ thống
- PHP 7.4 trở lên
- MySQL 5.7 trở lên
- Web server (Apache/Nginx)
- Composer (tùy chọn)

### Các bước cài đặt

1. **Clone dự án**
   ```bash
   git clone https://github.com/datyuno1409/serein-blog.git
   cd serein-blog
   ```

2. **Cấu hình cơ sở dữ liệu**
   - Tạo database MySQL mới
   - Import file `docs/serein.sql`
   - Cập nhật thông tin kết nối trong `config/database.php`

3. **Cấu hình web server**
   - Đặt document root tới thư mục dự án
   - Đảm bảo mod_rewrite được bật (Apache)

4. **Khởi chạy dự án**
   ```bash
   # Sử dụng PHP built-in server (development)
   php -S localhost:8000 router.php
   
   # Hoặc truy cập qua web server
   http://your-domain.com
   ```

5. **Truy cập admin panel**
   - URL: `http://localhost:8000/admin`
   - Tạo tài khoản admin đầu tiên
   admin / admin123
## Cấu trúc thư mục

```
serein-blog-new/
├── admin/                  # Admin panel
│   ├── css/               # Admin styles
│   ├── js/                # Admin scripts
│   ├── includes/          # Admin components
│   └── *.php             # Admin pages
├── api/                   # API endpoints
├── assets/                # Frontend assets
│   ├── css/              # Stylesheets
│   │   ├── style.css     # Main stylesheet
│   │   └── style.min.css # Minified version
│   ├── js/               # JavaScript files
│   │   ├── script.js     # Main script
│   │   └── script.min.js # Minified version
│   └── images/           # Image assets
├── config/                # Configuration files
│   ├── database.php      # Database config
│   ├── CSRF.php          # CSRF protection
│   └── *.php            # Other configs
├── docs/                  # Documentation
│   ├── serein.sql        # Database schema
│   └── *.pdf            # Documents
├── models/                # Data models
│   ├── Article.php       # Article model
│   ├── Project.php       # Project model
│   └── *.php            # Other models
├── *.html                 # Frontend pages
├── api.php               # Main API handler
├── router.php            # URL router
└── README.md             # This file
```

## Công nghệ sử dụng

### Backend
- **PHP 7.4+**: Ngôn ngữ lập trình chính
- **MySQL**: Cơ sở dữ liệu
- **PDO**: Database abstraction layer
- **Custom MVC**: Kiến trúc MVC tự xây dựng

### Frontend
- **HTML5/CSS3**: Markup và styling
- **Vanilla JavaScript**: Không sử dụng framework
- **Fira Code Font**: Font monospace chuyên nghiệp
- **CSS Grid/Flexbox**: Layout hiện đại

### Security & Performance
- **CSRF Protection**: Bảo vệ chống tấn công CSRF
- **Rate Limiting**: Giới hạn tần suất request
- **Input Sanitization**: Làm sạch dữ liệu đầu vào
- **File Minification**: Nén file CSS/JS

### Development Tools
- **Git**: Version control
- **Composer**: Dependency management (tùy chọn)
- **PowerShell**: Automation scripts

## API Documentation

### Endpoints chính
- `GET /api.php?endpoint=articles` - Lấy danh sách bài viết
- `GET /api.php?endpoint=projects` - Lấy danh sách dự án
- `GET /api.php?endpoint=about` - Thông tin cá nhân
- `GET /api.php?endpoint=stats` - Thống kê website

## Hướng dẫn sử dụng

### Quản lý bài viết
1. Truy cập admin panel tại `/admin`
2. Đăng nhập với tài khoản admin
3. Vào mục "Articles" để tạo/chỉnh sửa bài viết
4. Sử dụng editor để viết nội dung
5. Publish hoặc lưu draft

### Quản lý dự án
1. Vào mục "Projects" trong admin
2. Thêm thông tin dự án, link demo, source code
3. Upload hình ảnh minh họa
4. Cấu hình hiển thị trên trang chủ

### Tùy chỉnh giao diện
1. Chỉnh sửa file CSS trong `assets/css/`
2. Cập nhật JavaScript trong `assets/js/`
3. Sử dụng file minified cho production

## Thông tin liên hệ

**Tác giả**: Nguyen Thanh Dat  
**Email**: ngthanhdat.fudn@gmail.com  
**GitHub**: [github.com/datyuno1409](https://github.com/)  
**Website**: [serein-new](https://serein-new.netlify.app)  

## License

Dự án này được phát hành dưới [MIT License](LICENSE).

## Changelog

### v1.0.0 (2025-01-12)
- ✨ Phiên bản đầu tiên
- 🎨 Giao diện terminal-style hoàn chỉnh
- 📝 Hệ thống blog và portfolio
- 🔐 Admin panel với bảo mật
- 🚀 Tối ưu hóa performance

---

**⭐ Nếu dự án hữu ích, đừng quên star repository!**
