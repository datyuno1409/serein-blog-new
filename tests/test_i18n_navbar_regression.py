from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


def read(path: str) -> str:
    return (ROOT / path).read_text(encoding="utf-8")


def test_migrated_navbar_and_dashboard_use_i18n_keys():
    for path in [
        "frontend/templates/site_base.html",
        "frontend/templates/learning/base.html",
        "frontend/templates/learning/dashboard.html",
    ]:
        source = read(path)
        assert "<span data-lang-vi" not in source
        assert "<span data-lang-en" not in source


def test_no_known_duplicate_language_regressions_in_frontend_sources():
    forbidden = [
        "ResourcesResources",
        "LearningLearning",
        "PortfolioPortfolio",
        "LogoutLogout",
        "Tài nguyênResources",
        "Học tậpLearning",
        "Hồ sơProfile",
        "Learning TracksLearning",
        "onclick=\"logout",
    ]
    frontend_source = "\n".join(
        path.read_text(encoding="utf-8", errors="ignore")
        for path in (ROOT / "frontend").rglob("*")
        if path.is_file() and path.suffix in {".html", ".js", ".css"}
    )
    for pattern in forbidden:
        assert pattern not in frontend_source


def test_learning_pages_share_sidebar_partial():
    for path in [
        "frontend/templates/learning/dashboard.html",
        "frontend/templates/learning/roadmap.html",
        "frontend/templates/learning/courses.html",
        "frontend/templates/learning/profile.html",
        "frontend/templates/learning/interview.html",
        "frontend/templates/learning/exam.html",
        "frontend/templates/learning/qna.html",
        "frontend/templates/learning/chatbot.html",
    ]:
        source = read(path)
        assert "partials/learning_sidebar.html" in source
        assert '<aside class="dashboard-sidebar">' not in source


def test_learning_sidebar_contains_unified_items():
    source = read("frontend/templates/partials/learning_sidebar.html")
    for key in [
        "sidebar.dashboard",
        "sidebar.roadmap",
        "sidebar.courses",
        "sidebar.exam",
        "sidebar.forum",
        "sidebar.aiAssistant",
        "sidebar.profile",
    ]:
        assert key in source
    assert "sidebar.interview" not in source
    assert "/learning/interview" not in read("frontend/templates/partials/navbar.html")


def test_vietnamese_dashboard_copy_does_not_mix_english_ui_terms():
    vi = read("frontend/assets/i18n/vi.json")
    dashboard = read("frontend/templates/learning/dashboard.html")
    forbidden = [
        "Dashboard học tập",
        "Roadmap học tập",
        "Chỉnh sửa roadmap",
        "Số roadmap",
        "Tạo roadmap",
        "roadmap hoạt động",
        "Đang chuyển về dashboard",
    ]
    for pattern in forbidden:
        assert pattern not in vi
        assert pattern not in dashboard


def test_hsk_exam_answers_render_as_interactive_buttons():
    source = read("frontend/templates/learning/hsk.html")
    assert '<button type="button" class="exam-option"' in source
    assert "data-answer" in source
    assert "exam-feedback" in source
    assert "addEventListener('click'" in source
    assert "formatSkillType" in source
    assert "formatQuestionType" in source
    assert "HSK Learning Center" not in source
    assert '<div class="exam-option">' not in source
