from sqlalchemy import Column, Integer, String, Text, DateTime
from sqlalchemy.sql import func
from backend.database import Base


class SEOSetting(Base):
    """SEO settings model for meta tags and SEO configuration"""
    __tablename__ = "seo_settings"
    
    id = Column(Integer, primary_key=True, index=True)
    page = Column(String(50), unique=True, nullable=False)  # e.g., "home", "about", "blog"
    title = Column(String(255), nullable=False)
    description = Column(Text)
    keywords = Column(Text)
    og_image = Column(String(255))
    og_title = Column(String(255))
    og_description = Column(Text)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    def __repr__(self):
        return f"<SEOSetting {self.page}>"
