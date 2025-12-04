"""
API package initialization
"""
from fastapi import APIRouter

# Create main API router
api_router = APIRouter(prefix="/api")

# Import and include sub-routers
from .auth import router as auth_router
from .articles import router as articles_router
from .projects import router as projects_router
from .about import router as about_router
from .skills import router as skills_router
from .social_links import router as social_links_router
from .seo_settings import router as seo_router
from .settings import router as settings_router

# Include all routers
api_router.include_router(auth_router, prefix="/auth", tags=["Authentication"])
api_router.include_router(articles_router, prefix="/articles", tags=["Articles"])
api_router.include_router(projects_router, prefix="/projects", tags=["Projects"])
api_router.include_router(about_router, prefix="/about", tags=["About"])
api_router.include_router(skills_router, prefix="/skills", tags=["Skills"])
api_router.include_router(social_links_router, prefix="/social-links", tags=["Social Links"])
api_router.include_router(seo_router, prefix="/seo", tags=["SEO"])
api_router.include_router(settings_router, prefix="/settings", tags=["Settings"])
