"""
Pydantic schemas for request/response validation
"""
from pydantic import BaseModel, EmailStr, Field, validator
from typing import Optional, List
from datetime import datetime
from enum import Enum


# ============= User Schemas =============
class UserRole(str, Enum):
    ADMIN = "admin"
    EDITOR = "editor"
    VIEWER = "viewer"


class UserBase(BaseModel):
    username: str = Field(..., min_length=3, max_length=50)
    role: UserRole = UserRole.ADMIN


class UserCreate(UserBase):
    password: str = Field(..., min_length=6)


class UserLogin(BaseModel):
    username: str
    password: str


class UserResponse(UserBase):
    id: int
    created_at: datetime
    
    class Config:
        from_attributes = True


class Token(BaseModel):
    access_token: str
    token_type: str = "bearer"


# ============= Article Schemas =============
class ArticleStatus(str, Enum):
    PUBLISHED = "published"
    DRAFT = "draft"


class ArticleBase(BaseModel):
    title: str = Field(..., max_length=255)
    content: str
    excerpt: Optional[str] = None
    status: ArticleStatus = ArticleStatus.DRAFT


class ArticleCreate(ArticleBase):
    slug: Optional[str] = None


class ArticleUpdate(ArticleBase):
    title: Optional[str] = None
    content: Optional[str] = None
    slug: Optional[str] = None


class ArticleResponse(ArticleBase):
    id: int
    slug: str
    created_at: datetime
    updated_at: Optional[datetime]
    
    class Config:
        from_attributes = True


# ============= Project Schemas =============
class ProjectStatus(str, Enum):
    ACTIVE = "active"
    COMPLETED = "completed"
    ARCHIVED = "archived"


class ProjectBase(BaseModel):
    title: str = Field(..., max_length=255)
    description: str
    short_description: Optional[str] = Field(None, max_length=500)
    technologies: Optional[List[str]] = []
    project_url: Optional[str] = None
    github_url: Optional[str] = None
    featured_image: Optional[str] = None
    gallery_images: Optional[List[str]] = []
    status: ProjectStatus = ProjectStatus.ACTIVE
    featured: bool = False
    sort_order: int = 0


class ProjectCreate(ProjectBase):
    pass


class ProjectUpdate(ProjectBase):
    title: Optional[str] = None
    description: Optional[str] = None


class ProjectResponse(ProjectBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime]
    
    class Config:
        from_attributes = True


# ============= About Schemas =============
class AboutBase(BaseModel):
    name: str = Field(..., max_length=100)
    title: str = Field(..., max_length=255)
    bio: str
    email: Optional[str] = None
    phone: Optional[str] = None
    location: Optional[str] = None
    avatar: Optional[str] = None
    resume_url: Optional[str] = None


class AboutCreate(AboutBase):
    pass


class AboutUpdate(AboutBase):
    name: Optional[str] = None
    title: Optional[str] = None
    bio: Optional[str] = None


class AboutResponse(AboutBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime]
    
    class Config:
        from_attributes = True


# ============= Skill Schemas =============
class SkillBase(BaseModel):
    name: str = Field(..., max_length=100)
    category: Optional[str] = None
    proficiency: int = Field(50, ge=0, le=100)
    icon: Optional[str] = None
    sort_order: int = 0


class SkillCreate(SkillBase):
    pass


class SkillUpdate(SkillBase):
    name: Optional[str] = None


class SkillResponse(SkillBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime]
    
    class Config:
        from_attributes = True


# ============= Social Link Schemas =============
class SocialLinkBase(BaseModel):
    platform: str = Field(..., max_length=50)
    url: str = Field(..., max_length=255)
    icon: Optional[str] = None
    sort_order: int = 0


class SocialLinkCreate(SocialLinkBase):
    pass


class SocialLinkUpdate(SocialLinkBase):
    platform: Optional[str] = None
    url: Optional[str] = None


class SocialLinkResponse(SocialLinkBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime]
    
    class Config:
        from_attributes = True


# ============= SEO Setting Schemas =============
class SEOSettingBase(BaseModel):
    page: str = Field(..., max_length=50)
    title: str = Field(..., max_length=255)
    description: Optional[str] = None
    keywords: Optional[str] = None
    og_image: Optional[str] = None
    og_title: Optional[str] = None
    og_description: Optional[str] = None


class SEOSettingCreate(SEOSettingBase):
    pass


class SEOSettingUpdate(SEOSettingBase):
    page: Optional[str] = None
    title: Optional[str] = None


class SEOSettingResponse(SEOSettingBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime]
    
    class Config:
        from_attributes = True


# ============= Setting Schemas =============
class SettingBase(BaseModel):
    key: str = Field(..., max_length=100)
    value: Optional[str] = None
    description: Optional[str] = None


class SettingCreate(SettingBase):
    pass


class SettingUpdate(BaseModel):
    value: Optional[str] = None
    description: Optional[str] = None


class SettingResponse(SettingBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime]
    
    class Config:
        from_attributes = True
