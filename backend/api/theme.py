from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from typing import List, Dict, Any
from pydantic import BaseModel
from backend.database import get_db
from backend.models.theme_settings import ThemeSettings
from backend.models.user import User
from backend.auth import get_current_user
import json

router = APIRouter(prefix="/api/theme", tags=["theme"])


class ThemeSettingCreate(BaseModel):
    key: str
    value: str
    category: str


class ThemeSettingResponse(BaseModel):
    id: int
    key: str
    value: str
    category: str
    
    class Config:
        from_attributes = True


@router.get("/", response_model=Dict[str, Any])
async def get_all_theme_settings(db: Session = Depends(get_db)):
    """Get all theme settings grouped by category"""
    settings = db.query(ThemeSettings).all()
    
    result = {
        "navigation": {},
        "colors": {},
        "typography": {},
        "layout": {},
        "content": {}
    }
    
    for setting in settings:
        try:
            # Try to parse JSON values
            value = json.loads(setting.value)
        except:
            value = setting.value
        
        if setting.category in result:
            result[setting.category][setting.key] = value
        else:
            result[setting.category] = {setting.key: value}
    
    return result


@router.get("/navigation")
async def get_navigation_menu(db: Session = Depends(get_db)):
    """Get navigation menu items"""
    nav_setting = db.query(ThemeSettings).filter(
        ThemeSettings.key == "menu_items"
    ).first()
    
    if nav_setting:
        return json.loads(nav_setting.value)
    
    # Default menu
    return [
        {"text": "Home", "url": "/", "active": False},
        {"text": "About", "url": "/about", "active": False},
        {"text": "Portfolio", "url": "/portfolio", "active": False},
        {"text": "Blog", "url": "/blog", "active": False}
    ]


@router.post("/navigation")
async def update_navigation_menu(
    menu_items: List[Dict[str, Any]],
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Update navigation menu items"""
    nav_setting = db.query(ThemeSettings).filter(
        ThemeSettings.key == "menu_items"
    ).first()
    
    menu_json = json.dumps(menu_items)
    
    if nav_setting:
        nav_setting.value = menu_json
    else:
        nav_setting = ThemeSettings(
            key="menu_items",
            value=menu_json,
            category="navigation"
        )
        db.add(nav_setting)
    
    db.commit()
    db.refresh(nav_setting)
    
    return {"success": True, "message": "Navigation menu updated"}


@router.post("/")
async def update_theme_settings(
    settings: Dict[str, Any],
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Update multiple theme settings at once"""
    for category, values in settings.items():
        if not isinstance(values, dict):
            continue
            
        for key, value in values.items():
            setting_key = f"{category}_{key}"
            
            existing = db.query(ThemeSettings).filter(
                ThemeSettings.key == setting_key
            ).first()
            
            value_str = json.dumps(value) if isinstance(value, (dict, list)) else str(value)
            
            if existing:
                existing.value = value_str
                existing.category = category
            else:
                new_setting = ThemeSettings(
                    key=setting_key,
                    value=value_str,
                    category=category
                )
                db.add(new_setting)
    
    db.commit()
    return {"success": True, "message": "Theme settings updated"}


@router.get("/colors")
async def get_color_scheme(db: Session = Depends(get_db)):
    """Get color scheme"""
    colors = {}
    color_settings = db.query(ThemeSettings).filter(
        ThemeSettings.category == "colors"
    ).all()
    
    for setting in color_settings:
        key = setting.key.replace("colors_", "")
        colors[key] = setting.value
    
    # Defaults
    if not colors:
        colors = {
            "primary": "#00ff88",
            "secondary": "#000000",
            "text": "#ffffff",
            "background": "#000000"
        }
    
    return colors


@router.delete("/{key}")
async def delete_theme_setting(
    key: str,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Delete a theme setting"""
    setting = db.query(ThemeSettings).filter(ThemeSettings.key == key).first()
    
    if not setting:
        raise HTTPException(status_code=404, detail="Setting not found")
    
    db.delete(setting)
    db.commit()
    
    return {"success": True, "message": "Setting deleted"}
