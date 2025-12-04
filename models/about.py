from sqlalchemy import Column, Integer, String, Text, DateTime
from sqlalchemy.sql import func
from database import Base


class About(Base):
    """About model for personal information"""
    __tablename__ = "about"
    
    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(100), nullable=False)
    title = Column(String(255), nullable=False)
    bio = Column(Text, nullable=False)
    email = Column(String(100))
    phone = Column(String(20))
    location = Column(String(100))
    avatar = Column(String(255))
    resume_url = Column(String(255))
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    def __repr__(self):
        return f"<About {self.name}>"
