import requests

# Test login
url = "http://localhost:8000/api/auth/login"
data = {
    "username": "admin",
    "password": "admin"
}

print("Testing login...")
response = requests.post(url, data=data)

print(f"Status code: {response.status_code}")
print(f"Response: {response.json()}")

if response.status_code == 200:
    print("✓ Login successful!")
    token = response.json().get('access_token')
    print(f"Token: {token[:50]}...")
else:
    print("✗ Login failed!")
