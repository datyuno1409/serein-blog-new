import requests
import json

BASE_URL = "http://localhost:8000"
token = None

def login():
    global token
    print("=== Testing Login ===")
    response = requests.post(
        f"{BASE_URL}/api/auth/login",
        data={"username": "admin", "password": "admin"}
    )
    if response.status_code == 200:
        token = response.json()['access_token']
        print(f"✓ Login successful! Token: {token[:30]}...")
        return True
    else:
        print(f"✗ Login failed: {response.text}")
        return False

def test_articles():
    print("\n=== Testing Articles CRUD ===")
    headers = {"Authorization": f"Bearer {token}"}
    
    # CREATE
    print("\n1. Creating article...")
    article_data = {
        "title": "Test Article",
        "content": "This is test content",
        "excerpt": "Test excerpt",
        "status": "published"
    }
    response = requests.post(
        f"{BASE_URL}/api/articles/",
        json=article_data,
        headers=headers
    )
    if response.status_code in [200, 201]:
        article = response.json()
        article_id = article['id']
        print(f"✓ Article created! ID: {article_id}")
    else:
        print(f"✗ Failed to create: {response.text}")
        return
    
    # READ
    print("\n2. Reading articles...")
    response = requests.get(f"{BASE_URL}/api/articles/", headers=headers)
    if response.status_code == 200:
        articles = response.json()
        print(f"✓ Found {len(articles)} articles")
    else:
        print(f"✗ Failed to read: {response.text}")
    
    # UPDATE
    print("\n3. Updating article...")
    update_data = {
        "title": "Updated Test Article",
        "content": "Updated content",
        "excerpt": "Updated excerpt",
        "status": "published"
    }
    response = requests.put(
        f"{BASE_URL}/api/articles/{article_id}",
        json=update_data,
        headers=headers
    )
    if response.status_code == 200:
        print("✓ Article updated!")
    else:
        print(f"✗ Failed to update: {response.text}")
    
    # DELETE
    print("\n4. Deleting article...")
    response = requests.delete(
        f"{BASE_URL}/api/articles/{article_id}",
        headers=headers
    )
    if response.status_code in [200, 204]:
        print("✓ Article deleted!")
    else:
        print(f"✗ Failed to delete: {response.text}")

def test_projects():
    print("\n=== Testing Projects CRUD ===")
    headers = {"Authorization": f"Bearer {token}"}
    
    # CREATE
    print("\n1. Creating project...")
    project_data = {
        "title": "Test Project",
        "description": "Test project description",
        "technologies": "Python, FastAPI",
        "github_url": "https://github.com/test/project",
        "status": "completed"
    }
    response = requests.post(
        f"{BASE_URL}/api/projects/",
        json=project_data,
        headers=headers
    )
    if response.status_code in [200, 201]:
        project = response.json()
        project_id = project['id']
        print(f"✓ Project created! ID: {project_id}")
    else:
        print(f"✗ Failed to create: {response.text}")
        return
    
    # READ
    print("\n2. Reading projects...")
    response = requests.get(f"{BASE_URL}/api/projects/", headers=headers)
    if response.status_code == 200:
        projects = response.json()
        print(f"✓ Found {len(projects)} projects")
    else:
        print(f"✗ Failed to read: {response.text}")
    
    # UPDATE
    print("\n3. Updating project...")
    update_data = {
        "title": "Updated Test Project",
        "description": "Updated description",
        "technologies": "Python, FastAPI, React",
        "status": "completed"
    }
    response = requests.put(
        f"{BASE_URL}/api/projects/{project_id}",
        json=update_data,
        headers=headers
    )
    if response.status_code == 200:
        print("✓ Project updated!")
    else:
        print(f"✗ Failed to update: {response.text}")
    
    # DELETE
    print("\n4. Deleting project...")
    response = requests.delete(
        f"{BASE_URL}/api/projects/{project_id}",
        headers=headers
    )
    if response.status_code in [200, 204]:
        print("✓ Project deleted!")
    else:
        print(f"✗ Failed to delete: {response.text}")

if __name__ == "__main__":
    print("=" * 50)
    print("SEREIN BLOG - ADMIN FEATURES TEST")
    print("=" * 50)
    
    if login():
        test_articles()
        test_projects()
        print("\n" + "=" * 50)
        print("✓ ALL TESTS COMPLETED!")
        print("=" * 50)
