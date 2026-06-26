from sqlalchemy import Column, Integer, String, DateTime, Enum, Text, Boolean
from sqlalchemy.sql import func
from sqlalchemy.orm import relationship
from backend.database import Base
import bcrypt
import enum


# bcrypt operates on at most 72 bytes; longer secrets must be truncated.
_BCRYPT_MAX_BYTES = 72


def _truncate(password: str) -> bytes:
    return password.encode("utf-8")[:_BCRYPT_MAX_BYTES]


class UserRole(str, enum.Enum):
    """User role enumeration"""
    ADMIN = "admin"
    EDITOR = "editor"
    LEARNER = "learner"
    VIEWER = "viewer"


class User(Base):
    """User model for authentication and authorization"""
    __tablename__ = "users"

    id = Column(Integer, primary_key=True, index=True)
    username = Column(String(50), unique=True, nullable=False, index=True)
    email = Column(String(255), unique=True, nullable=False, index=True)
    password_hash = Column(String(255), nullable=False)
    full_name = Column(String(120), nullable=False, default="")
    bio = Column(Text)
    learning_goal = Column(String(255))
    avatar_url = Column(String(255))
    weekly_hours = Column(Integer, default=1)
    role = Column(Enum(UserRole), default=UserRole.LEARNER, nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    # Learning relationships
    learning_paths = relationship("LearningPath", back_populates="user", cascade="all, delete-orphan")
    course_progress = relationship("UserCourseProgress", back_populates="user", cascade="all, delete-orphan")
    lesson_progress = relationship("UserLessonProgress", back_populates="user", cascade="all, delete-orphan")

    def verify_password(self, password: str) -> bool:
        if not self.password_hash:
            return False
        try:
            return bcrypt.checkpw(_truncate(password), self.password_hash.encode("utf-8"))
        except ValueError:
            return False

    @staticmethod
    def hash_password(password: str) -> str:
        return bcrypt.hashpw(_truncate(password), bcrypt.gensalt()).decode("utf-8")

    def __repr__(self):
        return f"<User {self.username}>"
