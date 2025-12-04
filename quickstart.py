"""
Quick Start Script for Serein Blog Platform
Run this to set up everything automatically
"""
import subprocess
import sys
import os
from getpass import getpass

def run_command(cmd, description):
    """Run a command and print result"""
    print(f"\n{'='*60}")
    print(f"▶ {description}")
    print(f"{'='*60}")
    result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    if result.stdout:
        print(result.stdout)
    if result.returncode != 0:
        print(f"❌ Error: {result.stderr}")
        return False
    print(f"✅ {description} - Complete!")
    return True

def create_admin_user():
    """Create admin user directly"""
    print(f"\n{'='*60}")
    print(f"▶ Creating Admin User")
    print(f"{'='*60}")
    
    sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
    from backend.database import SessionLocal
    from backend.models.user import User, UserRole
    
    db = SessionLocal()
    
    try:
        # Check if admin exists
        existing = db.query(User).filter(User.username == "admin").first()
        if existing:
            print("✅ Admin user already exists!")
            return True
        
        # Create admin user
        username = input("Admin username (default: admin): ").strip() or "admin"
        password = getpass("Admin password: ")
        
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
        return True
        
    except Exception as e:
        print(f"❌ Error creating admin: {e}")
        db.rollback()
        return False
    finally:
        db.close()

def main():
    """Main setup function"""
    print("""
╔══════════════════════════════════════════════════════════╗
║                                                          ║
║        SEREIN BLOG PLATFORM - QUICK START SETUP         ║
║                                                          ║
╚══════════════════════════════════════════════════════════╝
    """)
    
    # Step 1: Check .env exists
    if not os.path.exists('.env'):
        print("📝 Creating .env file from template...")
        subprocess.run("copy .env.example .env", shell=True)
        print("✅ .env file created!")
        print("\n⚠️  Using SQLite database (serein.db)")
        print("   To use PostgreSQL, edit .env and update DATABASE_URL\n")
    
    # Step 2: Run migrations
    if not run_command("alembic upgrade head", "Running database migrations"):
        print("\n❌ Migration failed. Please check your database configuration.")
        return
    
    # Step 3: Create admin user
    if not create_admin_user():
        print("\n❌ Failed to create admin user.")
        return
    
    # Success!
    print(f"\n{'='*60}")
    print("✅ SETUP COMPLETE!")
    print(f"{'='*60}")
    print("\nYour Serein Blog Platform is ready!")
    print("\n📚 Next steps:")
    print("   1. Start the server: python main.py")
    print("   2. Open browser: http://localhost:8000")
    print("   3. API docs: http://localhost:8000/api/docs")
    print("   4. Admin panel: http://localhost:8000/admin")
    print(f"\n{'='*60}\n")

if __name__ == "__main__":
    main()
