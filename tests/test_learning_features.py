# Automated Test Script for Serein Learning Features
# Run via: python tests/test_learning_features.py

import sys
import os
import json

# Add project root directory to path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from fastapi.testclient import TestClient
from backend.app import app
from backend.auth import get_current_user
from backend.database import SessionLocal
from backend.models.user import User, UserRole
from backend.models.interaction import InterviewQuestion, UserInterviewProgress, QnaQuestion, QnaAnswer

# Initialize DB connection and seed user
db = SessionLocal()
test_user = db.query(User).filter(User.username == "test_cyber_learner").first()

if not test_user:
    test_user = User(
        username="test_cyber_learner",
        email="learner@cyber.net",
        full_name="Cyber Learner",
        password_hash=User.hash_password("testpassword123"),
        role=UserRole.LEARNER
    )
    db.add(test_user)
    db.commit()
    db.refresh(test_user)

user_id = test_user.id
db.close()

# Override FastAPI get_current_user dependency injection
app.dependency_overrides[get_current_user] = lambda: test_user

client = TestClient(app)

print("=" * 60)
print("SEREIN LEARNING MODULES - INTEGRATION TESTING")
print("=" * 60)

# Helper function to print results
def print_result(name, is_ok, details=""):
    status = "✓ PASSED" if is_ok else "✗ FAILED"
    print(f"{status} | {name} {f'({details})' if details else ''}")
    return is_ok

tests_passed = True

# Test 1: GET /api/learning/interview/questions
try:
    response = client.get("/api/learning/interview/questions")
    if response.status_code == 200:
        questions = response.json()
        tests_passed &= print_result("GET Interview Questions", True, f"Found {len(questions)} seeded questions")
    else:
        tests_passed &= print_result("GET Interview Questions", False, f"Status: {response.status_code}")
except Exception as e:
    tests_passed &= print_result("GET Interview Questions", False, str(e))

# Test 2: POST /api/learning/interview/questions/{id}/progress
try:
    # Get a question ID from seeded questions if exists
    db_session = SessionLocal()
    question = db_session.query(InterviewQuestion).first()
    db_session.close()

    if question:
        payload = {"status": "mastered"}
        response = client.post(f"/api/learning/interview/questions/{question.id}/progress", json=payload)
        if response.status_code == 200:
            data = response.json()
            tests_passed &= print_result("POST Update Interview Progress", True, f"Status updated to: {data.get('status')}")
        else:
            tests_passed &= print_result("POST Update Interview Progress", False, f"Status: {response.status_code}")
    else:
        tests_passed &= print_result("POST Update Interview Progress", False, "No interview questions seeded to test progress update")
except Exception as e:
    tests_passed &= print_result("POST Update Interview Progress", False, str(e))

# Test 3: POST /api/learning/qna/questions (Create Thread)
test_qna_id = None
try:
    payload = {
        "title": "How to configure multiple datasources in Spring Boot?",
        "tags": "java, spring, spring-boot",
        "content": "I am trying to connect to both SQLite and Postgres. How can I achieve this cleanly?"
    }
    response = client.post("/api/learning/qna/questions", json=payload)
    if response.status_code == 201:
        q_data = response.json()
        test_qna_id = q_data.get("id")
        tests_passed &= print_result("POST Create Forum Thread", True, f"Thread ID: {test_qna_id}")
    else:
        tests_passed &= print_result("POST Create Forum Thread", False, f"Status: {response.status_code}")
except Exception as e:
    tests_passed &= print_result("POST Create Forum Thread", False, str(e))

# Test 4: GET /api/learning/qna/questions (List Threads)
try:
    response = client.get("/api/learning/qna/questions")
    if response.status_code == 200:
        threads = response.json()
        found_test_thread = any(t.get("id") == test_qna_id for t in threads)
        tests_passed &= print_result("GET List Forum Threads", found_test_thread, f"Found created thread: {found_test_thread}")
    else:
        tests_passed &= print_result("GET List Forum Threads", False, f"Status: {response.status_code}")
