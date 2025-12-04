from sqlalchemy import Column, Integer, String, DateTime
from sqlalchemy.sql import func
from backend.database import Base


class SocialLink(Base):
    """Social link model for social media profiles"""
    __tablename__ = "social_links"
    
    id = Column(Integer, primary_key=True, index=True)
    platform = Column(String(50), nullable=False)  # e.g., "GitHub", "LinkedIn"
    url = Column(String(255), nullable=False)
    icon = Column(String(100))  # Font Awesome icon class
    sort_order = Column(Integer, default=0)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    def __repr__(self):
        return f"<SocialLink {self.platform}>"
