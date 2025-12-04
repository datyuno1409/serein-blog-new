from sqlalchemy import Column, Integer, String, Text, DateTime, Boolean, JSON, Enum
from sqlalchemy.sql import func
from database import Base
import enum


class ProjectStatus(str, enum.Enum):
    """Project status enumeration"""
    ACTIVE = "active"
    COMPLETED = "completed"
    ARCHIVED = "archived"


class Project(Base):
    """Project model for portfolio items"""
    __tablename__ = "projects"
    
    id = Column(Integer, primary_key=True, index=True)
    title = Column(String(255), nullable=False)
    description = Column(Text, nullable=False)
    short_description = Column(String(500))
    technologies = Column(JSON)  # Array of technology names
    project_url = Column(String(255))
    github_url = Column(String(255))
    featured_image = Column(String(255))
    gallery_images = Column(JSON)  # Array of image URLs
    status = Column(Enum(ProjectStatus), default=ProjectStatus.ACTIVE, nullable=False)
    featured = Column(Boolean, default=False)
    sort_order = Column(Integer, default=0)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    def __repr__(self):
        return f"<Project {self.title}>"
