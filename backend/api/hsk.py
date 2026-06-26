"""
HSK Chinese Learning API routes.
"""
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from typing import List, Optional

from ..database import get_db
from ..models.hsk import HSKVocabulary, HSKExamQuestion, HSKReadingLesson
from ..schemas import (
    HSKVocabularyResponse,
    HSKVocabularySearch,
    HSKExamResponse,
    HSKExamDetailResponse,
    HSKReadingResponse,
)

router = APIRouter()


# ============= Vocabulary =============
@router.get("/vocabulary", response_model=List[HSKVocabularyResponse])
async def get_hsk_vocabulary(
    hsk_level: Optional[int] = Query(None, ge=1, le=6),
    source: Optional[str] = Query(None),
    search: Optional[str] = None,
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
):
    query = db.query(HSKVocabulary)
    if hsk_level:
        query = query.filter(HSKVocabulary.hsk_level == hsk_level)
    if source:
        query = query.filter(HSKVocabulary.source == source)
    if search:
        query = query.filter(
            (HSKVocabulary.chinese.contains(search))
            | (HSKVocabulary.pinyin.contains(search))
            | (HSKVocabulary.vietnamese.contains(search))
        )
    return query.offset(skip).limit(limit).all()


@router.get("/vocabulary/{vocab_id}", response_model=HSKVocabularyResponse)
async def get_hsk_vocabulary_detail(vocab_id: int, db: Session = Depends(get_db)):
    vocab = db.query(HSKVocabulary).filter(HSKVocabulary.id == vocab_id).first()
    if not vocab:
        raise HTTPException(status_code=404, detail="Không tìm thấy từ vựng")
    return vocab


@router.post("/vocabulary/search")
async def search_hsk_vocabulary(payload: HSKVocabularySearch, db: Session = Depends(get_db)):
    query = db.query(HSKVocabulary)
    if payload.hsk_level:
        query = query.filter(HSKVocabulary.hsk_level == payload.hsk_level)
    results = query.filter(
        (HSKVocabulary.chinese.contains(payload.keyword))
        | (HSKVocabulary.pinyin.contains(payload.keyword))
        | (HSKVocabulary.vietnamese.contains(payload.keyword))
    ).all()
    return results


# ============= Exams =============
@router.get("/exams", response_model=List[HSKExamResponse])
async def get_hsk_exams(
    hsk_level: Optional[int] = Query(None, ge=1, le=6),
    skill_type: Optional[str] = Query(None),
    source: Optional[str] = None,
    db: Session = Depends(get_db),
):
    query = db.query(HSKExamQuestion)
    if hsk_level:
        query = query.filter(HSKExamQuestion.hsk_level == hsk_level)
    if skill_type:
        query = query.filter(HSKExamQuestion.skill_type == skill_type)
    if source:
        query = query.filter(HSKExamQuestion.source == source)
    return query.all()


@router.get("/exams/{exam_id}", response_model=HSKExamDetailResponse)
async def get_hsk_exam_detail(exam_id: int, db: Session = Depends(get_db)):
    exam = db.query(HSKExamQuestion).filter(HSKExamQuestion.id == exam_id).first()
    if not exam:
        raise HTTPException(status_code=404, detail="Không tìm thấy câu hỏi thi")
    return exam


# ============= Reading =============
@router.get("/reading", response_model=List[HSKReadingResponse])
async def get_hsk_reading_lessons(
    hsk_level: Optional[int] = None,
    source: Optional[str] = None,
    db: Session = Depends(get_db),
):
    query = db.query(HSKReadingLesson)
    if hsk_level:
        query = query.filter(HSKReadingLesson.hsk_level == hsk_level)
    if source:
        query = query.filter(HSKReadingLesson.source == source)
    return query.all()


# ============= Stats =============
@router.get("/stats")
async def get_hsk_stats(db: Session = Depends(get_db)):
    return {
        "vocabulary": {
            "total": db.query(HSKVocabulary).count(),
            "by_level": {
                f"hsk_{i}": db.query(HSKVocabulary).filter(HSKVocabulary.hsk_level == i).count()
                for i in range(1, 7)
            },
        },
        "exam_questions": db.query(HSKExamQuestion).count(),
        "reading_lessons": db.query(HSKReadingLesson).count(),
    }


# ============= Admin Scrape =============
@router.post("/admin/scrape/xiehanzi")
async def scrape_xiehanzi(hsk_level: int = Query(1, ge=1, le=6)):
    from ..scrapers.xiehanzi_scraper import XieHanziScraper

    scraper = XieHanziScraper()
    data = scraper.scrape_hsk_vocabulary(hsk_level)
    return {"message": f"Scraped {len(data)} items from XieHanzi HSK {hsk_level}", "data": data[:5]}


@router.post("/admin/scrape/mandarinbean")
async def scrape_mandarinbean(hsk_level: int = Query(1, ge=1, le=6)):
    from ..scrapers.mandarinbean_scraper import MandarinBeanScraper

    scraper = MandarinBeanScraper()
    vocab = scraper.scrape_vocabulary_list(hsk_level)
    return {"vocabulary_count": len(vocab), "sample": vocab[:5]}


@router.post("/admin/scrape/hihsk")
async def scrape_hihsk(level: int = Query(4, ge=1, le=6)):
    from ..scrapers.hihsk_scraper import HiHSKScraper

    scraper = HiHSKScraper()
    exams = scraper.scrape_exam_list(level)
    return {"exams_count": len(exams), "exams": exams[:3]}
