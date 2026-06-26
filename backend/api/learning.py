from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session, joinedload
from typing import Optional, List
from datetime import datetime, timezone

from ..auth import get_current_user
from ..database import get_db
from ..models.learning import Course, LearningPath, Lesson, QuizQuestion, RoadmapStep, UserCourseProgress, UserLessonProgress
from ..models.user import User
from ..models.interaction import InterviewQuestion, UserInterviewProgress, QnaQuestion, QnaAnswer, VirtualExamSession
from ..exam_sources import deserialize_question, grade_questions, serialize_question, start_exam_session
from ..learning_sources import get_learning_resources
from ..seed_learning import TRACK_CATALOG
from ..schemas import (
    ExamResultResponse,
    ExamHistoryItemResponse,
    ExamSessionResponse,
    ExamSourceInfo,
    ExamStartRequest,
    ExamSubmitRequest,
    LearningPathCreate,
    LearningPathResponse,
    ProfileUpdate,
    RoadmapStepResponse,
    RoadmapWizardRequest,
    RoadmapWizardResponse,
    UserResponse,
    InterviewQuestionResponse,
    UserInterviewProgressUpdate,
    QnaAnswerCreate,
    QnaAnswerResponse,
    QnaQuestionCreate,
    QnaQuestionResponse,
    QnaQuestionDetailResponse,
    ChatbotRequest,
    ChatbotResponse,
)
from ..seed_learning import create_personalized_learning_path

router = APIRouter()


@router.get("/tracks")
async def get_learning_tracks():
    return TRACK_CATALOG


@router.get("/resources")
async def get_learning_source_resources(
    domain: str,
    track: str,
    target_level: Optional[str] = None,
    limit: int = 6,
    current_user: User = Depends(get_current_user),
):
    bounded_limit = min(max(limit, 1), 12)
    return {
        "domain": domain,
        "track": track,
        "target_level": target_level,
        "resources": get_learning_resources(domain, track, target_level, bounded_limit),
    }


