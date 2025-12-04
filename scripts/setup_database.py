"""
Script to create PostgreSQL database and setup initial configuration
"""
import psycopg2
from psycopg2 import sql
from getpass import getpass

def create_database():
    """Create PostgreSQL database for Serein Blog"""
    
    print("=== PostgreSQL Database Setup ===\n")
    
    # Get PostgreSQL credentials
    postgres_password = getpass("Enter PostgreSQL password (user 'postgres'): ")
    
    try:
        # Connect to PostgreSQL server (default database)
        print("\nConnecting to PostgreSQL...")
        conn = psycopg2.connect(
            host="localhost",
            port=5432,
            user="postgres",
            password=postgres_password,
            database="postgres"
        )
        conn.autocommit = True
        cursor = conn.cursor()
        
        # Check if database exists
        cursor.execute(
            "SELECT 1 FROM pg_database WHERE datname = 'serein_db'"
        )
        exists = cursor.fetchone()
        
        if exists:
            print("✅ Database 'serein_db' already exists!")
        else:
            # Create database
            print("Creating database 'serein_db'...")
            cursor.execute(
                sql.SQL("CREATE DATABASE {}").format(
                    sql.Identifier('serein_db')
                )
            )
            print("✅ Database 'serein_db' created successfully!")
        
        cursor.close()
        conn.close()
        
        # Update .env file
        print("\n📝 Updating .env file...")
        env_content = f"""# Environment Configuration
ENVIRONMENT=development

# Database Configuration
DATABASE_URL=postgresql://postgres:{postgres_password}@localhost:5432/serein_db

# Security
SECRET_KEY=dev-secret-key-change-in-production-please
ALGORITHM=HS256
ACCESS_TOKEN_EXPIRE_MINUTES=30

# CORS
ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8000,http://localhost:5173

# Upload Configuration
UPLOAD_FOLDER=uploads
MAX_UPLOAD_SIZE=5242880

# Admin Configuration
ADMIN_EMAIL=admin@serein.com
"""
        
        with open('.env', 'w') as f:
            f.write(env_content)
        
        print("✅ .env file updated with PostgreSQL connection!")
        
        print("\n" + "="*50)
        print("✅ Setup Complete!")
        print("="*50)
        print("\nNext steps:")
        print("1. Run migrations: alembic upgrade head")
        print("2. Create admin user: python scripts/create_admin.py")
        print("3. Start server: python main.py")
        
    except psycopg2.OperationalError as e:
        print(f"\n❌ Error: Could not connect to PostgreSQL")
        print(f"Details: {e}")
        print("\nMake sure:")
        print("- PostgreSQL is running")
        print("- Password is correct")
        print("- Port 5432 is accessible")
    except Exception as e:
        print(f"\n❌ Error: {e}")

if __name__ == "__main__":
    create_database()
