"""
Social Links API endpoints
"""
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List

from ..database import get_db
from ..models.social_link import SocialLink
from ..models.user import User
from ..schemas import SocialLinkCreate, SocialLinkUpdate, SocialLinkResponse
from ..auth import get_current_admin_user

router = APIRouter()


@router.get("/", response_model=List[SocialLinkResponse])
async def get_social_links(db: Session = Depends(get_db)):
    """Get all social links"""
    links = db.query(SocialLink).order_by(SocialLink.sort_order).all()
    return links


@router.get("/{link_id}", response_model=SocialLinkResponse)
async def get_social_link(link_id: int, db: Session = Depends(get_db)):
    """Get social link by ID"""
    link = db.query(SocialLink).filter(SocialLink.id == link_id).first()
    if not link:
        raise HTTPException(status_code=404, detail="Social link not found")
    return link


@router.post("/", response_model=SocialLinkResponse, status_code=status.HTTP_201_CREATED)
async def create_social_link(
    link_data: SocialLinkCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_admin_user)
):
    """Create new social link (admin only)"""
    new_link = SocialLink(**link_data.model_dump())
    
    db.add(new_link)
    db.commit()
    db.refresh(new_link)
    
    return new_link


@router.put("/{link_id}", response_model=SocialLinkResponse)
async def update_social_link(
    link_id: int,
    link_data: SocialLinkUpdate,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_admin_user)
):
    """Update social link (admin only)"""
    link = db.query(SocialLink).filter(SocialLink.id == link_id).first()
    if not link:
        raise HTTPException(status_code=404, detail="Social link not found")
    
    update_data = link_data.model_dump(exclude_unset=True)
    for field, value in update_data.items():
        setattr(link, field, value)
    
    db.commit()
    db.refresh(link)
    
    return link


@router.delete("/{link_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_social_link(
    link_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_admin_user)
):
    """Delete social link (admin only)"""
    link = db.query(SocialLink).filter(SocialLink.id == link_id).first()
    if not link:
        raise HTTPException(status_code=404, detail="Social link not found")
    
    db.delete(link)
    db.commit()
    
    return None