@router.get("/dashboard")
async def get_learning_dashboard(current_user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    path = db.query(LearningPath).options(joinedload(LearningPath.courses)).filter(
        LearningPath.user_id == current_user.id,
        LearningPath.active == True,
    ).first()
    progress = db.query(UserCourseProgress).filter(UserCourseProgress.user_id == current_user.id).all()
    return {
        "user": UserResponse.model_validate(current_user),
        "active_path": path,
        "course_progress": progress,
        "stats": {
            "paths": db.query(LearningPath).filter(LearningPath.user_id == current_user.id).count(),
            "courses": db.query(Course).join(LearningPath).filter(LearningPath.user_id == current_user.id).count(),
            "completed_lessons": sum(item.completed_lessons for item in progress),
        },
    }


@router.post("/paths", response_model=LearningPathResponse, status_code=status.HTTP_201_CREATED)
async def create_learning_path(
    payload: LearningPathCreate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    db.query(LearningPath).filter(LearningPath.user_id == current_user.id).update({"active": False})
    new_path = LearningPath(user_id=current_user.id, **payload.model_dump(), active=True)
    db.add(new_path)
    db.commit()
    db.refresh(new_path)
    return new_path


@router.post("/roadmap", response_model=RoadmapWizardResponse, status_code=status.HTTP_201_CREATED)
async def create_roadmap(
    payload: RoadmapWizardRequest,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    path, steps, courses = create_personalized_learning_path(
        db,
        user=current_user,
        domain=payload.domain,
        level=payload.level,
        track=payload.track,
        target_level=payload.target_level,
        goal=payload.goal,
        intensity_hours=payload.intensity_hours,
        preferred_modes=payload.preferred_modes,
    )
    return RoadmapWizardResponse(
        learning_path=LearningPathResponse.model_validate(path),
        roadmap_steps=[RoadmapStepResponse.model_validate(step) for step in steps],
        recommended_course_slugs=[course.slug for course in courses],
    )


@router.post("/exams/sessions", response_model=ExamSessionResponse, status_code=status.HTTP_201_CREATED)
async def start_virtual_exam(
    payload: ExamStartRequest,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    session = start_exam_session(
        domain=payload.domain,
        track=payload.track,
        target_level=payload.target_level,
        question_count=payload.question_count,
    )
    db_session = VirtualExamSession(
        session_uid=session.session_id,
        user_id=current_user.id,
        domain=session.domain,
        track=session.track,
        target_level=session.target_level,
        duration_minutes=session.duration_minutes,
        source_provider=session.provider,
        source_type=session.source_type,
        source_url=session.source_url,
        freshness=session.freshness,
        questions=[serialize_question(question, include_answer=True) for question in session.questions],
        total=sum(question.points for question in session.questions),
    )
    db.add(db_session)
    db.commit()

    return ExamSessionResponse(
        session_id=session.session_id,
        domain=session.domain,
        track=session.track,
        target_level=session.target_level,
        duration_minutes=session.duration_minutes,
        source=ExamSourceInfo(
            provider=session.provider,
            source_type=session.source_type,
            freshness=session.freshness,
            source_url=session.source_url,
        ),
        questions=[
            {
                "id": question.id,
                "section": question.section,
                "question_type": question.question_type,
                "prompt": question.prompt,
                "options": question.options,
                "points": question.points,
            }
            for question in session.questions
        ],
    )


@router.post("/exams/submit", response_model=ExamResultResponse)
async def submit_virtual_exam(
    payload: ExamSubmitRequest,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    session = db.query(VirtualExamSession).filter(
        VirtualExamSession.session_uid == payload.session_id,
        VirtualExamSession.user_id == current_user.id,
    ).first()
    if not session:
        raise HTTPException(status_code=404, detail="Exam session not found")

    questions = [deserialize_question(question) for question in session.questions]
    score, total = grade_questions(questions, payload.answers)
    percentage = int((score / total) * 100)
    passed = percentage >= 60
    feedback = "Bạn đã đạt yêu cầu. Tiếp tục luyện đề cấp độ cao hơn." if passed else "Bạn nên ôn lại các phần sai và thi lại sau."

    session.answers = payload.answers
    session.score = score
    session.total = total
    session.percentage = percentage
    session.status = "submitted"
    session.submitted_at = datetime.now(timezone.utc)
    db.add(session)
    db.commit()

    return ExamResultResponse(
        session_id=payload.session_id,
        score=score,
        total=total,
        percentage=percentage,
        passed=passed,
        feedback=feedback,
    )


@router.get("/exams/history", response_model=List[ExamHistoryItemResponse])
async def get_virtual_exam_history(
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    sessions = (
        db.query(VirtualExamSession)
        .filter(VirtualExamSession.user_id == current_user.id)
        .order_by(VirtualExamSession.started_at.desc())
        .limit(20)
        .all()
    )
    return [
        ExamHistoryItemResponse(
            session_id=session.session_uid,
            domain=session.domain,
            track=session.track,
            target_level=session.target_level,
            score=session.score,
            total=session.total,
            percentage=session.percentage,
            status=session.status,
            source=ExamSourceInfo(
                provider=session.source_provider,
                source_type=session.source_type,
                freshness=session.freshness,
                source_url=session.source_url,
            ),
            started_at=session.started_at,
            submitted_at=session.submitted_at,
        )
        for session in sessions
    ]


@router.get("/roadmap/current")
async def get_current_roadmap(
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    path = db.query(LearningPath).filter(
        LearningPath.user_id == current_user.id,
        LearningPath.active == True,
    ).first()
    if not path:
        raise HTTPException(status_code=404, detail="No active roadmap found")

    steps = (
        db.query(RoadmapStep)
        .filter(RoadmapStep.learning_path_id == path.id)
        .order_by(RoadmapStep.order_index)
        .all()
    )
    courses = db.query(Course).filter(Course.learning_path_id == path.id).all()
    return RoadmapWizardResponse(
        learning_path=LearningPathResponse.model_validate(path),
        roadmap_steps=[RoadmapStepResponse.model_validate(step) for step in steps],
        recommended_course_slugs=[course.slug for course in courses],
    )


@router.put("/profile", response_model=UserResponse)
async def update_profile(
    payload: ProfileUpdate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    if payload.email and payload.email != current_user.email:
        existing = db.query(User).filter(User.email == payload.email).first()
        if existing:
            raise HTTPException(status_code=400, detail="Email already in use")

    for field, value in payload.model_dump(exclude_unset=True).items():
        setattr(current_user, field, value)

    db.add(current_user)
    db.commit()
    db.refresh(current_user)
    return current_user


@router.post("/lessons/{lesson_id}/progress")
async def save_lesson_progress(
    lesson_id: int,
    payload: dict,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    lesson = db.query(Lesson).filter(Lesson.id == lesson_id).first()
    if not lesson:
        raise HTTPException(status_code=404, detail="Lesson not found")

    progress = db.query(UserLessonProgress).filter(
        UserLessonProgress.user_id == current_user.id,
        UserLessonProgress.lesson_id == lesson_id,
    ).first()
    if not progress:
        progress = UserLessonProgress(user_id=current_user.id, lesson_id=lesson_id)

    for key in ["status", "notes", "writing_submission", "score"]:
        if key in payload:
            setattr(progress, key, payload[key])

    db.add(progress)
    db.flush()

    course = lesson.course
    lesson_ids = [item.id for item in course.lessons]
    total_lessons = len(lesson_ids)
    completed_lessons = db.query(UserLessonProgress).filter(
        UserLessonProgress.user_id == current_user.id,
        UserLessonProgress.lesson_id.in_(lesson_ids),
        UserLessonProgress.status == "completed",
    ).count()

    course_progress = db.query(UserCourseProgress).filter(
        UserCourseProgress.user_id == current_user.id,
        UserCourseProgress.course_id == course.id,
    ).first()
    if not course_progress:
        course_progress = UserCourseProgress(user_id=current_user.id, course_id=course.id)

    course_progress.total_lessons = total_lessons
    course_progress.completed_lessons = completed_lessons
    course_progress.completion_rate = int((completed_lessons / total_lessons) * 100) if total_lessons else 0
    db.add(course_progress)
    db.commit()
    db.refresh(course_progress)

    return {
        "message": "Progress saved",
        "completion_rate": course_progress.completion_rate,
        "completed_lessons": course_progress.completed_lessons,
        "total_lessons": course_progress.total_lessons,
    }


@router.post("/roadmap/steps/{step_id}/toggle")
async def toggle_step_completion(
    step_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    step = db.query(RoadmapStep).join(LearningPath).filter(
        RoadmapStep.id == step_id,
        LearningPath.user_id == current_user.id
    ).first()
    if not step:
        raise HTTPException(status_code=404, detail="Roadmap step not found")

    step.is_completed = not step.is_completed
    db.commit()
    db.refresh(step)
    return {"message": "Step toggled successfully", "is_completed": step.is_completed}


# ============= Interview Prep API =============
@router.get("/interview/questions", response_model=List[InterviewQuestionResponse])
async def get_interview_questions(
    category: Optional[str] = None,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    query = db.query(InterviewQuestion)
    if category:
        query = query.filter(InterviewQuestion.category == category)
    questions = query.order_by(InterviewQuestion.order_index).all()

    # Get user progress
    progress_rows = db.query(UserInterviewProgress).filter(
        UserInterviewProgress.user_id == current_user.id
    ).all()
    progress_map = {row.question_id: row.status for row in progress_rows}

    response_data = []
    for q in questions:
        response_data.append(
            InterviewQuestionResponse(
                id=q.id,
                category=q.category,
                question=q.question,
                answer=q.answer,
                difficulty=q.difficulty,
                order_index=q.order_index,
                user_status=progress_map.get(q.id)
            )
        )
    return response_data


@router.post("/interview/questions/{question_id}/progress")
async def update_interview_progress(
    question_id: int,
    payload: UserInterviewProgressUpdate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    question = db.query(InterviewQuestion).filter(InterviewQuestion.id == question_id).first()
    if not question:
        raise HTTPException(status_code=404, detail="Question not found")

    progress = db.query(UserInterviewProgress).filter(
        UserInterviewProgress.user_id == current_user.id,
        UserInterviewProgress.question_id == question_id
    ).first()

    if not progress:
        progress = UserInterviewProgress(
            user_id=current_user.id,
            question_id=question_id,
            status=payload.status
        )
        db.add(progress)
    else:
        progress.status = payload.status
        db.add(progress)

    db.commit()
    return {"message": "Interview progress updated", "status": payload.status}


# ============= Q&A Forum API =============
@router.get("/qna/questions", response_model=List[QnaQuestionResponse])
async def get_qna_questions(
    db: Session = Depends(get_db),
):
    questions = db.query(QnaQuestion).order_by(QnaQuestion.created_at.desc()).all()
    response_data = []
    for q in questions:
        answers_count = db.query(QnaAnswer).filter(QnaAnswer.question_id == q.id).count()
        response_data.append(
            QnaQuestionResponse(
                id=q.id,
                user_id=q.user_id,
                username=q.user.username if q.user else "Unknown",
                title=q.title,
                content=q.content,
                tags=q.tags,
                created_at=q.created_at,
                updated_at=q.updated_at,
                answers_count=answers_count
            )
        )
    return response_data


@router.get("/qna/questions/{question_id}", response_model=QnaQuestionDetailResponse)
async def get_qna_question_detail(
    question_id: int,
    db: Session = Depends(get_db),
):
    q = db.query(QnaQuestion).filter(QnaQuestion.id == question_id).first()
    if not q:
        raise HTTPException(status_code=404, detail="Discussion thread not found")

    answers = db.query(QnaAnswer).filter(QnaAnswer.question_id == q.id).order_by(QnaAnswer.created_at.asc()).all()
    answers_count = len(answers)

    answers_list = []
    for a in answers:
        answers_list.append(
            QnaAnswerResponse(
                id=a.id,
                question_id=a.question_id,
                user_id=a.user_id,
                username=a.user.username if a.user else "Unknown",
                content=a.content,
                is_accepted=a.is_accepted,
                created_at=a.created_at,
                updated_at=a.updated_at
            )
        )

    return QnaQuestionDetailResponse(
        id=q.id,
        user_id=q.user_id,
        username=q.user.username if q.user else "Unknown",
        title=q.title,
        content=q.content,
        tags=q.tags,
        created_at=q.created_at,
        updated_at=q.updated_at,
        answers_count=answers_count,
        answers=answers_list
    )


@router.post("/qna/questions", response_model=QnaQuestionResponse, status_code=status.HTTP_201_CREATED)
async def create_qna_question(
    payload: QnaQuestionCreate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    new_q = QnaQuestion(
        user_id=current_user.id,
        title=payload.title,
        content=payload.content,
        tags=payload.tags
    )
    db.add(new_q)
    db.commit()
    db.refresh(new_q)

    return QnaQuestionResponse(
        id=new_q.id,
        user_id=new_q.user_id,
        username=current_user.username,
        title=new_q.title,
        content=new_q.content,
        tags=new_q.tags,
        created_at=new_q.created_at,
        updated_at=new_q.updated_at,
        answers_count=0
    )


@router.post("/qna/questions/{question_id}/answers", response_model=QnaAnswerResponse, status_code=status.HTTP_201_CREATED)
async def create_qna_answer(
    question_id: int,
    payload: QnaAnswerCreate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    q = db.query(QnaQuestion).filter(QnaQuestion.id == question_id).first()
    if not q:
        raise HTTPException(status_code=404, detail="Discussion thread not found")

    new_a = QnaAnswer(
        question_id=question_id,
        user_id=current_user.id,
        content=payload.content
    )
    db.add(new_a)
    db.commit()
    db.refresh(new_a)

    return QnaAnswerResponse(
        id=new_a.id,
        question_id=new_a.question_id,
        user_id=new_a.user_id,
        username=current_user.username,
        content=new_a.content,
        is_accepted=new_a.is_accepted,
        created_at=new_a.created_at,
        updated_at=new_a.updated_at
    )


@router.post("/qna/answers/{answer_id}/accept")
async def accept_qna_answer(
    answer_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    answer = db.query(QnaAnswer).filter(QnaAnswer.id == answer_id).first()
    if not answer:
        raise HTTPException(status_code=404, detail="Answer not found")

    question = answer.question
    if not question:
        raise HTTPException(status_code=404, detail="Associated thread not found")

    if question.user_id != current_user.id:
        raise HTTPException(status_code=403, detail="Only the thread author can accept answers")

    answer.is_accepted = not answer.is_accepted
    db.commit()
    db.refresh(answer)

    return {"message": "Answer status updated", "is_accepted": answer.is_accepted}


# ============= Quiz API =============
@router.get("/lessons/{lesson_id}/quiz")
async def get_quiz_questions(
    lesson_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    lesson = db.query(Lesson).filter(Lesson.id == lesson_id).first()
    if not lesson:
        raise HTTPException(status_code=404, detail="Lesson not found")

    questions = (
        db.query(QuizQuestion)
        .filter(QuizQuestion.lesson_id == lesson_id)
        .order_by(QuizQuestion.order_index)
        .all()
    )
    return [
        {
            "id": q.id,
            "question_text": q.question_text,
            "question_type": q.question_type,
            "options": q.options or [],
            "order_index": q.order_index,
        }
        for q in questions
    ]


@router.post("/lessons/{lesson_id}/quiz/submit")
async def submit_quiz(
    lesson_id: int,
    payload: dict,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    lesson = db.query(Lesson).filter(Lesson.id == lesson_id).first()
    if not lesson:
        raise HTTPException(status_code=404, detail="Lesson not found")

    answers = payload.get("answers", {})
    questions = db.query(QuizQuestion).filter(QuizQuestion.lesson_id == lesson_id).all()
    question_map = {q.id: q for q in questions}

    results = []
    correct_count = 0
    for qid_str, user_answer in answers.items():
        qid = int(qid_str)
        q = question_map.get(qid)
        if not q:
            continue
        is_correct = user_answer.strip().lower() == q.correct_answer.strip().lower()
        if is_correct:
            correct_count += 1
        results.append({
            "question_id": qid,
            "is_correct": is_correct,
            "correct_answer": q.correct_answer,
            "explanation": q.explanation,
        })

    total = len(questions)
    score = int((correct_count / total) * 100) if total else 0

    progress = db.query(UserLessonProgress).filter(
        UserLessonProgress.user_id == current_user.id,
        UserLessonProgress.lesson_id == lesson_id,
    ).first()
    if not progress:
        progress = UserLessonProgress(user_id=current_user.id, lesson_id=lesson_id)
    progress.score = score
    if score >= 60:
        progress.status = "completed"
    else:
        progress.status = "in_progress"
    db.add(progress)
    db.commit()

    return {"score": score, "correct": correct_count, "total": total, "results": results}


# ============= AI Chatbot API =============
import httpx
import os

@router.post("/chatbot", response_model=ChatbotResponse)
async def learning_chatbot(
    payload: ChatbotRequest,
    current_user: User = Depends(get_current_user),
):
    api_key = os.getenv("GEMINI_API_KEY")
    if api_key:
        try:
            url = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={api_key}"
            headers = {"Content-Type": "application/json"}
            data = {
                "contents": [
                    {
                        "parts": [
                            {
                                "text": (
                                    "You are Serein-AI, a premium cyberpunk-styled coding tutor. "
                                    "Format all code blocks beautifully. Keep answers relatively concise, encouraging, and highly technical. "
                                    f"User question: {payload.message}"
                                )
                            }
                        ]
                    }
                ]
            }
            async with httpx.AsyncClient() as client:
                resp = await client.post(url, json=data, headers=headers, timeout=10.0)
                if resp.status_code == 200:
                    result = resp.json()
                    reply = result["candidates"][0]["content"]["parts"][0]["text"]
                    return ChatbotResponse(reply=reply)
        except Exception as e:
            print(f"Chatbot Gemini API error: {e}")

    # Fallback response
    msg_lower = payload.message.lower()
    if "hello" in msg_lower or "hi" in msg_lower or "chào" in msg_lower:
        reply = "Chào mừng bạn đến với mạng lưới học tập SEREIN. Tôi là Serein-AI, trợ lý ảo hỗ trợ lập trình của bạn. Bạn cần tôi trợ giúp gì về Python, Java, Spring Boot hay SQL hôm nay?"
    elif "python" in msg_lower:
        reply = (
            "### Python Development\n"
            "Python hỗ trợ xử lý rất nhiều tác vụ từ Scripting đến AI/ML. \n\n"
            "Ví dụ về hàm decorator trong Python:\n"
            "```python\n"
            "def cyber_decorator(func):\n"
            "    def wrapper(*args, **kwargs):\n"
            "        print(\"[SEREIN-SECURE] Đang quét hệ thống...\")\n"
            "        return func(*args, **kwargs)\n"
            "    return wrapper\n"
            "```\n"
            "Bạn có thắc mắc gì về GIL hay cấu trúc dữ liệu trong Python không?"
        )
    elif "java" in msg_lower or "spring" in msg_lower:
        reply = (
            "### Java & Spring Boot\n"
            "Spring Framework hoạt động dựa trên hai nguyên lý cốt lõi: IoC (Inversion of Control) và DI (Dependency Injection).\n\n"
            "Sử dụng Annotation để định nghĩa controller:\n"
            "```java\n"
            "@RestController\n"
            "@RequestMapping(\"/api/v1\")\n"
            "public class LearningController {\n"
            "    // các endpoints ở đây\n"
            "}\n"
            "```\n"
            "Hãy cho tôi biết nếu bạn muốn giải thích chi tiết hơn về Spring Security hay JPA!"
        )
    elif "sql" in msg_lower or "database" in msg_lower or "db" in msg_lower:
        reply = (
            "### SQL & Database\n"
            "Một truy vấn tối ưu giúp lấy dữ liệu cực kỳ nhanh chóng.\n\n"
            "Ví dụ truy vấn thống kê tiến độ học tập:\n"
            "```sql\n"
            "SELECT user_id, COUNT(*) AS completed_steps \n"
            "FROM user_interview_progress \n"
            "WHERE status = 'mastered' \n"
            "GROUP BY user_id;\n"
            "```\n"
            "Tôi có thể giải đáp thêm về Transaction, ACID, hay các kỹ thuật tối ưu hóa Index!"
        )
    else:
        reply = (
            f"Tôi đã tiếp nhận câu hỏi của bạn: *\"{payload.message}\"*. \n\n"
            "Hệ thống SEREIN hiện đang chạy ở chế độ **Offline Demo**. Để mở khóa toàn bộ trí tuệ nhân tạo của trợ lý Serein-AI, vui lòng cấu hình biến môi trường `GEMINI_API_KEY` trong file `.env` của dự án.\n\n"
            "Dù vậy, tôi có thể hỗ trợ trực tiếp các chủ đề cốt lõi như: **Python, Java, Spring Boot, SQL**. Bạn hãy nhập các từ khóa này để thử nghiệm!"
        )
    return ChatbotResponse(reply=reply)
