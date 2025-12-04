from sqlalchemy import Column, Integer, String, Text, DateTime
from sqlalchemy.sql import func
from database import Base


class Testimonial(Base):
    """Testimonial model for client reviews"""
    __tablename__ = "testimonials"
    
    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(100), nullable=False)
    position = Column(String(100))
    company = Column(String(100))
    content = Column(Text, nullable=False)
    avatar = Column(String(255))
    rating = Column(Integer, default=5)  # 1-5 stars
    sort_order = Column(Integer, default=0)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    def __repr__(self):
        return f"<Testimonial {self.name}>"
