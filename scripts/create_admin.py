"""
Script to create initial admin user
"""
import sys
from getpass import getpass
from database import SessionLocal
from models.user import User, UserRole


def create_admin():
    """Create admin user interactively"""
    db = SessionLocal()
    
    try:
        print("=== Create Admin User ===\n")
        
        # Get username
        while True:
            username = input("Username: ").strip()
            if len(username) < 3:
                print("Username must be at least 3 characters")
                continue
            
            # Check if exists
            existing = db.query(User).filter(User.username == username).first()
            if existing:
                print(f"User '{username}' already exists!")
                return
            break
        
        # Get password
        while True:
            password = getpass("Password: ")
            if len(password) < 6:
                print("Password must be at least 6 characters")
                continue
            
            password_confirm = getpass("Confirm password: ")
            if password != password_confirm:
                print("Passwords don't match!")
                continue
            break
        
        # Create user
        admin_user = User(
            username=username,
            password_hash=User.hash_password(password),
            role=UserRole.ADMIN
        )
        
        db.add(admin_user)
        db.commit()
        db.refresh(admin_user)
        
        print(f"\n✅ Admin user '{username}' created successfully!")
        print(f"User ID: {admin_user.id}")
        print(f"Role: {admin_user.role.value}")
        
    except Exception as e:
        print(f"\n❌ Error: {e}")
        db.rollback()
    finally:
        db.close()


if __name__ == "__main__":
    create_admin()
