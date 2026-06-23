from sqlalchemy import Column, Integer, String, DateTime, Enum
from sqlalchemy.sql import func
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
    VIEWER = "viewer"


class User(Base):
    """User model for authentication and authorization"""
    __tablename__ = "users"

    id = Column(Integer, primary_key=True, index=True)
    username = Column(String(50), unique=True, nullable=False, index=True)
    password_hash = Column(String(255), nullable=False)
    role = Column(Enum(UserRole), default=UserRole.ADMIN, nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    def verify_password(self, password: str) -> bool:
        """Verify a plaintext password against the stored bcrypt hash."""
        if not self.password_hash:
            return False
        try:
            return bcrypt.checkpw(_truncate(password), self.password_hash.encode("utf-8"))
        except ValueError:
            # Stored value is not a valid bcrypt hash
            return False

    @staticmethod
    def hash_password(password: str) -> str:
        """Hash a password with bcrypt and return it as a string."""
        return bcrypt.hashpw(_truncate(password), bcrypt.gensalt()).decode("utf-8")

    def __repr__(self):
        return f"<User {self.username}>"
