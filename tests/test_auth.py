from backend.database import SessionLocal
from backend.models.user import User

db = SessionLocal()

# Check if user exists
user = db.query(User).filter(User.username == 'admin').first()

if user:
    print(f"✓ User found: {user.username}")
    print(f"  Role: {user.role.value}")
    print(f"  Password hash (first 50 chars): {user.password_hash[:50]}...")
    
    # Test password verification
    test_passwords = ['admin', 'admin123', 'Admin', 'ADMIN']
    for pwd in test_passwords:
        result = user.verify_password(pwd)
        print(f"  Password '{pwd}': {result}")
else:
    print("✗ User not found!")

db.close()