except Exception as e:
    tests_passed &= print_result("GET List Forum Threads", False, str(e))

# Test 5: GET /api/learning/qna/questions/{id} (Thread Details)
try:
    if test_qna_id:
        response = client.get(f"/api/learning/qna/questions/{test_qna_id}")
        if response.status_code == 200:
            details = response.json()
            tests_passed &= print_result("GET Forum Thread Details", True, f"Title: {details.get('title')}")
        else:
            tests_passed &= print_result("GET Forum Thread Details", False, f"Status: {response.status_code}")
    else:
        tests_passed &= print_result("GET Forum Thread Details", False, "Skipped - Thread creation failed")
except Exception as e:
    tests_passed &= print_result("GET Forum Thread Details", False, str(e))

# Test 6: POST /api/learning/qna/questions/{id}/answers (Post Answer)
test_answer_id = None
try:
    if test_qna_id:
        payload = {
            "content": "You can declare separate Bean definitions for each routing HikariDataSource."
        }
        response = client.post(f"/api/learning/qna/questions/{test_qna_id}/answers", json=payload)
        if response.status_code == 201:
            ans_data = response.json()
            test_answer_id = ans_data.get("id")
            tests_passed &= print_result("POST Add Answer Reply", True, f"Answer ID: {test_answer_id}")
        else:
            tests_passed &= print_result("POST Add Answer Reply", False, f"Status: {response.status_code}")
    else:
        tests_passed &= print_result("POST Add Answer Reply", False, "Skipped - Thread creation failed")
except Exception as e:
    tests_passed &= print_result("POST Add Answer Reply", False, str(e))

# Test 7: POST /api/learning/chatbot (Chatbot Fallback Engines)
try:
    payloads = [
        {"message": "Hello Serein AI chatbot", "keyword": "Chào mừng"},
        {"message": "Explain decorator in python", "keyword": "Python"},
        {"message": "What is SQL index?", "keyword": "SQL"},
        {"message": "Spring Boot configuration", "keyword": "Java"}
    ]
    
    chatbot_ok = True
    for p in payloads:
        response = client.post("/api/learning/chatbot", json={"message": p["message"]})
        if response.status_code == 200:
            reply = response.json().get("reply", "")
            if p["keyword"] not in reply and "Serein" not in reply:
                chatbot_ok = False
        else:
            chatbot_ok = False
            
    tests_passed &= print_result("POST Chatbot Conversation & Keywords", chatbot_ok)
except Exception as e:
    tests_passed &= print_result("POST Chatbot Conversation & Keywords", False, str(e))

# Clean up Test Data from database
print("-" * 60)
print("PURGING TEST RECORDS...")
db_cleanup = SessionLocal()
try:
    # Delete answers
    if test_answer_id:
        db_cleanup.query(QnaAnswer).filter(QnaAnswer.id == test_answer_id).delete()
    # Delete QnA question
    if test_qna_id:
        db_cleanup.query(QnaQuestion).filter(QnaQuestion.id == test_qna_id).delete()
    # Delete User Interview Progress
    if question:
        db_cleanup.query(UserInterviewProgress).filter(
            UserInterviewProgress.user_id == user_id,
            UserInterviewProgress.question_id == question.id
        ).delete()
    
    # Delete test user
    db_cleanup.query(User).filter(User.id == user_id).delete()
    db_cleanup.commit()
    print("✓ Purged test user and created test items.")
except Exception as e:
    db_cleanup.rollback()
    print(f"✗ Failed to cleanup test records: {e}")
finally:
    db_cleanup.close()

print("=" * 60)
if tests_passed:
    print("ALL TESTS COMPLETED SUCCESSFULLY! SYSTEM INTEGRATION READY.")
    sys.exit(0)
else:
    print("SOME TESTS FAILED. PLEASE AUDIT ROUTERS & SCHEMAS.")
    sys.exit(1)
