"""
Main FastAPI application.
"""

from typing import Optional

from fastapi import Depends, FastAPI, Request, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import HTMLResponse, RedirectResponse
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates
from jose import JWTError, jwt
from sqlalchemy.orm import Session, joinedload

from . import models
from .api import api_router
from .config import settings
from .database import Base, SessionLocal, engine, get_db
from .models.user import User, UserRole
from .page_deps import build_learning_context, get_user_from_request
from .seed_learning import INTENSITY_LABELS, run_seed_if_needed
from .seed_hsk import seed_hsk_data
from .roadmap_data import CAREER_LEVELS, ROADMAP_TOPICS, ROADMAP_QUOTE


Base.metadata.create_all(bind=engine)


def _role_value(role) -> str:
    return str(getattr(role, "value", role) or "").lower()


def _ensure_env_admin():
    if not settings.admin_password:
        return

    db = SessionLocal()
    try:
        user = db.query(User).filter(User.username == settings.admin_username).first()
        if user:
            user.email = user.email or settings.admin_email
            user.full_name = user.full_name or "Serein Admin"
            user.password_hash = User.hash_password(settings.admin_password)
            user.role = UserRole.ADMIN
        else:
            user = User(
                username=settings.admin_username,
                email=settings.admin_email,
                full_name="Serein Admin",
                password_hash=User.hash_password(settings.admin_password),
                role=UserRole.ADMIN,
            )
            db.add(user)
        db.commit()
    finally:
        db.close()


try:
    _ensure_env_admin()
except Exception as exc:
    print(f"[admin] skipped env admin setup: {exc}")

try:
    run_seed_if_needed()
except Exception as exc:
    print(f"[seed] skipped: {exc}")

try:
    seed_hsk_data()
except Exception as exc:
    print(f"[seed-hsk] skipped: {exc}")


