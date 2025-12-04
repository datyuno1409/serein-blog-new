# Serein Blog Platform - Python/FastAPI

> Modern terminal-style blog platform with FastAPI backend and SQLite/PostgreSQL database

## 📁 Project Structure

```
serein-blog-new/
├── main.py                   # Application entry point
├── backend/                  # Python/FastAPI backend
│   ├── app.py               # Main FastAPI application
│   ├── config.py            # Settings management
│   ├── database.py          # Database connection
│   ├── auth.py              # JWT authentication
│   ├── schemas.py           # Pydantic schemas
│   ├── models/              # SQLAlchemy models
│   │   ├── user.py
│   │   ├── article.py
│   │   ├── project.py
│   │   └── ...
│   └── api/                 # API endpoints
│       ├── auth.py
│       ├── articles.py
│       ├── projects.py
│       └── ...
├── frontend/                 # Static frontend files
│   ├── index.html
│   ├── about.html
│   ├── blog.html
│   └── ...
├── assets/                   # CSS, JS, images
├── admin/                    # AdminLTE3 panel
├── docs/                     # Documentation
├── scripts/                  # Utility scripts
├── alembic/                  # Database migrations
├── old_php_code/            # Archived PHP code (reference)
├── requirements.txt          # Python dependencies
├── .env.example             # Environment template
└── README.md                # This file
```

## 🚀 Quick Start

### 1. Install Dependencies

```bash
# Create virtual environment
python -m venv .venv

# Activate virtual environment
# Windows:
.venv\Scripts\activate
# Linux/Mac:
source .venv/bin/activate

# Install packages
pip install -r requirements.txt
```

### 2. Configure Environment

```bash
# Copy environment template
cp .env.example .env

# Edit .env with your settings (SQLite is default)
```

### 3. Initialize Database

```bash
# Run migrations
alembic upgrade head

# Create admin user
python scripts/create_admin.py
```

### 4. Run Application

```bash
# Development server with auto-reload
python main.py

# Or using uvicorn directly
uvicorn main:app --reload
```

Visit: **http://localhost:8000**

## 📚 API Documentation

FastAPI provides automatic interactive documentation:

- **Swagger UI**: http://localhost:8000/api/docs
- **ReDoc**: http://localhost:8000/api/redoc

## 🗄️ Database Options

### SQLite (Default - Easy Setup)
```env
DATABASE_URL=sqlite:///./serein.db
```

### PostgreSQL (Production)
```env
DATABASE_URL=postgresql://user:password@localhost:5432/serein_db
```

## 🔐 Authentication

The API uses JWT (JSON Web Tokens) for authentication:

1. **Register**: `POST /api/auth/register`
2. **Login**: `POST /api/auth/login` → Get access token
3. **Use Token**: Add header `Authorization: Bearer <token>`

## 📖 API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login and get token
- `GET /api/auth/me` - Get current user info

### Articles
- `GET /api/articles` - List articles (with pagination)
- `GET /api/articles/{id}` - Get article by ID
- `GET /api/articles/slug/{slug}` - Get by slug
- `POST /api/articles` - Create article (admin)
- `PUT /api/articles/{id}` - Update article (admin)
- `DELETE /api/articles/{id}` - Delete article (admin)

### Projects
- `GET /api/projects` - List projects
- `POST /api/projects` - Create project (admin)
- `PUT /api/projects/{id}` - Update project (admin)
- `DELETE /api/projects/{id}` - Delete project (admin)

### Other Endpoints
- `/api/about` - Personal information
- `/api/skills` - Technical skills
- `/api/social-links` - Social media links
- `/api/seo` - SEO settings
- `/api/settings` - Application settings

## 🛠️ Development

### Project Structure Explained

- **`main.py`** - Entry point, imports from backend
- **`backend/`** - All Python/FastAPI code
- **`frontend/`** - HTML pages (terminal-style UI)
- **`assets/`** - CSS, JavaScript, images
- **`admin/`** - AdminLTE3 admin panel
- **`old_php_code/`** - Archived PHP code (for reference)

### Running Tests

```bash
# Install test dependencies
pip install pytest pytest-asyncio httpx

# Run tests
pytest
```

### Database Migrations

```bash
# Create new migration
alembic revision --autogenerate -m "description"

# Apply migrations
alembic upgrade head

# Rollback one migration
alembic downgrade -1
```

## 🚀 Deployment

See detailed deployment guides:
- [VPS Deployment](docs/VPS_DEPLOYMENT.md) - Ubuntu, Nginx, PostgreSQL
- [Data Migration](docs/MIGRATION.md) - Migrate from PHP/MySQL

### Quick Deploy Commands

```bash
# Install dependencies
pip install -r requirements.txt gunicorn

# Run with Gunicorn
gunicorn main:app -w 4 -k uvicorn.workers.UvicornWorker --bind 0.0.0.0:8000
```

## 🔧 Tech Stack

### Backend
- **FastAPI** - Modern async web framework
- **SQLAlchemy** - ORM for database
- **SQLite/PostgreSQL** - Database
- **Pydantic** - Data validation
- **JWT** - Authentication
- **Uvicorn** - ASGI server

### Frontend
- **HTML5/CSS3** - Structure and styling
- **Vanilla JavaScript** - Terminal effects
- **AdminLTE3** - Admin panel theme

## 📝 Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `ENVIRONMENT` | Environment mode | `development` |
| `DATABASE_URL` | Database connection string | `sqlite:///./serein.db` |
| `SECRET_KEY` | JWT secret key | (required) |
| `ALLOWED_ORIGINS` | CORS allowed origins | `http://localhost:3000,...` |
| `UPLOAD_FOLDER` | Upload directory | `uploads` |

## 👤 Author

**Nguyen Thanh Dat**  
Email: ngthanhdat.fudn@gmail.com  
GitHub: [@datyuno1409](https://github.com/datyuno1409)

## 📄 License

MIT License

---

**Made with ❤️ using FastAPI and Python**