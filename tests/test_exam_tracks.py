import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.append(str(ROOT))

import pytest
from fastapi.testclient import TestClient

from backend.app import app
from backend.auth import get_current_user
from backend.database import SessionLocal
from backend.models.interaction import VirtualExamSession
from backend.models.user import User, UserRole


@pytest.fixture
def auth_client():
    db = SessionLocal()
    user = db.query(User).filter(User.username == "exam_track_test_user").first()
    if not user:
        user = User(
            username="exam_track_test_user",
            email="exam_track_test@serein.test",
            full_name="Exam Track Test",
            password_hash=User.hash_password("testpassword123"),
            role=UserRole.LEARNER,
        )
        db.add(user)
        db.commit()
        db.refresh(user)
    db.close()
    app.dependency_overrides[get_current_user] = lambda: user
    try:
        yield TestClient(app), user
    finally:
        app.dependency_overrides.clear()
        cleanup_db = SessionLocal()
        cleanup_user = cleanup_db.query(User).filter(User.username == "exam_track_test_user").first()
        if cleanup_user:
            cleanup_db.query(VirtualExamSession).filter(VirtualExamSession.user_id == cleanup_user.id).delete()
            cleanup_db.delete(cleanup_user)
            cleanup_db.commit()
        cleanup_db.close()


def test_track_catalog_exposes_exam_specific_levels():
    client = TestClient(app)
    response = client.get("/api/learning/tracks")
    assert response.status_code == 200
    data = response.json()
    assert data["english"]["toeic"]["levels"] == ["100", "200", "300", "400", "500", "600", "700"]
    assert data["english"]["ielts"]["levels"] == ["1.0", "2.0", "3.0", "4.0", "5.0", "6.0", "7.0"]
    assert data["chinese"]["hsk"]["levels"] == ["1", "2", "3", "4", "5", "6"]


def test_learning_resources_endpoint_returns_api_ready_sources(auth_client):
    client, _user = auth_client
    response = client.get("/api/learning/resources?domain=english&track=ielts&target_level=7.0&limit=3")
    assert response.status_code == 200
    data = response.json()
    assert data["domain"] == "english"
    assert data["track"] == "ielts"
    assert data["resources"]
    assert data["resources"][0]["url"].startswith("https://")
    assert data["resources"][0]["source_type"] in {"curated-fallback", "external-api-live", "public-api-live"}


def test_roadmap_accepts_track_and_target_level(auth_client):
    client, _user = auth_client
    response = client.post(
        "/api/learning/roadmap",
        json={
            "domain": "java-backend",
            "level": "intermediate",
            "track": "spring-boot",
            "target_level": "advanced",
            "goal": "Java Backend Developer",
            "intensity_hours": 1.0,
            "preferred_modes": ["quiz", "flashcard"],
        },
    )
    assert response.status_code == 201
    data = response.json()
    assert "Java Backend Developer" in data["learning_path"]["title"]
    assert data["roadmap_steps"]
    assert data["roadmap_steps"][0]["step_metadata"]["track"] == "spring-boot"
    assert data["roadmap_steps"][0]["step_metadata"]["target_level"] == "advanced"
    resource = data["roadmap_steps"][0]["step_metadata"]["learning_resource"]
    assert resource["title"]
    assert resource["url"].startswith("https://")
    assert resource["source_type"] in {"curated-fallback", "external-api-live", "public-api-live"}


def test_language_domains_do_not_create_roadmaps(auth_client):
    client, _user = auth_client
    response = client.post(
        "/api/learning/roadmap",
        json={
            "domain": "english",
            "level": "intermediate",
            "track": "toeic",
            "target_level": "700",
            "goal": "TOEIC 700",
            "intensity_hours": 1.0,
            "preferred_modes": ["quiz", "flashcard"],
        },
    )
    assert response.status_code == 422


def test_virtual_exam_session_and_submit_flow(auth_client):
    client, _user = auth_client
    session_response = client.post(
        "/api/learning/exams/sessions",
        json={
            "domain": "chinese",
            "track": "hsk",
            "target_level": "3",
            "question_count": 5,
        },
    )
    assert session_response.status_code == 201
    session = session_response.json()
    assert session["track"] == "hsk"
    assert session["source"]["source_type"] == "external-api-ready"
    assert len(session["questions"]) == 5

    history_response = client.get("/api/learning/exams/history")
    assert history_response.status_code == 200
    history = history_response.json()
    assert history
    assert history[0]["session_id"] == session["session_id"]
    assert history[0]["status"] == "in_progress"

    submit_response = client.post(
        "/api/learning/exams/submit",
        json={"session_id": session["session_id"], "answers": {}},
    )
    assert submit_response.status_code == 200
    result = submit_response.json()
    assert result["total"] == 5
    assert result["percentage"] == 0

    history_response = client.get("/api/learning/exams/history")
    assert history_response.status_code == 200
    history = history_response.json()
    assert history[0]["session_id"] == session["session_id"]
    assert history[0]["status"] == "submitted"
    assert history[0]["total"] == 5


def test_hsk_exam_list_includes_answer_metadata():
    client = TestClient(app)
    response = client.get("/api/hsk/exams")
    assert response.status_code == 200
    data = response.json()
    assert data
    assert "correct_answer" in data[0]
    assert "explanation" in data[0]
