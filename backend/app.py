"""
Main FastAPI Application
Serein Blog Platform - Python/FastAPI Version
"""
from fastapi import FastAPI, Request, Depends
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import HTMLResponse
from sqlalchemy.orm import Session

from .config import settings
from .database import engine, get_db, Base
from .api import api_router
from . import models  # Import all models to create tables

# Create database tables
Base.metadata.create_all(bind=engine)

# Initialize FastAPI app
app = FastAPI(
    title="Serein Blog Platform API",
    description="Modern blog platform with terminal-style UI",
    version="2.0.0",
    docs_url="/api/docs",
    redoc_url="/api/redoc"
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.allowed_origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Static files
app.mount("/assets", StaticFiles(directory="frontend/assets"), name="assets")
app.mount("/admin/css", StaticFiles(directory="admin/css"), name="admin_css")
app.mount("/admin/js", StaticFiles(directory="admin/js"), name="admin_js")

# Templates
templates = Jinja2Templates(directory="frontend/templates")

# Include API router
app.include_router(api_router)


# ============= Frontend Routes =============
@app.get("/", response_class=HTMLResponse)
async def home(request: Request, db: Session = Depends(get_db)):
    """Homepage"""
    return templates.TemplateResponse("index.html", {"request": request})


@app.get("/home", response_class=HTMLResponse)
async def home_alias(request: Request):
    """Homepage alias"""
    return templates.TemplateResponse("index.html", {"request": request})


@app.get("/about", response_class=HTMLResponse)
async def about(request: Request):
    """About page"""
    return templates.TemplateResponse("about.html", {"request": request})


@app.get("/portfolio", response_class=HTMLResponse)
async def portfolio(request: Request):
    """Portfolio/Services page"""
    return templates.TemplateResponse("services.html", {"request": request})


@app.get("/blog", response_class=HTMLResponse)
async def blog(request: Request):
    """Blog listing page"""
    return templates.TemplateResponse("blog.html", {"request": request})


@app.get("/post", response_class=HTMLResponse)
async def post(request: Request):
    """Single post page"""
    return templates.TemplateResponse("post.html", {"request": request})


# ============= Admin Routes =============
@app.get("/admin", response_class=HTMLResponse)
async def admin_login(request: Request):
    """Admin login page"""
    return templates.TemplateResponse("admin/index.html", {"request": request})


@app.get("/admin/dashboard", response_class=HTMLResponse)
async def admin_dashboard(request: Request):
    """Admin dashboard"""
    return templates.TemplateResponse("admin/dashboard.html", {"request": request})


@app.get("/admin/articles", response_class=HTMLResponse)
async def admin_articles(request: Request):
    """Admin articles management"""
    return templates.TemplateResponse("admin/articles.html", {"request": request})


@app.get("/admin/projects", response_class=HTMLResponse)
async def admin_projects(request: Request):
    """Admin projects management"""
    return templates.TemplateResponse("admin/projects.html", {"request": request})


@app.get("/admin/settings", response_class=HTMLResponse)
async def admin_settings(request: Request):
    """Admin settings"""
    return templates.TemplateResponse("admin/settings.html", {"request": request})


# ============= Health Check =============
@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "version": "2.0.0",
        "framework": "FastAPI",
        "database": "PostgreSQL"
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "app:app",
        host="0.0.0.0",
        port=8000,
        reload=settings.environment == "development"
    )
