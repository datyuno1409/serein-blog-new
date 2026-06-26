from sqlalchemy import Boolean, Column, DateTime, ForeignKey, Integer, JSON, String, Text
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func

from backend.database import Base


class LearningPath(Base):
    __tablename__ = "learning_paths"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    title = Column(String(255), nullable=False)
    target_role = Column(String(120), nullable=False)
    focus_area = Column(String(120), nullable=False)
    pace = Column(String(80), nullable=False)
    weekly_hours = Column(Integer, nullable=False)
    description = Column(Text)
    active = Column(Boolean, default=True, nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    user = relationship("User", back_populates="learning_paths")
    courses = relationship("Course", back_populates="learning_path", cascade="all, delete-orphan")
    roadmap_steps = relationship("RoadmapStep", back_populates="learning_path", cascade="all, delete-orphan")


class Course(Base):
    __tablename__ = "courses"

    id = Column(Integer, primary_key=True, index=True)
    learning_path_id = Column(Integer, ForeignKey("learning_paths.id"), nullable=False, index=True)
    title = Column(String(255), nullable=False)
    slug = Column(String(255), nullable=False, unique=True, index=True)
    category = Column(String(80), nullable=False)
    language = Column(String(80), nullable=False)
    level = Column(String(80), nullable=False)
    summary = Column(Text, nullable=False)
    estimated_hours = Column(Integer, default=0, nullable=False)
    tags = Column(JSON, default=list)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    learning_path = relationship("LearningPath", back_populates="courses")
    lessons = relationship("Lesson", back_populates="course", cascade="all, delete-orphan")
    progress_entries = relationship("UserCourseProgress", back_populates="course", cascade="all, delete-orphan")


class Lesson(Base):
    __tablename__ = "lessons"

    id = Column(Integer, primary_key=True, index=True)
    course_id = Column(Integer, ForeignKey("courses.id"), nullable=False, index=True)
    title = Column(String(255), nullable=False)
    lesson_type = Column(String(80), nullable=False)
    content = Column(Text, nullable=False)
    order_index = Column(Integer, default=0, nullable=False)
    duration_minutes = Column(Integer, default=15, nullable=False)
    xp_reward = Column(Integer, default=20, nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    course = relationship("Course", back_populates="lessons")
    flashcards = relationship("Flashcard", back_populates="lesson", cascade="all, delete-orphan")
    quiz_questions = relationship("QuizQuestion", back_populates="lesson", cascade="all, delete-orphan")
    progress_entries = relationship("UserLessonProgress", back_populates="lesson", cascade="all, delete-orphan")

    @property
    def content_json(self):
        import json
        if self.lesson_type == "hanzi":
            try:
                data = json.loads(self.content)
                if isinstance(data, dict) and "hanzi" in data:
                    return data
            except Exception:
                pass
            return {"hanzi": ["你", "好", "学", "习", "中", "文", "大", "小", "人", "天", "日", "月"]}
        return None

    @property
    def description(self):
        import json
        if self.lesson_type == "hanzi":
            try:
                data = json.loads(self.content)
                if isinstance(data, dict) and "description" in data:
                    return data["description"]
            except Exception:
                pass
        return self.content


class Flashcard(Base):
    __tablename__ = "flashcards"

    id = Column(Integer, primary_key=True, index=True)
    lesson_id = Column(Integer, ForeignKey("lessons.id"), nullable=False, index=True)
    front_text = Column(Text, nullable=False)
    back_text = Column(Text, nullable=False)
    pronunciation = Column(String(255))
    example = Column(Text)
    created_at = Column(DateTime(timezone=True), server_default=func.now())

    lesson = relationship("Lesson", back_populates="flashcards")


class UserCourseProgress(Base):
    __tablename__ = "user_course_progress"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    course_id = Column(Integer, ForeignKey("courses.id"), nullable=False, index=True)
    completed_lessons = Column(Integer, default=0, nullable=False)
    total_lessons = Column(Integer, default=0, nullable=False)
    completion_rate = Column(Integer, default=0, nullable=False)
    current_streak = Column(Integer, default=0, nullable=False)
    last_activity = Column(DateTime(timezone=True), server_default=func.now())
    preferences = Column(JSON, default=dict)

    user = relationship("User", back_populates="course_progress")
    course = relationship("Course", back_populates="progress_entries")


class UserLessonProgress(Base):
    __tablename__ = "user_lesson_progress"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    lesson_id = Column(Integer, ForeignKey("lessons.id"), nullable=False, index=True)
    status = Column(String(40), default="not_started", nullable=False)
    notes = Column(Text)
    writing_submission = Column(Text)
    score = Column(Integer, default=0, nullable=False)
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())

    user = relationship("User", back_populates="lesson_progress")
    lesson = relationship("Lesson", back_populates="progress_entries")


class RoadmapStep(Base):
    __tablename__ = "roadmap_steps"

    id = Column(Integer, primary_key=True, index=True)
    learning_path_id = Column(Integer, ForeignKey("learning_paths.id"), nullable=False, index=True)
    title = Column(String(255), nullable=False)
    description = Column(Text, nullable=False)
    phase = Column(String(80), nullable=False)
    step_type = Column(String(80), nullable=False)
    estimated_days = Column(Integer, default=3, nullable=False)
    order_index = Column(Integer, default=0, nullable=False)
    is_completed = Column(Boolean, default=False, nullable=False)
    resource_hint = Column(String(255))
    step_metadata = Column(JSON, default=dict)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())

    learning_path = relationship("LearningPath", back_populates="roadmap_steps")


class QuizQuestion(Base):
    __tablename__ = "quiz_questions"

    id = Column(Integer, primary_key=True, index=True)
    lesson_id = Column(Integer, ForeignKey("lessons.id"), nullable=False, index=True)
    question_text = Column(Text, nullable=False)
    question_type = Column(String(40), nullable=False, default="multiple_choice")  # multiple_choice | fill_blank
    options = Column(JSON, default=list)  # ["A", "B", "C", "D"] for multiple_choice
    correct_answer = Column(String(255), nullable=False)
    explanation = Column(Text)
    order_index = Column(Integer, default=0, nullable=False)

    lesson = relationship("Lesson", back_populates="quiz_questions")
