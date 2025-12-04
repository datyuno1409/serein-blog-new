# Project Structure Guide

## Overview

The Serein Blog Platform has been reorganized into a clean, modular structure that separates concerns and makes the codebase easy to navigate.

## Directory Structure

```
serein-blog-new/
│
├── main.py                      # 🚀 Application entry point
│
├── backend/                     # 🐍 Python/FastAPI backend
│   ├── app.py                  # Main FastAPI application
│   ├── config.py               # Settings & configuration
│   ├── database.py             # Database connection
│   ├── auth.py                 # JWT authentication
│   ├── schemas.py              # Pydantic validation schemas
│   │
│   ├── models/                 # 📊 SQLAlchemy database models
│   │   ├── __init__.py
│   │   ├── user.py            # User model
│   │   ├── article.py         # Article/blog post model
│   │   ├── project.py         # Portfolio project model
│   │   ├── about.py           # Personal info model
│   │   ├── skill.py           # Technical skills model
│   │   ├── social_link.py     # Social media links
│   │   ├── testimonial.py     # Client testimonials
│   │   ├── seo_setting.py     # SEO meta tags
│   │   └── setting.py         # App settings
│   │
│   └── api/                    # 🔌 API endpoints (routers)
│       ├── __init__.py
│       ├── auth.py            # Authentication endpoints
│       ├── articles.py        # Article CRUD
│       ├── projects.py        # Project CRUD
│       ├── about.py           # About info endpoints
│       ├── skills.py          # Skills management
│       ├── social_links.py    # Social links CRUD
│       ├── seo_settings.py    # SEO management
│       └── settings.py        # Settings CRUD
│
├── frontend/                    # 🎨 Static HTML pages
│   ├── index.html              # Homepage
│   ├── about.html              # About page
│   ├── blog.html               # Blog listing
│   ├── post.html               # Single post view
│   └── services.html           # Portfolio/services
│
├── assets/                      # 📦 Static assets
│   ├── css/                    # Stylesheets
│   │   ├── style.css          # Main styles
│   │   └── style.min.css      # Minified
│   ├── js/                     # JavaScript
│   │   ├── script.js          # Main script
│   │   └── script.min.js      # Minified
│   └── images/                 # Image assets
│
├── admin/                       # 👨‍💼 AdminLTE3 admin panel
│   ├── css/                    # Admin styles
│   ├── js/                     # Admin scripts
│   └── includes/               # Admin components
│
├── docs/                        # 📚 Documentation
│   ├── VPS_DEPLOYMENT.md       # VPS deployment guide
│   ├── MIGRATION.md            # Data migration guide
│   └── STRUCTURE.md            # This file
│
├── scripts/                     # 🛠️ Utility scripts
│   └── create_admin.py         # Create admin user
│
├── alembic/                     # 🗄️ Database migrations
│   ├── env.py                  # Migration environment
│   ├── script.py.mako          # Migration template
│   └── versions/               # Migration files
│
├── old_php_code/               # 📦 Archived PHP code (reference)
│
├── .env                         # 🔐 Environment variables (gitignored)
├── .env.example                # Environment template
├── .gitignore                  # Git ignore rules
├── alembic.ini                 # Alembic configuration
├── requirements.txt            # Python dependencies
├── Procfile                    # Deployment config
├── runtime.txt                 # Python version
└── README.md                   # Project documentation
```

## Key Files Explained

### Entry Point
- **`main.py`** - Starts the application, imports from `backend/app.py`

### Backend Core
- **`backend/app.py`** - FastAPI application setup, routes, middleware
- **`backend/config.py`** - Environment configuration using Pydantic
- **`backend/database.py`** - SQLAlchemy engine and session management
- **`backend/auth.py`** - JWT token creation and validation
- **`backend/schemas.py`** - Pydantic models for request/response validation

### Models (Database Tables)
Each model file represents a database table:
- `user.py` - Authentication and authorization
- `article.py` - Blog posts with slug and status
- `project.py` - Portfolio items with JSON fields
- `about.py` - Personal information
- `skill.py` - Technical skills with proficiency
- `social_link.py` - Social media profiles
- `testimonial.py` - Client reviews
- `seo_setting.py` - SEO meta tags per page
- `setting.py` - Key-value configuration

### API Endpoints
Each API file contains related endpoints:
- `auth.py` - `/api/auth/*` - Login, register, user info
- `articles.py` - `/api/articles/*` - Article CRUD
- `projects.py` - `/api/projects/*` - Project CRUD
- And so on...

## Import Structure

### From Root Directory
```python
# main.py imports from backend
from backend.app import app
```

### Within Backend
```python
# Models import database
from database import Base

# API imports models and schemas
from models.user import User
from schemas import UserCreate, UserResponse

# Auth imports config
from config import settings
```

## Running the Application

### Development
```bash
# From project root
python main.py

# Or with uvicorn
uvicorn main:app --reload
```

### Production
```bash
# With Gunicorn
gunicorn main:app -w 4 -k uvicorn.workers.UvicornWorker
```

## Database

### SQLite (Default)
- File: `serein.db` in project root
- No installation needed
- Perfect for development

### PostgreSQL (Production)
- Requires PostgreSQL server
- Update `.env` with connection string
- Better for production use

## Frontend Access

- **Homepage**: http://localhost:8000/
- **About**: http://localhost:8000/about
- **Blog**: http://localhost:8000/blog
- **Portfolio**: http://localhost:8000/portfolio
- **Admin**: http://localhost:8000/admin

## API Access

- **Swagger Docs**: http://localhost:8000/api/docs
- **ReDoc**: http://localhost:8000/api/redoc
- **Health Check**: http://localhost:8000/health

## Old PHP Code

The `old_php_code/` directory contains the original PHP implementation:
- Kept for reference
- Not used by the Python application
- Can be deleted after migration is complete

## Best Practices

### Adding New Features

1. **Model** - Create in `backend/models/`
2. **Schema** - Add to `backend/schemas.py`
3. **API** - Create router in `backend/api/`
4. **Register** - Add to `backend/api/__init__.py`

### Database Changes

1. **Modify Model** - Update SQLAlchemy model
2. **Create Migration** - `alembic revision --autogenerate -m "description"`
3. **Apply Migration** - `alembic upgrade head`

### Configuration

1. **Add to `.env.example`** - Document new variable
2. **Add to `config.py`** - Define in Settings class
3. **Use** - Import `settings` and access value

---

**This structure provides:**
- ✅ Clear separation of concerns
- ✅ Easy to navigate and understand
- ✅ Scalable for future growth
- ✅ Standard Python project layout