app = FastAPI(
    title="Serein Learning Platform API",
    description="Cyberpunk-inspired learning platform",
    version="2.0.0",
    docs_url="/api/docs",
    redoc_url="/api/redoc",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.allowed_origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.middleware("http")
async def admin_auth_middleware(request: Request, call_next):
    path = request.url.path
    if path.startswith("/admin"):
        if path not in {"/admin", "/admin/"} and not path.startswith(("/admin/css", "/admin/js")):
            token = request.cookies.get("access_token")
            is_authenticated = False
            if token:
                try:
                    payload = jwt.decode(token, settings.secret_key, algorithms=[settings.algorithm])
                    username = payload.get("sub")
                    if username:
                        db = SessionLocal()
                        try:
                            user = db.query(User).filter(User.username == username).first()
                            if user and _role_value(user.role) == "admin":
                                is_authenticated = True
                        finally:
                            db.close()
                except JWTError:
                    pass
            if not is_authenticated:
                return RedirectResponse(url="/admin", status_code=status.HTTP_303_SEE_OTHER)

    return await call_next(request)


app.mount("/assets", StaticFiles(directory="frontend/assets"), name="assets")
app.mount("/admin/css", StaticFiles(directory="admin/css"), name="admin_css")
app.mount("/admin/js", StaticFiles(directory="admin/js"), name="admin_js")

templates = Jinja2Templates(directory=["frontend/templates", "."])

app.include_router(api_router)


def _page_context(request: Request, db: Session, nav_active: str, **extra):
    user = get_user_from_request(request, db)
    return build_learning_context(request, user, nav_active=nav_active, **extra)


def _get_user_courses(db: Session, user):
    from .models.learning import Course, LearningPath, RoadmapStep, UserCourseProgress

    paths = db.query(LearningPath).filter(LearningPath.user_id == user.id).all()
    path_ids = [path.id for path in paths]
    courses = db.query(Course).filter(Course.learning_path_id.in_(path_ids)).all() if path_ids else []
    progress_rows = db.query(UserCourseProgress).filter(UserCourseProgress.user_id == user.id).all()
    progress_map = {row.course_id: row.completion_rate for row in progress_rows}
    active_path = next((path for path in paths if path.active), paths[0] if paths else None)
    roadmap_steps = []
    if active_path:
        roadmap_steps = (
            db.query(RoadmapStep)
            .filter(RoadmapStep.learning_path_id == active_path.id)
            .order_by(RoadmapStep.order_index)
            .all()
        )
    return paths, courses, progress_map, active_path, roadmap_steps


@app.get("/", response_class=HTMLResponse)
async def home(request: Request, db: Session = Depends(get_db)):
    return templates.TemplateResponse("index.html", _page_context(request, db, nav_active="home"))


@app.get("/home", response_class=HTMLResponse)
async def home_alias(request: Request, db: Session = Depends(get_db)):
    return templates.TemplateResponse("index.html", _page_context(request, db, nav_active="home"))


@app.get("/about")
async def about():
    return RedirectResponse(url="/", status_code=status.HTTP_307_TEMPORARY_REDIRECT)


@app.get("/portfolio")
async def portfolio():
    return RedirectResponse(url="/", status_code=status.HTTP_307_TEMPORARY_REDIRECT)


@app.get("/blog", response_class=HTMLResponse)
async def blog(request: Request, db: Session = Depends(get_db)):
    return templates.TemplateResponse("blog.html", _page_context(request, db, nav_active="blog"))


@app.get("/post", response_class=HTMLResponse)
async def post(request: Request, db: Session = Depends(get_db)):
    return templates.TemplateResponse("post.html", _page_context(request, db, nav_active="blog"))


@app.get("/login", response_class=HTMLResponse)
async def login_page(request: Request, db: Session = Depends(get_db)):
    user = get_user_from_request(request, db)
    if user:
        return RedirectResponse(url="/learning", status_code=status.HTTP_307_TEMPORARY_REDIRECT)
    return templates.TemplateResponse("learning/login.html", _page_context(request, db, nav_active="login"))


@app.get("/register", response_class=HTMLResponse)
async def register_page(request: Request, db: Session = Depends(get_db)):
    return templates.TemplateResponse("learning/register.html", _page_context(request, db, nav_active="register"))


@app.get("/learning", response_class=HTMLResponse)
async def learning_dashboard(request: Request):
    db = SessionLocal()
    try:
        user = get_user_from_request(request, db)
        if not user:
            return RedirectResponse(url="/login", status_code=status.HTTP_307_TEMPORARY_REDIRECT)

        paths, courses, progress_map, active_path, roadmap_steps = _get_user_courses(db, user)
        completed_lessons = db.query(models.UserLessonProgress).filter(
            models.UserLessonProgress.user_id == user.id,
            models.UserLessonProgress.status == "completed",
        ).count()
        roadmap_completed = sum(1 for step in roadmap_steps if step.is_completed)
        stats = {
            "paths": len(paths),
            "courses": len(courses),
            "completed_lessons": completed_lessons,
            "roadmap_steps": len(roadmap_steps),
            "roadmap_completed": roadmap_completed,
        }

        return templates.TemplateResponse(
            "learning/dashboard.html",
            build_learning_context(
                request,
                user,
                nav_active="learning",
                active_path=active_path,
                courses=courses,
                progress_map=progress_map,
                roadmap_steps=roadmap_steps,
                intensity_options=INTENSITY_LABELS,
                stats=stats,
            ),
        )
    finally:
        db.close()


@app.get("/my-course", response_class=HTMLResponse)
async def my_courses_page(request: Request):
    db = SessionLocal()
    try:
        user = get_user_from_request(request, db)
        if not user:
            return RedirectResponse(url="/login", status_code=status.HTTP_307_TEMPORARY_REDIRECT)

        paths, courses, progress_map, active_path, roadmap_steps = _get_user_courses(db, user)
        stats = {
            "paths": len(paths),
            "courses": len(courses),
            "roadmap_steps": len(roadmap_steps),
            "roadmap_completed": sum(1 for step in roadmap_steps if step.is_completed),
        }
        return templates.TemplateResponse(
            "learning/courses.html",
            build_learning_context(
                request,
                user,
                nav_active="courses",
                active_path=active_path,
                courses=courses,
                progress_map=progress_map,
                roadmap_steps=roadmap_steps,
                stats=stats,
            ),
        )
    finally:
        db.close()


@app.get("/my-profile", response_class=HTMLResponse)
async def my_profile_page(request: Request):
    db = SessionLocal()
    try:
        user = get_user_from_request(request, db)
        if not user:
            return RedirectResponse(url="/login", status_code=status.HTTP_307_TEMPORARY_REDIRECT)
        return templates.TemplateResponse(
            "learning/profile.html",
            build_learning_context(request, user, nav_active="my-profile"),
        )
    finally:
        db.close()


@app.get("/learning/roadmap", response_class=HTMLResponse)
async def roadmap_wizard_page(request: Request):
    db = SessionLocal()
    try:
        user = get_user_from_request(request, db)
        if not user:
            return RedirectResponse(url="/login", status_code=status.HTTP_307_TEMPORARY_REDIRECT)

        _paths, courses, progress_map, active_path, roadmap_steps = _get_user_courses(db, user)
        return templates.TemplateResponse(
            "learning/roadmap.html",
            build_learning_context(
                request,
                user,
                nav_active="roadmap",
                courses=courses,
                progress_map=progress_map,
                active_path=active_path,
                roadmap_steps=roadmap_steps,
                intensity_options=INTENSITY_LABELS,
            ),
        )
    finally:
        db.close()


@app.get("/learning/session/{slug}", response_class=HTMLResponse)
async def learning_session(request: Request, slug: str):
    from .models.learning import Course, Lesson, QuizQuestion, UserLessonProgress

    db = SessionLocal()
    try:
        user = get_user_from_request(request, db)
        if not user:
            return RedirectResponse(url="/login", status_code=status.HTTP_307_TEMPORARY_REDIRECT)

        course: Optional[Course] = db.query(Course).filter(Course.slug == slug).first()
        if not course:
            return templates.TemplateResponse(
                "404.html",
                build_learning_context(request, user, nav_active="learning", error="Course not found"),
                status_code=404,
            )

        lessons = (
            db.query(Lesson)
            .filter(Lesson.course_id == course.id)
            .options(joinedload(Lesson.flashcards), joinedload(Lesson.quiz_questions))
            .order_by(Lesson.order_index)
            .all()
        )
        lesson_progress_rows = db.query(UserLessonProgress).filter(UserLessonProgress.user_id == user.id).all()
        lesson_progress_map = {row.lesson_id: row for row in lesson_progress_rows}

        return templates.TemplateResponse(
            "learning/session.html",
            build_learning_context(
                request,
                user,
                nav_active="learning",
                course=course,
                lessons=lessons,
                lesson_progress_map=lesson_progress_map,
            ),
        )
    finally:
        db.close()


@app.get("/learning/interview", response_class=HTMLResponse)
async def interview_prep_page(request: Request):
    db = SessionLocal()
    try:
        user = get_user_from_request(request, db)
        if not user:
            return RedirectResponse(url="/login", status_code=status.HTTP_307_TEMPORARY_REDIRECT)
        return templates.TemplateResponse(
            "learning/interview.html",
            build_learning_context(request, user, nav_active="interview"),
        )
    finally:
        db.close()


@app.get("/learning/exam", response_class=HTMLResponse)
async def virtual_exam_page(request: Request):
    db = SessionLocal()
    try:
        user = get_user_from_request(request, db)
        if not user:
            return RedirectResponse(url="/login", status_code=status.HTTP_307_TEMPORARY_REDIRECT)
        _paths, courses, progress_map, active_path, roadmap_steps = _get_user_courses(db, user)
        return templates.TemplateResponse(
            "learning/exam.html",
            build_learning_context(
                request,
                user,
                nav_active="exam",
                courses=courses,
                progress_map=progress_map,
                active_path=active_path,
                roadmap_steps=roadmap_steps,
            ),
        )
    finally:
        db.close()


@app.get("/learning/qna", response_class=HTMLResponse)
async def qna_forum_page(request: Request):
    db = SessionLocal()
    try:
        user = get_user_from_request(request, db)
        if not user:
            return RedirectResponse(url="/login", status_code=status.HTTP_307_TEMPORARY_REDIRECT)
        return templates.TemplateResponse(
            "learning/qna.html",
            build_learning_context(request, user, nav_active="qna"),
        )
    finally:
        db.close()


@app.get("/learning/chatbot", response_class=HTMLResponse)
async def chatbot_terminal_page(request: Request):
    db = SessionLocal()
    try:
        user = get_user_from_request(request, db)
        if not user:
            return RedirectResponse(url="/login", status_code=status.HTTP_307_TEMPORARY_REDIRECT)
        return templates.TemplateResponse(
            "learning/chatbot.html",
            build_learning_context(request, user, nav_active="chatbot"),
        )
    finally:
        db.close()


@app.get("/learning/hsk", response_class=HTMLResponse)
async def hsk_learning_page(request: Request):
    db = SessionLocal()
    try:
        user = get_user_from_request(request, db)
        if not user:
            return RedirectResponse(url="/login", status_code=status.HTTP_307_TEMPORARY_REDIRECT)
        return templates.TemplateResponse(
            "learning/hsk.html",
            build_learning_context(request, user, nav_active="hsk"),
        )
    finally:
        db.close()


@app.get("/roadmap", response_class=HTMLResponse)
async def roadmap_page(request: Request, db: Session = Depends(get_db)):
    return templates.TemplateResponse(
        "roadmap_page.html",
        _page_context(
            request,
            db,
            nav_active="roadmap",
            career_levels=CAREER_LEVELS,
            roadmap_topics=ROADMAP_TOPICS,
            roadmap_quote=ROADMAP_QUOTE,
        ),
    )


@app.get("/admin", response_class=HTMLResponse)
async def admin_login(request: Request):
    return templates.TemplateResponse("admin/index.html", {"request": request})


@app.get("/admin/dashboard", response_class=HTMLResponse)
async def admin_dashboard(request: Request):
    return templates.TemplateResponse("admin/dashboard.html", {"request": request})


@app.get("/admin/articles", response_class=HTMLResponse)
async def admin_articles(request: Request):
    return templates.TemplateResponse("admin/articles.html", {"request": request})


@app.get("/admin/projects", response_class=HTMLResponse)
async def admin_projects(request: Request):
    return templates.TemplateResponse("admin/projects.html", {"request": request})


@app.get("/admin/settings", response_class=HTMLResponse)
async def admin_settings(request: Request):
    return templates.TemplateResponse("admin/settings.html", {"request": request})


@app.get("/admin/appearance", response_class=HTMLResponse)
async def admin_appearance(request: Request):
    return templates.TemplateResponse("admin/settings.html", {"request": request})


@app.get("/health")
async def health_check():
    return {
        "status": "healthy",
        "version": "2.0.0",
        "framework": "FastAPI",
        "database": "SQLite",
    }
