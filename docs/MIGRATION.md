# Migration Guide: PHP to Python/FastAPI

Guide for migrating data from existing PHP/MySQL installation to Python/PostgreSQL.

## Overview

This guide helps you migrate from the old PHP version to the new Python/FastAPI version while preserving all your data.

## Prerequisites

- Access to old MySQL database
- New PostgreSQL database set up
- Python environment configured
- Both databases accessible

## Step 1: Export Data from MySQL

### Option A: Using mysqldump

```bash
# Export all data
mysqldump -u root -p serein > mysql_backup.sql

# Export specific tables
mysqldump -u root -p serein users articles projects > mysql_backup.sql
```

### Option B: Using Python Script

Create `scripts/export_mysql.py`:

```python
import mysql.connector
import json
from datetime import datetime

# MySQL connection
mysql_conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="serein"
)

cursor = mysql_conn.cursor(dictionary=True)

# Export function
def export_table(table_name):
    cursor.execute(f"SELECT * FROM {table_name}")
    rows = cursor.fetchall()
    
    # Convert datetime to string
    for row in rows:
        for key, value in row.items():
            if isinstance(value, datetime):
                row[key] = value.isoformat()
    
    with open(f"export_{table_name}.json", "w") as f:
        json.dump(rows, f, indent=2)
    
    print(f"Exported {len(rows)} rows from {table_name}")

# Export all tables
tables = ["users", "articles", "projects", "about", "skills", 
          "social_links", "testimonials", "seo_settings", "settings"]

for table in tables:
    export_table(table)

cursor.close()
mysql_conn.close()
```

Run the script:

```bash
python scripts/export_mysql.py
```

## Step 2: Transform Data (if needed)

Some fields may need transformation:

```python
# scripts/transform_data.py
import json

def transform_users(data):
    """Transform user data"""
    for user in data:
        # Ensure role is valid
        if user['role'] not in ['admin', 'editor', 'viewer']:
            user['role'] = 'admin'
    return data

def transform_articles(data):
    """Transform article data"""
    for article in data:
        # Ensure status is valid
        if article['status'] not in ['published', 'draft']:
            article['status'] = 'draft'
    return data

# Load and transform
with open('export_users.json') as f:
    users = json.load(f)
    users = transform_users(users)
    with open('export_users_transformed.json', 'w') as out:
        json.dump(users, out, indent=2)
```

## Step 3: Import Data to PostgreSQL

### Create Import Script

Create `scripts/import_data.py`:

```python
import json
from sqlalchemy.orm import Session
from database import SessionLocal, engine
from models import *

def import_users(db: Session):
    """Import users"""
    with open('export_users.json') as f:
        users_data = json.load(f)
    
    for user_data in users_data:
        user = User(
            id=user_data['id'],
            username=user_data['username'],
            password_hash=user_data['password_hash'],
            role=user_data['role'],
            created_at=user_data.get('created_at')
        )
        db.add(user)
    
    db.commit()
    print(f"Imported {len(users_data)} users")

def import_articles(db: Session):
    """Import articles"""
    with open('export_articles.json') as f:
        articles_data = json.load(f)
    
    for article_data in articles_data:
        article = Article(
            id=article_data['id'],
            title=article_data['title'],
            slug=article_data['slug'],
            content=article_data['content'],
            excerpt=article_data.get('excerpt'),
            status=article_data['status'],
            created_at=article_data.get('created_at'),
            updated_at=article_data.get('updated_at')
        )
        db.add(article)
    
    db.commit()
    print(f"Imported {len(articles_data)} articles")

def import_projects(db: Session):
    """Import projects"""
    with open('export_projects.json') as f:
        projects_data = json.load(f)
    
    for project_data in projects_data:
        # Parse JSON fields
        technologies = json.loads(project_data.get('technologies', '[]'))
        gallery_images = json.loads(project_data.get('gallery_images', '[]'))
        
        project = Project(
            id=project_data['id'],
            title=project_data['title'],
            description=project_data['description'],
            short_description=project_data.get('short_description'),
            technologies=technologies,
            project_url=project_data.get('project_url'),
            github_url=project_data.get('github_url'),
            featured_image=project_data.get('featured_image'),
            gallery_images=gallery_images,
            status=project_data['status'],
            featured=bool(project_data.get('featured', 0)),
            sort_order=project_data.get('sort_order', 0),
            created_at=project_data.get('created_at'),
            updated_at=project_data.get('updated_at')
        )
        db.add(project)
    
    db.commit()
    print(f"Imported {len(projects_data)} projects")

# Main import function
def main():
    db = SessionLocal()
    
    try:
        print("Starting data import...")
        
        import_users(db)
        import_articles(db)
        import_projects(db)
        # Add more import functions as needed
        
        print("Data import completed successfully!")
        
    except Exception as e:
        print(f"Error during import: {e}")
        db.rollback()
    finally:
        db.close()

if __name__ == "__main__":
    main()
```

Run the import:

```bash
# Make sure database is migrated first
alembic upgrade head

# Run import
python scripts/import_data.py
```

## Step 4: Verify Data

```python
# scripts/verify_data.py
from database import SessionLocal
from models import User, Article, Project

db = SessionLocal()

# Count records
users_count = db.query(User).count()
articles_count = db.query(Article).count()
projects_count = db.query(Project).count()

print(f"Users: {users_count}")
print(f"Articles: {articles_count}")
print(f"Projects: {projects_count}")

# Sample data
print("\nSample User:")
user = db.query(User).first()
if user:
    print(f"  Username: {user.username}")
    print(f"  Role: {user.role}")

print("\nSample Article:")
article = db.query(Article).first()
if article:
    print(f"  Title: {article.title}")
    print(f"  Status: {article.status}")

db.close()
```

## Step 5: Update File Paths

If you have uploaded files (images, etc.):

```bash
# Copy uploads directory
cp -r /old/path/uploads /new/path/uploads

# Update permissions
chmod -R 755 uploads
chown -R www-data:www-data uploads
```

## Rollback Plan

If something goes wrong:

```bash
# Backup before migration
pg_dump -U serein_user serein_db > pre_migration_backup.sql

# Restore if needed
psql -U serein_user serein_db < pre_migration_backup.sql
```

## Common Issues

### Password Hashes

PHP uses `password_hash()` with bcrypt, which is compatible with Python's `passlib[bcrypt]`. No conversion needed!

### JSON Fields

MySQL stores JSON as text. Convert to proper JSON in PostgreSQL:

```python
import json
technologies = json.loads(mysql_data['technologies'])
```

### Timestamps

Convert MySQL DATETIME to PostgreSQL TIMESTAMP:

```python
from datetime import datetime
created_at = datetime.fromisoformat(mysql_data['created_at'])
```

## Post-Migration Checklist

- ✅ All tables have correct row counts
- ✅ Sample data looks correct
- ✅ User login works
- ✅ Admin panel accessible
- ✅ API endpoints return data
- ✅ Images/uploads accessible
- ✅ SEO settings preserved
- ✅ Old database backed up

---

**Migration complete! Your data is now in PostgreSQL. 🎉**
