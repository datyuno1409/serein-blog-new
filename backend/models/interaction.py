from sqlalchemy import Column, Integer, String, Text, Boolean, DateTime, ForeignKey, JSON
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from backend.database import Base


class InterviewQuestion(Base):
    """Interview question bank model"""
    __tablename__ = "interview_questions"

    id = Column(Integer, primary_key=True, index=True)
    category = Column(String(100), nullable=False, index=True)
    question = Column(Text, nullable=False)
    answer = Column(Text, nullable=False)
    difficulty = Column(String(40), default="Medium", nullable=False)
    order_index = Column(Integer, default=0, nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())

    user_progress = relationship("UserInterviewProgress", back_populates="question", cascade="all, delete-orphan")


class UserInterviewProgress(Base):
    """User study progress tracking for interview prep"""
    __tablename__ = "user_interview_progress"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    question_id = Column(Integer, ForeignKey("interview_questions.id"), nullable=False, index=True)
    status = Column(String(40), default="review", nullable=False)  # "mastered", "review"
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())

    user = relationship("User")
    question = relationship("InterviewQuestion", back_populates="user_progress")


class QnaQuestion(Base):
    """Q&A discussion forum question model"""
    __tablename__ = "qna_questions"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    title = Column(String(255), nullable=False)
    content = Column(Text, nullable=False)
    tags = Column(String(255), default="")  # comma-separated tags
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())

    user = relationship("User")
    answers = relationship("QnaAnswer", back_populates="question", cascade="all, delete-orphan")


class QnaAnswer(Base):
    """Q&A discussion forum answer model"""
    __tablename__ = "qna_answers"

    id = Column(Integer, primary_key=True, index=True)
    question_id = Column(Integer, ForeignKey("qna_questions.id"), nullable=False, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    content = Column(Text, nullable=False)
    is_accepted = Column(Boolean, default=False, nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())

    user = relationship("User")
    question = relationship("QnaQuestion", back_populates="answers")


class VirtualExamSession(Base):
    """Persisted virtual exam attempt for learning tracks such as TOEIC, IELTS, HSK, and coding."""
    __tablename__ = "virtual_exam_sessions"

    id = Column(Integer, primary_key=True, index=True)
    session_uid = Column(String(80), nullable=False, unique=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    domain = Column(String(80), nullable=False, index=True)
    track = Column(String(80), nullable=False, index=True)
    target_level = Column(String(40), nullable=False, index=True)
    duration_minutes = Column(Integer, nullable=False)
    source_provider = Column(String(120), nullable=False)
    source_type = Column(String(80), nullable=False)
    source_url = Column(String(500))
    freshness = Column(String(120), nullable=False)
    questions = Column(JSON, default=list, nullable=False)
    answers = Column(JSON, default=dict, nullable=False)
    score = Column(Integer, default=0, nullable=False)
    total = Column(Integer, default=0, nullable=False)
    percentage = Column(Integer, default=0, nullable=False)
    status = Column(String(40), default="in_progress", nullable=False)
    started_at = Column(DateTime(timezone=True), server_default=func.now())
    submitted_at = Column(DateTime(timezone=True))
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())

    user = relationship("User")
