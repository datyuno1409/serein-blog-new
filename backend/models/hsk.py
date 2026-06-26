"""
HSK Learning models — vocabulary, exam questions, and reading lessons.
"""
from sqlalchemy import Column, DateTime, Integer, JSON, String, Text
from sqlalchemy.sql import func

from backend.database import Base


class HSKVocabulary(Base):
    __tablename__ = "hsk_vocabulary"

    id = Column(Integer, primary_key=True, index=True)
    source = Column(String(50), nullable=False, default="seed")
    hsk_level = Column(Integer, nullable=False, index=True)
    chinese = Column(String(100), nullable=False)
    pinyin = Column(String(200), nullable=False)
    vietnamese = Column(Text)
    english = Column(Text)
    example_sentences = Column(JSON)
    audio_url = Column(String(500))
    stroke_order = Column(String(500))
    created_at = Column(DateTime(timezone=True), server_default=func.now())


class HSKExamQuestion(Base):
    __tablename__ = "hsk_exam_questions"

    id = Column(Integer, primary_key=True, index=True)
    source = Column(String(50), nullable=False, default="seed")
    hsk_level = Column(Integer, nullable=False, index=True)
    exam_code = Column(String(20))
    skill_type = Column(String(20), nullable=False)
    question_type = Column(String(50))
    question_text = Column(Text, nullable=False)
    options = Column(JSON)
    correct_answer = Column(String(10))
    explanation = Column(Text)
    audio_url = Column(String(500))
    image_url = Column(String(500))
    created_at = Column(DateTime(timezone=True), server_default=func.now())


class HSKReadingLesson(Base):
    __tablename__ = "hsk_reading_lessons"

    id = Column(Integer, primary_key=True, index=True)
    source = Column(String(50), nullable=False, default="seed")
    hsk_level = Column(Integer, nullable=False, index=True)
    title = Column(String(500), nullable=False)
    content = Column(Text, nullable=False)
    translation = Column(Text)
    vocabulary_list = Column(JSON)
    audio_url = Column(String(500))
    url = Column(String(500))
    created_at = Column(DateTime(timezone=True), server_default=func.now())
