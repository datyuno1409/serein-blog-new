from __future__ import annotations

from dataclasses import dataclass
import os
from uuid import uuid4

import httpx


@dataclass(frozen=True)
class ExamQuestion:
    id: str
    section: str
    question_type: str
    prompt: str
    options: list[str]
    answer: str
    points: int = 1


@dataclass(frozen=True)
class ExamSession:
    session_id: str
    domain: str
    track: str
    target_level: str
    duration_minutes: int
    provider: str
    source_type: str
    freshness: str
    source_url: str | None
    questions: list[ExamQuestion]


EXAM_BLUEPRINTS: dict[str, dict] = {
    "english:toeic": {
        "duration_minutes": 120,
        "source_url": "external-provider://toeic/latest",
        "prompts": [
            ("Vocabulary", "Choose the best synonym for 'purchase'.", ["buy", "sell", "delay", "cancel"], "buy"),
            ("Reading", "The meeting was postponed. What happened to the meeting?", ["It was canceled", "It was delayed", "It was finished", "It was confirmed"], "It was delayed"),
            ("Listening", "A speaker says: 'The report is due Friday.' When is the report due?", ["Monday", "Wednesday", "Friday", "Sunday"], "Friday"),
        ],
    },
    "english:ielts": {
        "duration_minutes": 165,
        "source_url": "external-provider://ielts/latest",
        "prompts": [
            ("Reading", "Identify the main idea of a short academic paragraph.", ["detail", "main idea", "example", "citation"], "main idea"),
            ("Writing", "Which sentence has the clearest topic sentence?", ["Because weather.", "This essay discusses urban transport.", "And it is.", "Transport maybe."], "This essay discusses urban transport."),
            ("Listening", "A lecture mentions two causes. What should you note?", ["only names", "causes and examples", "speaker age", "room number"], "causes and examples"),
        ],
    },
    "chinese:hsk": {
        "duration_minutes": 90,
        "source_url": "external-provider://hsk/latest",
        "prompts": [
            ("Vocabulary", "水 means what?", ["tea", "water", "rice", "book"], "water"),
            ("Pinyin", "Choose the pinyin for 你好.", ["nǐ hǎo", "nǐ ma", "hǎo ma", "xiè xie"], "nǐ hǎo"),
            ("Reading", "我学习中文。What is being studied?", ["English", "Chinese", "Java", "Math"], "Chinese"),
        ],
    },
    "programming:python": {
        "duration_minutes": 60,
        "source_url": None,
        "prompts": [
            ("Syntax", "Which keyword defines a Python function?", ["func", "def", "function", "lambda"], "def"),
            ("Debugging", "What does IndexError usually mean?", ["bad indentation", "missing import", "index out of range", "wrong password"], "index out of range"),
            ("Problem solving", "Which structure stores key-value pairs?", ["list", "dict", "tuple", "set"], "dict"),
        ],
    },
    "java-backend:spring-boot": {
        "duration_minutes": 75,
        "source_url": None,
        "prompts": [
            ("Spring", "Which annotation marks a REST controller?", ["@Service", "@Entity", "@RestController", "@Bean"], "@RestController"),
            ("Persistence", "Which Spring project simplifies JPA repositories?", ["Spring Data JPA", "Spring Batch", "Spring Shell", "Spring AMQP"], "Spring Data JPA"),
            ("REST", "Which HTTP method usually creates a resource?", ["GET", "POST", "DELETE", "HEAD"], "POST"),
        ],
    },
}


EXAM_SESSION_CACHE: dict[str, ExamSession] = {}


def _blueprint_key(domain: str, track: str) -> str:
    key = f"{domain}:{track}"
    if key in EXAM_BLUEPRINTS:
        return key
    if domain == "english":
        return "english:toeic"
    if domain == "chinese":
        return "chinese:hsk"
    if domain == "java-backend":
        return "java-backend:spring-boot"
    return "programming:python"


def _question_from_payload(item: dict, index: int) -> ExamQuestion | None:
    try:
        options = item.get("options") or []
        answer = item.get("answer") or item.get("correct_answer")
        if not isinstance(options, list) or not answer:
            return None
        return ExamQuestion(
            id=str(item.get("id") or f"q{index + 1}"),
            section=str(item.get("section") or "General"),
            question_type=str(item.get("question_type") or "multiple_choice"),
            prompt=str(item["prompt"]),
            options=[str(option) for option in options],
            answer=str(answer),
            points=int(item.get("points") or 1),
        )
    except (KeyError, TypeError, ValueError):
        return None


