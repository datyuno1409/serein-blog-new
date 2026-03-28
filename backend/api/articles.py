"""
Articles API endpoints
"""
from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session
from typing import List, Optional

from ..database import get_db
from ..models.article import Article
from ..models.user import User
from ..schemas import ArticleCreate, ArticleUpdate, ArticleResponse
from ..auth import get_current_admin_user

router = APIRouter()


@router.get("/", response_model=List[ArticleResponse])
async def get_articles(
    skip: int = Query(0, ge=0),
    limit: int = Query(10, ge=1, le=100),
    status: Optional[str] = None,
    db: Session = Depends(get_db)
):
    """Get all articles with pagination"""
    query = db.query(Article)
    
    if status:
        query = query.filter(Article.status == status)
    
    articles = query.order_by(Article.created_at.desc()).offset(skip).limit(limit).all()
    return articles


@router.get("/{article_id}", response_model=ArticleResponse)
async def get_article(article_id: int, db: Session = Depends(get_db)):
    """Get article by ID"""
    article = db.query(Article).filter(Article.id == article_id).first()
    if not article:
        raise HTTPException(status_code=404, detail="Article not found")
    return article


@router.get("/slug/{slug}", response_model=ArticleResponse)
async def get_article_by_slug(slug: str, db: Session = Depends(get_db)):
    """Get article by slug"""
    article = db.query(Article).filter(Article.slug == slug).first()
    if not article:
        raise HTTPException(status_code=404, detail="Article not found")
    return article


@router.post("/", response_model=ArticleResponse, status_code=status.HTTP_201_CREATED)
async def create_article(
    article_data: ArticleCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_admin_user)
):
    """Create new article (admin only)"""
    # Generate slug if not provided
    if not article_data.slug:
        slug = Article.generate_slug(article_data.title)
    else:
        slug = article_data.slug
    
    # Check if slug exists
    existing = db.query(Article).filter(Article.slug == slug).first()
    if existing:
        raise HTTPException(status_code=400, detail="Slug already exists")
    
    new_article = Article(
        title=article_data.title,
        slug=slug,
        content=article_data.content,
        excerpt=article_data.excerpt,
        status=article_data.status
    )
    
    db.add(new_article)
    db.commit()
    db.refresh(new_article)
    
    return new_article


@router.put("/{article_id}", response_model=ArticleResponse)
async def update_article(
    article_id: int,
    article_data: ArticleUpdate,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_admin_user)
):
    """Update article (admin only)"""
    article = db.query(Article).filter(Article.id == article_id).first()
    if not article:
        raise HTTPException(status_code=404, detail="Article not found")
    
    # Update fields
    if article_data.title is not None:
        article.title = article_data.title
    if article_data.content is not None:
        article.content = article_data.content
    if article_data.excerpt is not None:
        article.excerpt = article_data.excerpt
    if article_data.status is not None:
        article.status = article_data.status
    if article_data.slug is not None:
        # Check if new slug exists
        existing = db.query(Article).filter(
            Article.slug == article_data.slug,
            Article.id != article_id
        ).first()
        if existing:
            raise HTTPException(status_code=400, detail="Slug already exists")
        article.slug = article_data.slug
    
    db.commit()
    db.refresh(article)
    
    return article


@router.delete("/{article_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_article(
    article_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_admin_user)
):
    """Delete article (admin only)"""
    article = db.query(Article).filter(Article.id == article_id).first()
    if not article:
        raise HTTPException(status_code=404, detail="Article not found")
    
    db.delete(article)
    db.commit()
    
    return None
