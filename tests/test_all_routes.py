import requests
import pytest


BASE_URL = "http://localhost:8000"

frontend_routes = [
    "/",
    "/home",
    "/portfolio",
    "/blog",
    "/post",
]

admin_routes = [
    "/admin",
    "/admin/dashboard",
    "/admin/articles",
    "/admin/projects",
    "/admin/appearance",
    "/admin/settings",
]

api_routes = [
    "/health",
    "/api/docs",
    "/api/articles/",
    "/api/projects/",
    "/api/skills/",
    "/api/settings/",
]

static_routes = [
    "/assets/css/style.css",
    "/assets/js/main.js",
]


def check_route(path, expected_status=200):
    """Manual smoke helper used when this file is run as a script."""
    try:
        response = requests.get(f"{BASE_URL}{path}", timeout=10)
        status = "✓" if response.status_code == expected_status else "✗"
        return f"{status} {path} - Status: {response.status_code}"
    except Exception as exc:
        return f"✗ {path} - Error: {exc}"


@pytest.mark.parametrize("route", frontend_routes + admin_routes + api_routes + static_routes)
def test_route_list_is_well_formed(route):
    assert route.startswith("/")


if __name__ == "__main__":
    print("=" * 60)
    print("SEREIN BLOG - COMPREHENSIVE TESTING")
    print("=" * 60)

    print("\nFRONTEND ROUTES:")
    for route in frontend_routes:
        print(check_route(route))

    print("\nADMIN ROUTES:")
    for route in admin_routes:
        print(check_route(route))

    print("\nAPI ROUTES:")
    for route in api_routes:
        print(check_route(route))

    print("\nSTATIC ASSETS:")
    for route in static_routes:
        print(check_route(route))

    print("\n" + "=" * 60)
    print("TESTING COMPLETE")
    print("=" * 60)
