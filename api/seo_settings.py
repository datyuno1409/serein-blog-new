"""
SEO Settings API endpoints
"""
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List

from database import get_db
from models.seo_setting import SEOSetting
from models.user import User
from schemas import SEOSettingCreate, SEOSettingUpdate, SEOSettingResponse
from auth import get_current_admin_user

router = APIRouter()


@router.get("/", response_model=List[SEOSettingResponse])
async def get_seo_settings(db: Session = Depends(get_db)):
    """Get all SEO settings"""
    settings = db.query(SEOSetting).all()
    return settings


@router.get("/page/{page_name}", response_model=SEOSettingResponse)
async def get_seo_by_page(page_name: str, db: Session = Depends(get_db)):
    """Get SEO settings by page name"""
    seo = db.query(SEOSetting).filter(SEOSetting.page == page_name).first()
    if not seo:
        raise HTTPException(status_code=404, detail="SEO settings not found for this page")
    return seo


@router.post("/", response_model=SEOSettingResponse, status_code=status.HTTP_201_CREATED)
async def create_seo_setting(
    seo_data: SEOSettingCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_admin_user)
):
    """Create SEO settings (admin only)"""
    # Check if page already exists
    existing = db.query(SEOSetting).filter(SEOSetting.page == seo_data.page).first()
    if existing:
        raise HTTPException(status_code=400, detail="SEO settings for this page already exist")
    
    new_seo = SEOSetting(**seo_data.model_dump())
    
    db.add(new_seo)
    db.commit()
    db.refresh(new_seo)
    
    return new_seo


@router.put("/{seo_id}", response_model=SEOSettingResponse)
async def update_seo_setting(
    seo_id: int,
    seo_data: SEOSettingUpdate,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_admin_user)
):
    """Update SEO settings (admin only)"""
    seo = db.query(SEOSetting).filter(SEOSetting.id == seo_id).first()
    if not seo:
        raise HTTPException(status_code=404, detail="SEO settings not found")
    
    update_data = seo_data.model_dump(exclude_unset=True)
    for field, value in update_data.items():
        setattr(seo, field, value)
    
    db.commit()
    db.refresh(seo)
    
    return seo


@router.delete("/{seo_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_seo_setting(
    seo_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_admin_user)
):
    """Delete SEO settings (admin only)"""
    seo = db.query(SEOSetting).filter(SEOSetting.id == seo_id).first()
    if not seo:
        raise HTTPException(status_code=404, detail="SEO settings not found")
    
    db.delete(seo)
    db.commit()
    
    return None
