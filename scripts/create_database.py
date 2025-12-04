"""
Script to create PostgreSQL database for Serein Blog
"""
import psycopg2
from psycopg2.extensions import ISOLATION_LEVEL_AUTOCOMMIT

def create_database():
    """Create the serein_db database if it doesn't exist"""
    try:
        # Connect to PostgreSQL server
        conn = psycopg2.connect(
            host="localhost",
            port=5432,
            user="postgres",
            password="Fpt1409!@"
        )
        conn.set_isolation_level(ISOLATION_LEVEL_AUTOCOMMIT)
        cursor = conn.cursor()
        
        # Check if database exists
        cursor.execute("SELECT 1 FROM pg_database WHERE datname='serein_db'")
        exists = cursor.fetchone()
        
        if not exists:
            cursor.execute("CREATE DATABASE serein_db")
            print("✓ Database 'serein_db' created successfully!")
        else:
            print("✓ Database 'serein_db' already exists.")
        
        cursor.close()
        conn.close()
        return True
        
    except Exception as e:
        print(f"✗ Error creating database: {e}")
        return False

if __name__ == "__main__":
    create_database()
