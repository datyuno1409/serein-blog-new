# Test Script - Comprehensive Application Testing

import requests
import json

BASE_URL = "http://localhost:8000"

def test_route(path, expected_status=200):
    """Test a route and return result"""
    try:
        response = requests.get(f"{BASE_URL}{path}")
        status = "✓" if response.status_code == expected_status else "✗"
        return f"{status} {path} - Status: {response.status_code}"
    except Exception as e:
        return f"✗ {path} - Error: {str(e)}"

print("=" * 60)
print("SEREIN BLOG - COMPREHENSIVE TESTING")
print("=" * 60)

print("\n📄 FRONTEND ROUTES:")
frontend_routes = [
    "/",
    "/home",
    "/about",
    "/portfolio",
    "/blog",
    "/post"
]

for route in frontend_routes:
    print(test_route(route))

print("\n🔐 ADMIN ROUTES:")
admin_routes = [
    "/admin",
    "/admin/dashboard",
    "/admin/articles",
    "/admin/projects",
    "/admin/appearance",
    "/admin/settings"
]

for route in admin_routes:
    print(test_route(route))

print("\n🔌 API ROUTES:")
api_routes = [
    "/health",
    "/api/docs",
    "/api/articles/",
    "/api/projects/",
    "/api/skills/",
    "/api/settings/"
]

for route in api_routes:
    print(test_route(route))

print("\n📁 STATIC ASSETS:")
static_routes = [
    "/assets/css/style.css",
    "/assets/js/main.js"
]

for route in static_routes:
    print(test_route(route))

print("\n" + "=" * 60)
print("TESTING COMPLETE")
print("=" * 60)