def _load_external_questions(domain: str, track: str, target_level: str, question_count: int) -> tuple[list[ExamQuestion], str | None, str | None]:
    base_url = os.getenv("EXAM_SOURCE_API_URL")
    if not base_url:
        return [], None, None

    api_key = os.getenv("EXAM_SOURCE_API_KEY")
    headers = {"Accept": "application/json"}
    if api_key:
        headers["Authorization"] = f"Bearer {api_key}"

    try:
        with httpx.Client(timeout=8.0) as client:
            response = client.get(
                base_url.rstrip("/") + "/questions",
                params={
                    "domain": domain,
                    "track": track,
                    "targetLevel": target_level,
                    "limit": question_count,
                },
                headers=headers,
            )
            response.raise_for_status()
            payload = response.json()
    except Exception:
        return [], base_url, "external-api-error-fallback"

    raw_questions = payload.get("questions") if isinstance(payload, dict) else payload
    if not isinstance(raw_questions, list):
        return [], base_url, "external-api-invalid-fallback"

    questions = []
    for index, item in enumerate(raw_questions[:question_count]):
        if isinstance(item, dict):
            question = _question_from_payload(item, index)
            if question:
                questions.append(question)
    source_url = payload.get("source_url", base_url) if isinstance(payload, dict) else base_url
    return questions, source_url, "external-api-live"


def serialize_question(question: ExamQuestion, include_answer: bool = False) -> dict:
    data = {
        "id": question.id,
        "section": question.section,
        "question_type": question.question_type,
        "prompt": question.prompt,
        "options": question.options,
        "points": question.points,
    }
    if include_answer:
        data["answer"] = question.answer
    return data


def deserialize_question(data: dict) -> ExamQuestion:
    return ExamQuestion(
        id=str(data["id"]),
        section=str(data["section"]),
        question_type=str(data["question_type"]),
        prompt=str(data["prompt"]),
        options=[str(option) for option in data.get("options", [])],
        answer=str(data["answer"]),
        points=int(data.get("points") or 1),
    )


def start_exam_session(domain: str, track: str, target_level: str, question_count: int) -> ExamSession:
    blueprint = EXAM_BLUEPRINTS[_blueprint_key(domain, track)]
    external_questions, external_source_url, external_freshness = _load_external_questions(domain, track, target_level, question_count)

    if external_questions:
        questions = external_questions
        provider = "external-exam-api"
        source_type = "external-api-live"
        freshness = external_freshness or "external-api-live"
        source_url = external_source_url
    else:
        raw_prompts = blueprint["prompts"]
        questions = []
        for index in range(question_count):
            section, prompt, options, answer = raw_prompts[index % len(raw_prompts)]
            questions.append(
                ExamQuestion(
                    id=f"q{index + 1}",
                    section=section,
                    question_type="multiple_choice",
                    prompt=f"[{target_level}] {prompt}",
                    options=list(options),
                    answer=answer,
                )
            )
        provider = "curated-fallback-provider"
        source_type = "external-api-ready" if blueprint.get("source_url") else "internal-bank"
        freshness = external_freshness or "fallback-curated-bank"
        source_url = external_source_url or blueprint.get("source_url")

    session = ExamSession(
        session_id=str(uuid4()),
        domain=domain,
        track=track,
        target_level=target_level,
        duration_minutes=blueprint["duration_minutes"],
        provider=provider,
        source_type=source_type,
        freshness=freshness,
        source_url=source_url,
        questions=questions,
    )
    EXAM_SESSION_CACHE[session.session_id] = session
    return session


def grade_questions(questions: list[ExamQuestion], answers: dict[str, str]) -> tuple[int, int]:
    total = sum(question.points for question in questions)
    score = sum(question.points for question in questions if answers.get(question.id) == question.answer)
    return score, total


def grade_exam_session(session_id: str, answers: dict[str, str]) -> tuple[int, int]:
    session = EXAM_SESSION_CACHE.get(session_id)
    if not session:
        return 0, 0
    return grade_questions(session.questions, answers)
