# Serein Blog Platform - Python/FastAPI Version

> Modern terminal-style blog platform with FastAPI backend and PostgreSQL database

## 🚀 Quick Start

### Prerequisites
- Python 3.11+
- PostgreSQL 14+
- pip or poetry

### Installation

```bash
# Clone repository
git clone https://github.com/datyuno1409/serein-blog-new.git
cd serein-blog-new

# Create virtual environment
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Setup environment variables
cp .env.example .env
# Edit .env with your database credentials

# Run database migrations
alembic upgrade head

# Create admin user (optional)
python scripts/create_admin.py

# Run development server
uvicorn app:app --reload
```

Visit: http://localhost:8000

## 📁 Project Structure

```
serein-blog-new/
├── app.py                 # Main FastAPI application
├── config.py              # Configuration management
├── database.py            # Database connection
├── auth.py                # Authentication utilities
├── schemas.py             # Pydantic schemas
├── models/                # SQLAlchemy models
│   ├── user.py
│   ├── article.py
│   ├── project.py
│   └── ...
├── api/                   # API endpoints
│   ├── auth.py
│   ├── articles.py
│   ├── projects.py
│   └── ...
├── admin/                 # Admin panel (AdminLTE3)
├── assets/                # Frontend assets
├── templates/             # Jinja2 templates
└── requirements.txt       # Python dependencies
```

## 🎯 Features

- ✅ **FastAPI Backend** - High-performance async API
- ✅ **PostgreSQL Database** - Production-ready database
- ✅ **JWT Authentication** - Secure token-based auth
- ✅ **AdminLTE3 Interface** - Modern admin panel
- ✅ **RESTful API** - Complete CRUD operations
- ✅ **Auto Documentation** - Swagger UI at `/api/docs`
- ✅ **Terminal UI** - Matrix rain effects, typewriter animation
- ✅ **Responsive Design** - Mobile-first approach

## 🔧 API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login and get token
- `GET /api/auth/me` - Get current user

### Articles
- `GET /api/articles` - List articles
- `POST /api/articles` - Create article (admin)
- `PUT /api/articles/{id}` - Update article (admin)
- `DELETE /api/articles/{id}` - Delete article (admin)

### Projects
- `GET /api/projects` - List projects
- `POST /api/projects` - Create project (admin)
- `PUT /api/projects/{id}` - Update project (admin)
- `DELETE /api/projects/{id}` - Delete project (admin)

**Full API Documentation**: http://localhost:8000/api/docs

## 🗄️ Database Setup

### PostgreSQL

```bash
# Create database
createdb serein_db

# Or using psql
psql -U postgres
CREATE DATABASE serein_db;
```

### Migrations

```bash
# Create migration
alembic revision --autogenerate -m "description"

# Apply migrations
alembic upgrade head

# Rollback
alembic downgrade -1
```

## 🚀 Deployment

### VPS Deployment

```bash
# Install dependencies
sudo apt update
sudo apt install python3.11 python3-pip postgresql nginx

# Setup PostgreSQL
sudo -u postgres createdb serein_db

# Clone and setup
git clone https://github.com/datyuno1409/serein-blog-new.git
cd serein-blog-new
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt

# Configure environment
cp .env.example .env
nano .env  # Edit with production settings

# Run with Gunicorn
pip install gunicorn
gunicorn app:app -w 4 -k uvicorn.workers.UvicornWorker --bind 0.0.0.0:8000

# Setup systemd service (optional)
sudo nano /etc/systemd/system/serein.service
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    location /assets {
        alias /path/to/serein-blog-new/assets;
    }
}
```

## 🔐 Security

- JWT token authentication
- Password hashing with bcrypt
- CORS protection
- SQL injection prevention (SQLAlchemy ORM)
- Input validation (Pydantic)

## 📊 Tech Stack

### Backend
- **FastAPI** - Modern Python web framework
- **SQLAlchemy** - ORM for database
- **PostgreSQL** - Production database
- **Pydantic** - Data validation
- **python-jose** - JWT tokens
- **passlib** - Password hashing

### Frontend
- **HTML5/CSS3** - Structure and styling
- **Vanilla JavaScript** - No framework
- **AdminLTE3** - Admin panel theme
- **Font Awesome** - Icons

## 👤 Author

**Nguyen Thanh Dat**  
Email: ngthanhdat.fudn@gmail.com  
GitHub: [@datyuno1409](https://github.com/datyuno1409)

## 📝 License

MIT License

## 🔄 Migration from PHP

This project was migrated from PHP to Python/FastAPI while maintaining:
- ✅ All functionality
- ✅ AdminLTE3 interface
- ✅ Frontend design
- ✅ Database structure
- ✅ API compatibility

---

**Made with ❤️ using FastAPI and Python**