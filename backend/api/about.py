"""
About API endpoints
"""
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List

from ..database import get_db
from ..models.about import About
from ..models.user import User
from ..schemas import AboutCreate, AboutUpdate, AboutResponse
from ..auth import get_current_admin_user

router = APIRouter()


@router.get("/", response_model=List[AboutResponse])
async def get_about_info(db: Session = Depends(get_db)):
    """Get all about information"""
    about = db.query(About).all()
    return about


@router.get("/{about_id}", response_model=AboutResponse)
async def get_about_by_id(about_id: int, db: Session = Depends(get_db)):
    """Get about info by ID"""
    about = db.query(About).filter(About.id == about_id).first()
    if not about:
        raise HTTPException(status_code=404, detail="About info not found")
    return about


@router.post("/", response_model=AboutResponse, status_code=status.HTTP_201_CREATED)
async def create_about(
    about_data: AboutCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_admin_user)
):
    """Create about information (admin only)"""
    new_about = About(**about_data.model_dump())
    
    db.add(new_about)
    db.commit()
    db.refresh(new_about)
    
    return new_about


@router.put("/{about_id}", response_model=AboutResponse)
async def update_about(
    about_id: int,
    about_data: AboutUpdate,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_admin_user)
):
    """Update about information (admin only)"""
    about = db.query(About).filter(About.id == about_id).first()
    if not about:
        raise HTTPException(status_code=404, detail="About info not found")
    
    update_data = about_data.model_dump(exclude_unset=True)
    for field, value in update_data.items():
        setattr(about, field, value)
    
    db.commit()
    db.refresh(about)
    
    return about


@router.delete("/{about_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_about(
    about_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_admin_user)
):
    """Delete about information (admin only)"""
    about = db.query(About).filter(About.id == about_id).first()
    if not about:
        raise HTTPException(status_code=404, detail="About info not found")
    
    db.delete(about)
    db.commit()
    
    return None
