"""
Create or reset an admin user.

Interactive:
    python -m scripts.create_admin

Non-interactive:
    ADMIN_USERNAME=admin ADMIN_PASSWORD=... python -m scripts.create_admin
"""
import os
import sys
from getpass import getpass


sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from backend.database import SessionLocal
from backend.models.user import User, UserRole


def _prompt_text(label: str, minimum: int = 1, default: str | None = None) -> str:
    suffix = f" [{default}]" if default else ""
    while True:
        value = input(f"{label}{suffix}: ").strip() or (default or "")
        if len(value) >= minimum:
            return value
        print(f"{label} must be at least {minimum} characters")


def _prompt_password() -> str:
    while True:
        password = getpass("Password: ")
        if len(password) < 6:
            print("Password must be at least 6 characters")
            continue

        password_confirm = getpass("Confirm password: ")
        if password != password_confirm:
            print("Passwords do not match")
            continue
        return password


def create_admin():
    db = SessionLocal()

    try:
        print("=== Create or Reset Admin User ===\n")

        env_password = os.getenv("ADMIN_PASSWORD")
        non_interactive = bool(env_password)

        username = os.getenv("ADMIN_USERNAME") or ("admin" if non_interactive else _prompt_text("Username", minimum=3, default="admin"))
        default_email = f"{username}@serein.local"
        email = os.getenv("ADMIN_EMAIL") or (default_email if non_interactive else _prompt_text("Email", minimum=3, default=default_email))
        full_name = os.getenv("ADMIN_FULL_NAME") or ("Serein Admin" if non_interactive else _prompt_text("Full name", minimum=1, default="Serein Admin"))
        password = env_password or _prompt_password()

        existing = db.query(User).filter(User.username == username).first()
        if existing:
            existing.email = email
            existing.full_name = full_name
            existing.password_hash = User.hash_password(password)
            existing.role = UserRole.ADMIN
            admin_user = existing
            action = "updated"
        else:
            admin_user = User(
                username=username,
                email=email,
                full_name=full_name,
                password_hash=User.hash_password(password),
                role=UserRole.ADMIN,
            )
            db.add(admin_user)
            action = "created"

        db.commit()
        db.refresh(admin_user)

        print(f"\nAdmin user '{username}' {action} successfully.")
        print(f"User ID: {admin_user.id}")
        print(f"Role: {admin_user.role.value}")
    except Exception as exc:
        print(f"\nError: {exc}")
        db.rollback()
        raise
    finally:
        db.close()


if __name__ == "__main__":
    create_admin()
