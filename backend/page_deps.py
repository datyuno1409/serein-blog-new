from __future__ import annotations

from typing import Optional

from fastapi import Request
from jose import JWTError, jwt
from sqlalchemy.orm import Session

from .config import settings
from .models.user import User


def get_user_from_request(request: Request, db: Session) -> Optional[User]:
    token = request.cookies.get("access_token")
    if not token:
        return None

    try:
        payload = jwt.decode(token, settings.secret_key, algorithms=[settings.algorithm])
    except JWTError:
        return None

    username = payload.get("sub")
    if not username:
        return None

    return db.query(User).filter(User.username == username).first()


def build_learning_context(request: Request, user: Optional[User], **extra):
    base = {
        "request": request,
        "current_user": user,
        "is_authenticated": user is not None,
    }
    base.update(extra)
    return base
