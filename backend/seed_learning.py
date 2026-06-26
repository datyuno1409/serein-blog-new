from __future__ import annotations

from dataclasses import dataclass
from typing import Iterable

from sqlalchemy.orm import Session

from backend.database import SessionLocal
from backend.models.learning import Course, Flashcard, LearningPath, Lesson, QuizQuestion, RoadmapStep
from backend.models.user import User, UserRole
from backend.learning_sources import get_learning_resources
from backend.models.interaction import InterviewQuestion
from backend.seed_chinese import (
    CHINESE_SURVIVAL_CARDS, CHINESE_SURVIVAL_HANZI, CHINESE_SURVIVAL_QUIZ,
    CHINESE_HSK1_CARDS, CHINESE_HSK1_QUIZ,
    CHINESE_CONVERSATION_CARDS, CHINESE_CONVERSATION_QUIZ,
)
from backend.seed_english import (
    ENGLISH_COMMUNICATION_CARDS, ENGLISH_COMMUNICATION_QUIZ,
    ENGLISH_TOEIC_CARDS, ENGLISH_TOEIC_QUIZ,
    ENGLISH_GRAMMAR_CARDS, ENGLISH_GRAMMAR_QUIZ,
)


INTENSITY_LABELS = {
    0.5: "30 min/day",
    1.0: "1 hour/day",
    1.5: "1.5 hours/day",
    2.0: "2 hours/day",
    2.5: "2.5 hours/day",
    3.0: "3 hours/day",
    4.0: "4 hours/day",
    6.0: "6 hours/day",
    8.0: "8 hours/day",
}


TRACK_CATALOG: dict[str, dict[str, dict]] = {
    "english": {
        "toeic": {
            "label": "TOEIC",
            "levels": ["100", "200", "300", "400", "500", "600", "700"],
            "templates": ["english-toeic-vocabulary", "english-grammar-essentials", "english-communication"],
            "exam": {"duration_minutes": 120, "provider": "exam_api"},
        },
        "ielts": {
            "label": "IELTS",
            "levels": ["1.0", "2.0", "3.0", "4.0", "5.0", "6.0", "7.0"],
            "templates": ["english-grammar-essentials", "english-communication", "english-toeic-vocabulary"],
            "exam": {"duration_minutes": 165, "provider": "exam_api"},
        },
        "communication": {
            "label": "Giao tiếp",
            "levels": ["beginner", "intermediate", "advanced"],
            "templates": ["english-communication", "english-grammar-essentials"],
            "exam": {"duration_minutes": 45, "provider": "internal"},
        },
    },
    "chinese": {
        "hsk": {
            "label": "HSK",
            "levels": ["1", "2", "3", "4", "5", "6"],
            "templates": ["chinese-hsk1", "chinese-survival", "chinese-conversation"],
            "exam": {"duration_minutes": 90, "provider": "exam_api"},
        },
        "conversation": {
            "label": "Giao tiếp tiếng Trung",
            "levels": ["beginner", "intermediate", "advanced"],
            "templates": ["chinese-survival", "chinese-conversation"],
            "exam": {"duration_minutes": 45, "provider": "internal"},
        },
    },
    "programming": {
        "python": {
            "label": "Python",
            "levels": ["beginner", "intermediate", "advanced"],
            "templates": ["programming-python", "programming-javascript"],
            "exam": {"duration_minutes": 60, "provider": "internal"},
        },
        "javascript": {
            "label": "JavaScript",
            "levels": ["beginner", "intermediate", "advanced"],
            "templates": ["programming-javascript", "programming-python"],
            "exam": {"duration_minutes": 60, "provider": "internal"},
        },
        "fullstack": {
            "label": "Full-stack",
            "levels": ["beginner", "intermediate", "advanced"],
            "templates": ["programming-python", "programming-javascript"],
            "exam": {"duration_minutes": 75, "provider": "internal"},
        },
    },
    "java-backend": {
        "spring-boot": {
            "label": "Spring Boot",
            "levels": ["beginner", "intermediate", "advanced"],
            "templates": ["java-backend-springboot", "java-backend-microservices", "java-backend-websocket"],
            "exam": {"duration_minutes": 75, "provider": "internal"},
        },
        "microservices": {
            "label": "Microservices",
            "levels": ["beginner", "intermediate", "advanced"],
            "templates": ["java-backend-microservices", "java-backend-springboot", "java-backend-websocket"],
            "exam": {"duration_minutes": 90, "provider": "internal"},
        },
        "realtime": {
            "label": "Realtime/WebSocket",
            "levels": ["beginner", "intermediate", "advanced"],
            "templates": ["java-backend-websocket", "java-backend-springboot"],
            "exam": {"duration_minutes": 60, "provider": "internal"},
        },
    },
}


@dataclass(frozen=True)
class CourseTemplate:
    key: str
    title: str
    category: str
    language: str
    level: str
    summary: str
    estimated_hours: int
    tags: list[str]
    lessons: list[dict]
    roadmap_steps: list[dict]


COURSE_LIBRARY: dict[str, CourseTemplate] = {
    "programming-python": CourseTemplate(
        key="programming-python",
        title="Python Developer Foundations",
        category="programming",
        language="en",
        level="beginner",
        summary="Build Python fundamentals through syntax drills, mini coding tasks, debugging reps, and applied quizzes.",
        estimated_hours=28,
        tags=["python", "backend", "logic", "beginner"],
        lessons=[
            {
                "title": "Core syntax flashcards",
                "type": "flashcard",
                "content": "Memorize Python primitives, conditions, loops, and function keywords with compact recall rounds.",
                "duration": 20,
                "xp": 30,
                "cards": [
                    ("def", "Defines a function", None, "def greet(name):"),
                    ("for", "Loops over an iterable", None, "for item in items:"),
                    ("len()", "Returns collection length", None, "len(users)"),
                ],
            },
            {
                "title": "Write a command line mini script",
                "type": "writing",
                "content": "Write a tiny Python script that reads input, transforms it, and prints a result.",
                "duration": 25,
                "xp": 40,
            },
            {
                "title": "Debugging patterns quiz",
                "type": "quiz",
                "content": "Answer multiple-choice questions about stack traces, type mismatches, and off-by-one bugs.",
                "duration": 18,
                "xp": 35,
            },
            {
                "title": "Functions and reuse",
                "type": "flashcard",
                "content": "Learn parameter passing, return values, and code reuse through rapid review cards.",
                "duration": 20,
                "xp": 30,
                "cards": [
                    ("return", "Sends a value back from a function", None, "return total"),
                    ("args", "Positional arguments", None, "greet('Lan')"),
                    ("keyword args", "Named arguments in function calls", None, "open(file='notes.txt')"),
                ],
            },
        ],
        roadmap_steps=[
            {"title": "Syntax bootcamp", "description": "Lock in variables, conditions, loops, and functions before moving to projects.", "phase": "foundation", "step_type": "flashcard", "estimated_days": 4, "resource_hint": "Python cards + syntax drills"},
            {"title": "Mini script reps", "description": "Ship tiny scripts daily to connect syntax with practical problem solving.", "phase": "practice", "step_type": "writing", "estimated_days": 5, "resource_hint": "Command line exercises"},
            {"title": "Debugging and review", "description": "Use quick quizzes to recognize common Python bugs and reading patterns.", "phase": "reinforcement", "step_type": "quiz", "estimated_days": 3, "resource_hint": "Bug fixing quiz set"},
        ],
    ),
    "programming-javascript": CourseTemplate(
        key="programming-javascript",
        title="JavaScript Frontend Builder",
        category="programming",
        language="en",
        level="intermediate",
        summary="Practice DOM state, events, async flow, and UI architecture with task-driven front-end exercises.",
        estimated_hours=32,
        tags=["javascript", "frontend", "dom", "async"],
        lessons=[
            {
                "title": "DOM and event flashcards",
                "type": "flashcard",
                "content": "Recall selectors, event handlers, and state update patterns used in real front-end flows.",
                "duration": 20,
                "xp": 28,
                "cards": [
                    ("querySelector", "Selects the first matching DOM element", None, "document.querySelector('.card')"),
                    ("addEventListener", "Registers an event callback", None, "button.addEventListener('click', save)"),
                    ("async/await", "Readable async control flow", None, "const data = await fetch(url)"),
                ],
            },
            {
                "title": "State handling writing lab",
                "type": "writing",
                "content": "Explain how a user action updates UI state, local cache, and the remote API.",
                "duration": 24,
                "xp": 38,
            },
            {
                "title": "Async behavior quiz",
                "type": "quiz",
                "content": "Pick the correct async patterns for loading, saving, and handling API failures.",
                "duration": 20,
                "xp": 36,
            },
        ],
        roadmap_steps=[
            {"title": "DOM control", "description": "Get comfortable with selectors, handlers, and UI feedback loops.", "phase": "foundation", "step_type": "flashcard", "estimated_days": 4, "resource_hint": "Interactive DOM cards"},
            {"title": "State design", "description": "Model what changes in the UI after each action and write it down clearly.", "phase": "practice", "step_type": "writing", "estimated_days": 4, "resource_hint": "State architecture prompts"},
            {"title": "Async confidence", "description": "Train your mental model for asynchronous requests and error handling.", "phase": "reinforcement", "step_type": "quiz", "estimated_days": 3, "resource_hint": "Async scenarios"},
        ],
    ),
    "english-communication": CourseTemplate(
        key="english-communication",
        title="English Communication Sprint",
        category="language",
        language="en",
        level="intermediate",
        summary="Master 30+ workplace phrases, email language, and professional communication through flashcards, writing, and interactive quizzes.",
        estimated_hours=22,
        tags=["english", "communication", "email", "workplace"],
        lessons=[
            {"title": "Professional phrase flashcards", "type": "flashcard", "content": "Memorize 30 common openings, transitions, and follow-up phrases for study and work.", "duration": 25, "xp": 40, "cards": ENGLISH_COMMUNICATION_CARDS},
            {"title": "Daily reflection writing", "type": "writing", "content": "Write a short daily reflection about what you learned and how you will apply it.", "duration": 20, "xp": 32},
            {"title": "Communication quiz", "type": "quiz", "content": "Test your knowledge of professional phrases and email etiquette with multiple-choice and fill-in-blank questions.", "duration": 20, "xp": 35, "quiz": ENGLISH_COMMUNICATION_QUIZ},
        ],
        roadmap_steps=[
            {"title": "Phrase bank", "description": "Collect and review useful expressions for work, study, and social replies.", "phase": "foundation", "step_type": "flashcard", "estimated_days": 4, "resource_hint": "Phrase deck"},
            {"title": "Output reps", "description": "Write short summaries and answers to build expressive confidence.", "phase": "practice", "step_type": "writing", "estimated_days": 4, "resource_hint": "Daily writing prompts"},
            {"title": "Context accuracy", "description": "Use quizzes to lock grammar and natural wording into place.", "phase": "reinforcement", "step_type": "quiz", "estimated_days": 3, "resource_hint": "Usage quiz"},
        ],
    ),
    "english-toeic-vocabulary": CourseTemplate(
        key="english-toeic-vocabulary",
        title="TOEIC Vocabulary Builder",
        category="language",
        language="en",
        level="intermediate",
        summary="Study 30 essential TOEIC business vocabulary words with definitions, examples, and test-style quizzes.",
        estimated_hours=20,
        tags=["english", "toeic", "vocabulary", "business"],
        lessons=[
            {"title": "TOEIC business vocabulary", "type": "flashcard", "content": "Learn 30 high-frequency TOEIC words used in meetings, reports, and negotiations.", "duration": 25, "xp": 40, "cards": ENGLISH_TOEIC_CARDS},
            {"title": "Vocabulary in context writing", "type": "writing", "content": "Write sentences using 10 new TOEIC words in realistic workplace scenarios.", "duration": 20, "xp": 35},
            {"title": "TOEIC vocabulary quiz", "type": "quiz", "content": "Multiple-choice and fill-in-blank questions testing TOEIC vocabulary knowledge.", "duration": 20, "xp": 35, "quiz": ENGLISH_TOEIC_QUIZ},
        ],
        roadmap_steps=[
            {"title": "Core vocabulary", "description": "Learn high-frequency business English words tested on the TOEIC.", "phase": "foundation", "step_type": "flashcard", "estimated_days": 5, "resource_hint": "TOEIC word list"},
            {"title": "Contextual writing", "description": "Practice using new words in realistic business sentences.", "phase": "practice", "step_type": "writing", "estimated_days": 4, "resource_hint": "Business writing prompts"},
            {"title": "Test simulation", "description": "Quiz yourself with TOEIC-style vocabulary questions.", "phase": "reinforcement", "step_type": "quiz", "estimated_days": 3, "resource_hint": "TOEIC practice tests"},
        ],
    ),
    "english-grammar-essentials": CourseTemplate(
        key="english-grammar-essentials",
        title="English Grammar Essentials",
        category="language",
        language="en",
        level="beginner",
        summary="Master 24 core English grammar patterns — tenses, conditionals, passive voice, articles, and more with interactive exercises.",
        estimated_hours=24,
        tags=["english", "grammar", "tenses", "beginner"],
        lessons=[
            {"title": "Grammar pattern flashcards", "type": "flashcard", "content": "Review 24 essential grammar patterns with examples and usage notes.", "duration": 25, "xp": 40, "cards": ENGLISH_GRAMMAR_CARDS},
            {"title": "Error correction writing", "type": "writing", "content": "Identify and correct grammar errors in sample paragraphs and rewrite them correctly.", "duration": 20, "xp": 35},
            {"title": "Grammar quiz", "type": "quiz", "content": "Test your grammar with sentence completion, error spotting, and fill-in-blank exercises.", "duration": 20, "xp": 35, "quiz": ENGLISH_GRAMMAR_QUIZ},
        ],
        roadmap_steps=[
            {"title": "Tenses & structures", "description": "Master present, past, and future tenses plus conditionals.", "phase": "foundation", "step_type": "flashcard", "estimated_days": 5, "resource_hint": "Grammar cards"},
            {"title": "Error correction", "description": "Practice identifying and fixing common grammar mistakes.", "phase": "practice", "step_type": "writing", "estimated_days": 4, "resource_hint": "Error correction exercises"},
            {"title": "Grammar mastery", "description": "Prove your understanding through interactive grammar quizzes.", "phase": "reinforcement", "step_type": "quiz", "estimated_days": 3, "resource_hint": "Grammar test bank"},
        ],
    ),
    "chinese-survival": CourseTemplate(
        key="chinese-survival",
        title="Chinese Essentials & Hanzi Practice",
        category="language",
        language="zh",
        level="beginner",
        summary="Study 40 daily Mandarin words with pinyin, 12 Hanzi stroke-order practice, and interactive quizzes.",
        estimated_hours=26,
        tags=["chinese", "hanzi", "pinyin", "writing"],
        lessons=[
            {"title": "Daily Mandarin flashcards", "type": "flashcard", "content": "Review 40 daily-use Mandarin words — greetings, numbers, verbs, time words.", "duration": 30, "xp": 45, "cards": CHINESE_SURVIVAL_CARDS},
            {"title": "Hanzi writing practice", "type": "hanzi", "content": "Trace and write 12 basic characters with interactive stroke order.", "duration": 25, "xp": 38, "hanzi": CHINESE_SURVIVAL_HANZI},
            {"title": "Pinyin and sentence quiz", "type": "quiz", "content": "Match pinyin, choose correct meanings, and fill missing words in simple sentences.", "duration": 20, "xp": 35, "quiz": CHINESE_SURVIVAL_QUIZ},
            {"title": "Short phrase writing", "type": "writing", "content": "Write short self-introduction lines and describe your study plan in simple Chinese.", "duration": 22, "xp": 36},
        ],
        roadmap_steps=[
            {"title": "Daily words", "description": "Start with high-frequency greetings, time words, and study vocabulary.", "phase": "foundation", "step_type": "flashcard", "estimated_days": 4, "resource_hint": "Starter Mandarin deck"},
            {"title": "Stroke order lab", "description": "Practice core Hanzi with visual stroke order and handwriting repetition.", "phase": "practice", "step_type": "hanzi", "estimated_days": 5, "resource_hint": "Hanzi tracing set"},
            {"title": "Simple sentence control", "description": "Confirm comprehension with pinyin and sentence-assembly quizzes.", "phase": "reinforcement", "step_type": "quiz", "estimated_days": 3, "resource_hint": "Pinyin and sentence drills"},
        ],
    ),
    "chinese-hsk1": CourseTemplate(
        key="chinese-hsk1",
        title="HSK 1 Vocabulary & Grammar",
        category="language",
        language="zh",
        level="beginner",
        summary="Master 50 HSK Level 1 vocabulary words — family, food, places, adjectives — with pinyin drills and quizzes.",
        estimated_hours=28,
        tags=["chinese", "hsk1", "vocabulary", "beginner"],
        lessons=[
            {"title": "HSK1 vocabulary flashcards", "type": "flashcard", "content": "Review 50 essential HSK1 words covering family, food, places, and descriptions.", "duration": 30, "xp": 45, "cards": CHINESE_HSK1_CARDS},
            {"title": "HSK1 writing practice", "type": "writing", "content": "Write simple sentences using HSK1 vocabulary: introduce your family, describe your daily routine.", "duration": 25, "xp": 38},
            {"title": "HSK1 quiz", "type": "quiz", "content": "Test your HSK1 knowledge with meaning matching, pinyin recognition, and fill-in-blank exercises.", "duration": 20, "xp": 35, "quiz": CHINESE_HSK1_QUIZ},
        ],
        roadmap_steps=[
            {"title": "HSK1 core words", "description": "Learn the 50 most important words for HSK Level 1.", "phase": "foundation", "step_type": "flashcard", "estimated_days": 5, "resource_hint": "HSK1 word list"},
            {"title": "Sentence building", "description": "Practice writing simple Chinese sentences with new vocabulary.", "phase": "practice", "step_type": "writing", "estimated_days": 4, "resource_hint": "HSK1 sentence patterns"},
            {"title": "HSK1 review", "description": "Verify vocabulary retention with interactive quizzes.", "phase": "reinforcement", "step_type": "quiz", "estimated_days": 3, "resource_hint": "HSK1 practice tests"},
        ],
    ),
    "chinese-daily-conversation": CourseTemplate(
        key="chinese-daily-conversation",
        title="Chinese Daily Conversation",
        category="language",
        language="zh",
        level="intermediate",
        summary="Practice 20 real-life Chinese conversation phrases for restaurants, shopping, travel, and social situations.",
        estimated_hours=20,
        tags=["chinese", "conversation", "travel", "practical"],
        lessons=[
            {"title": "Conversation phrase flashcards", "type": "flashcard", "content": "Learn 20 essential phrases for ordering food, shopping, asking directions, and socializing.", "duration": 25, "xp": 40, "cards": CHINESE_CONVERSATION_CARDS},
            {"title": "Dialogue writing practice", "type": "writing", "content": "Write short dialogues for real situations: ordering at a restaurant, buying something, introducing yourself.", "duration": 25, "xp": 38},
            {"title": "Conversation quiz", "type": "quiz", "content": "Test your conversational Chinese with situational questions.", "duration": 18, "xp": 34, "quiz": CHINESE_CONVERSATION_QUIZ},
        ],
        roadmap_steps=[
            {"title": "Survival phrases", "description": "Learn phrases for restaurants, shops, taxis, and hotels.", "phase": "foundation", "step_type": "flashcard", "estimated_days": 4, "resource_hint": "Travel phrase deck"},
            {"title": "Dialogue practice", "description": "Write realistic dialogues for common travel and social scenarios.", "phase": "practice", "step_type": "writing", "estimated_days": 4, "resource_hint": "Dialogue prompts"},
            {"title": "Situational quiz", "description": "Test if you can pick the right phrase for each real-life situation.", "phase": "reinforcement", "step_type": "quiz", "estimated_days": 3, "resource_hint": "Scenario-based quiz"},
        ],
    ),
    "java-backend-springboot": CourseTemplate(
        key="java-backend-springboot",
        title="Spring Boot Core & REST APIs",
        category="programming",
        language="vi",
        level="beginner",
        summary="Học cách thiết kế REST API chuẩn RESTful, tích hợp Spring Data JPA kết nối database, xử lý Security và Dependency Injection trong Spring Boot.",
        estimated_hours=36,
        tags=["java", "springboot", "backend", "api"],
        lessons=[
            {
                "title": "Spring IoC & Dependency Injection",
                "type": "flashcard",
                "content": "Hiểu cơ chế quản lý Bean, ApplicationContext, @Component, @Autowired và Spring IoC Container.",
                "duration": 25,
                "xp": 40,
                "cards": [
                    ("@Component", "Đánh dấu một class là Spring Bean để IoC container quản lý", "Component annotation", "@Component\npublic class UserService {}"),
                    ("@Autowired", "Tự động inject dependency vào Bean", "Autowired annotation", "@Autowired\nprivate UserRepository repo;"),
                    ("@Bean", "Định nghĩa Bean thủ công trong class cấu hình", "Bean annotation", "@Bean\npublic RestTemplate restTemplate() { return new RestTemplate(); }"),
                ],
            },
            {
                "title": "Thiết kế REST API với Spring Web",
                "type": "writing",
                "content": "Viết các Endpoint xử lý HTTP Requests sử dụng @RestController, @GetMapping, @PostMapping, @PathVariable và ResponseEntity.",
                "duration": 30,
                "xp": 50,
            },
            {
                "title": "Kết nối Database với Spring Data JPA",
                "type": "quiz",
                "content": "Kiểm tra các câu hỏi về Entity lifecycle, ORM mapping, JPQL và cách viết custom query methods trong Repository.",
                "duration": 20,
                "xp": 45,
            },
        ],
        roadmap_steps=[
            {"title": "Spring Core & Container", "description": "Nắm vững cơ chế Dependency Injection và quản lý vòng đời Bean trong Spring Framework.", "phase": "foundation", "step_type": "flashcard", "estimated_days": 4, "resource_hint": "Spring Core Beans and annotations"},
            {"title": "RESTful API Design", "description": "Dựng các REST API chuẩn mực, xử lý exception global và cấu hình CORS.", "phase": "practice", "step_type": "writing", "estimated_days": 5, "resource_hint": "Spring Web and REST controllers"},
            {"title": "Database & JPA Persistence", "description": "Tích hợp Spring Data JPA, thiết kế quan hệ bảng và truy vấn cơ sở dữ liệu.", "phase": "reinforcement", "step_type": "quiz", "estimated_days": 4, "resource_hint": "Spring Data Repository & Hibernate"},
        ],
    ),
    "java-backend-microservices": CourseTemplate(
        key="java-backend-microservices",
        title="Microservices với Spring Cloud",
        category="programming",
        language="vi",
        level="intermediate",
        summary="Xây dựng kiến trúc hệ thống phân tán phân rã từ Monolith. Thiết lập Service Discovery, API Gateway, Fault Tolerance và Distributed Tracing.",
        estimated_hours=32,
        tags=["java", "microservices", "springcloud", "docker"],
        lessons=[
            {
                "title": "Service Discovery với Eureka",
                "type": "flashcard",
                "content": "Tìm hiểu cách đăng ký và phát hiện dịch vụ tự động giữa các microservice bằng Netflix Eureka.",
                "duration": 25,
                "xp": 40,
                "cards": [
                    ("@EnableEurekaServer", "Đánh dấu ứng dụng là Eureka Registry Server", "Enable Eureka Server", "@EnableEurekaServer\n@SpringBootApplication"),
                    ("@EnableDiscoveryClient", "Đăng ký một service với Eureka Server", "Enable Discovery Client", "@EnableDiscoveryClient"),
                    ("Ribbon / Load Balancer", "Cân bằng tải phía client giữa các instance của service", "Client-side load balancer", "Load balancer details"),
                ],
            },
            {
                "title": "API Gateway & Routing",
                "type": "writing",
                "content": "Cấu hình định tuyến (Routing) tập trung, kiểm tra quyền truy cập và rate limit tại API Gateway.",
                "duration": 30,
                "xp": 50,
            },
            {
                "title": "Resilience & Circuit Breaker",
                "type": "quiz",
                "content": "Học cách xử lý lỗi lan truyền bằng Resilience4j Circuit Breaker, Fallback methods.",
                "duration": 20,
                "xp": 45,
            },
        ],
        roadmap_steps=[
            {"title": "Discovery & Registry", "description": "Thiết lập Eureka Server để tự động đăng ký và phát hiện dịch vụ.", "phase": "foundation", "step_type": "flashcard", "estimated_days": 4, "resource_hint": "Eureka Server setup & client registry"},
            {"title": "Gateway & Filtering", "description": "Dựng Spring Cloud Gateway để quản lý định tuyến, xác thực và rate-limiting tập trung.", "phase": "practice", "step_type": "writing", "estimated_days": 5, "resource_hint": "Gateway route filters and rate limiters"},
            {"title": "Resilience & Config", "description": "Áp dụng Resilience4j Circuit Breaker để ngăn chặn lỗi hệ thống dây chuyền.", "phase": "reinforcement", "step_type": "quiz", "estimated_days": 3, "resource_hint": "Resilience4j configurations"},
        ],
    ),
    "java-backend-websocket": CourseTemplate(
        key="java-backend-websocket",
        title="Real-time WebSocket & Redis",
        category="programming",
        language="vi",
        level="advanced",
        summary="Xây dựng tính năng chat thời gian thực và đồng bộ dữ liệu. Tích hợp Spring WebSocket, STOMP protocol, quản lý phiên và cache với Redis.",
        estimated_hours=28,
        tags=["java", "websocket", "redis", "realtime"],
        lessons=[
            {
                "title": "WebSocket & STOMP Protocol",
                "type": "flashcard",
                "content": "Tìm hiểu sự khác biệt giữa raw WebSocket và STOMP message broker. Đăng ký endpoint và destination prefix.",
                "duration": 20,
                "xp": 35,
                "cards": [
                    ("@MessageMapping", "Định tuyến tin nhắn gửi từ client đến controller", "Message Mapping", "@MessageMapping(\"/chat.sendMessage\")"),
                    ("@SendTo", "Phát tin nhắn (broadcast) tới một topic chung", "SendTo topic", "@SendTo(\"/topic/public\")"),
                    ("SimpMessagingTemplate", "Gửi tin nhắn trực tiếp đến một user hoặc topic cụ thể", "Simp messaging template", "messagingTemplate.convertAndSendToUser(...)"),
                ],
            },
            {
                "title": "Redis Session & Cache Store",
                "type": "writing",
                "content": "Mô tả cách lưu trữ session trực tuyến của người dùng và đồng bộ hóa trạng thái Online/Offline qua Redis Pub/Sub.",
                "duration": 25,
                "xp": 45,
            },
            {
                "title": "Real-time Chat Architecture Quiz",
                "type": "quiz",
                "content": "Các câu hỏi trắc nghiệm kiểm tra khả năng xử lý quá tải WebSocket, Heartbeat, và bảo mật bằng Spring Security.",
                "duration": 20,
                "xp": 40,
            },
        ],
        roadmap_steps=[
            {"title": "STOMP Messaging", "description": "Cấu hình WebSocket Message Broker và đăng ký endpoint kết nối ở backend.", "phase": "foundation", "step_type": "flashcard", "estimated_days": 4, "resource_hint": "WebSocketConfig and STOMP message flows"},
            {"title": "Redis Pub/Sub & State", "description": "Tích hợp Redis để quản lý người dùng đang online và phân phối tin nhắn đa instance.", "phase": "practice", "step_type": "writing", "estimated_days": 4, "resource_hint": "Redis configurations and channel subscribers"},
            {"title": "Security & Scalability", "description": "Bảo mật endpoint WebSocket bằng JWT Interceptor và tối ưu số lượng kết nối đồng thời.", "phase": "reinforcement", "step_type": "quiz", "estimated_days": 3, "resource_hint": "WebSocket authentication security channels"},
        ],
    ),
}


ROADMAP_BY_DOMAIN = {
    "programming": ["programming-python", "programming-javascript"],
    "english": ["english-communication", "english-toeic-vocabulary", "english-grammar-essentials"],
    "chinese": ["chinese-survival", "chinese-hsk1", "chinese-daily-conversation"],
    "java-backend": ["java-backend-springboot", "java-backend-microservices", "java-backend-websocket"],
}


def _slugify(value: str) -> str:
    allowed = []
    for char in value.lower():
        if char.isalnum():
            allowed.append(char)
        elif char in {" ", "-", "_"}:
            allowed.append("-")
    slug = "".join(allowed).strip("-")
    while "--" in slug:
        slug = slug.replace("--", "-")
    return slug or "path"


def _build_path_title(domain: str, goal: str) -> str:
    labels = {
        "programming": "Programming",
        "english": "English",
        "chinese": "Chinese",
        "java-backend": "Java Backend",
    }
    return f"{labels.get(domain, 'Learning')} roadmap for {goal}"


def _default_track(domain: str) -> str | None:
    tracks = TRACK_CATALOG.get(domain, {})
    return next(iter(tracks.keys()), None)


def _recommended_templates(domain: str, level: str, track: str | None = None, target_level: str | None = None) -> list[CourseTemplate]:
    track_key = track or _default_track(domain)
    track_config = TRACK_CATALOG.get(domain, {}).get(track_key or "")
    keys = track_config.get("templates", []) if track_config else ROADMAP_BY_DOMAIN.get(domain, [])
    templates = [COURSE_LIBRARY[key] for key in keys]
    normalized_level = _normalize_level_for_templates(domain, level, track_key, target_level)
    level_matches = [template for template in templates if template.level == normalized_level]
    return level_matches or templates


def _normalize_level_for_templates(domain: str, level: str, track: str | None, target_level: str | None) -> str:
    if domain == "english" and track in {"toeic", "ielts"} and target_level:
        try:
            numeric = float(target_level)
        except ValueError:
            return level
        if track == "toeic":
            if numeric <= 300:
                return "beginner"
            if numeric <= 600:
                return "intermediate"
            return "advanced"
        if track == "ielts":
            if numeric <= 3.0:
                return "beginner"
            if numeric <= 5.5:
                return "intermediate"
            return "advanced"
    if domain == "chinese" and track == "hsk" and target_level:
        try:
            numeric = int(target_level)
        except ValueError:
            return level
        if numeric <= 2:
            return "beginner"
        if numeric <= 4:
            return "intermediate"
        return "advanced"
    return level


def _clone_course_template(db: Session, learning_path_id: int, template: CourseTemplate, slug_prefix: str) -> Course:
    base_slug = _slugify(f"{slug_prefix}-{template.key}")
    slug = base_slug
    counter = 1
    while db.query(Course).filter(Course.slug == slug).first():
        counter += 1
        slug = f"{base_slug}-{counter}"

    course = Course(
        learning_path_id=learning_path_id,
        title=template.title,
        slug=slug,
        category=template.category,
        language=template.language,
        level=template.level,
        summary=template.summary,
        estimated_hours=template.estimated_hours,
        tags=template.tags,
    )
    db.add(course)
    db.flush()

    for index, lesson_data in enumerate(template.lessons):
        content_val = lesson_data["content"]
        if lesson_data["type"] == "hanzi" and "hanzi" in lesson_data:
            import json
            content_val = json.dumps({
                "description": lesson_data["content"],
                "hanzi": lesson_data["hanzi"]
            }, ensure_ascii=False)

        lesson = Lesson(
            course_id=course.id,
            title=lesson_data["title"],
            lesson_type=lesson_data["type"],
            content=content_val,
            order_index=index,
            duration_minutes=lesson_data.get("duration", 20),
            xp_reward=lesson_data.get("xp", 30),
        )
        db.add(lesson)
        db.flush()

        for front, back, pronunciation, example in lesson_data.get("cards", []):
            db.add(
                Flashcard(
                    lesson_id=lesson.id,
                    front_text=front,
                    back_text=back,
                    pronunciation=pronunciation,
                    example=example,
                )
            )

        for qi, quiz_item in enumerate(lesson_data.get("quiz", [])):
            db.add(
                QuizQuestion(
                    lesson_id=lesson.id,
                    question_text=quiz_item["q"],
                    question_type=quiz_item.get("type", "multiple_choice"),
                    options=quiz_item.get("options", []),
                    correct_answer=quiz_item["answer"],
                    explanation=quiz_item.get("explain"),
                    order_index=qi,
                )
            )

    return course


def _build_roadmap_steps(
    db: Session,
    learning_path_id: int,
    templates: Iterable[CourseTemplate],
    intensity_hours: float,
    preferred_modes: list[str],
    learning_resources: list[dict] | None = None,
) -> list[RoadmapStep]:
    steps: list[RoadmapStep] = []
    preferred_modes_set = {mode.lower() for mode in preferred_modes}
    resource_pool = learning_resources or []

    for template in templates:
        for blueprint in template.roadmap_steps:
            mode_bonus = 1 if blueprint["step_type"].lower() in preferred_modes_set else 0
            estimated_days = max(2, int(blueprint["estimated_days"] * (2.0 / max(intensity_hours, 0.5))) - mode_bonus)
            external_resource = resource_pool[len(steps) % len(resource_pool)] if resource_pool else None
            step = RoadmapStep(
                learning_path_id=learning_path_id,
                title=blueprint["title"],
                description=blueprint["description"],
                phase=blueprint["phase"],
                step_type=blueprint["step_type"],
                estimated_days=estimated_days,
                order_index=len(steps),
                resource_hint=blueprint.get("resource_hint"),
                step_metadata={
                    "intensity_label": INTENSITY_LABELS.get(intensity_hours, f"{intensity_hours} hours/day"),
                    "preferred_modes": list(preferred_modes_set),
                    "course_template": template.key,
                    "learning_resource": external_resource,
                },
            )
            db.add(step)
            steps.append(step)

    return steps


def create_personalized_learning_path(
    db: Session,
    *,
    user: User,
    domain: str,
    level: str,
    goal: str,
    intensity_hours: float,
    preferred_modes: list[str],
    track: str | None = None,
    target_level: str | None = None,
) -> tuple[LearningPath, list[RoadmapStep], list[Course]]:
    db.query(LearningPath).filter(LearningPath.user_id == user.id).update({"active": False})

    weekly_hours = max(1, round(intensity_hours * 7))
    pace = INTENSITY_LABELS.get(intensity_hours, f"{intensity_hours} hours/day")
    track_key = track or _default_track(domain)
    track_config = TRACK_CATALOG.get(domain, {}).get(track_key or "", {})
    track_label = track_config.get("label", track_key or domain)
    target_label = f" {target_level}" if target_level else ""
    focus_area = {
        "programming": "Programming languages",
        "english": "English communication",
        "chinese": "Chinese and Hanzi writing",
        "java-backend": "Java Backend Development",
    }.get(domain, domain.title())
    focus_area = f"{focus_area} / {track_label}{target_label}"

    path = LearningPath(
        user_id=user.id,
        title=f"{track_label}{target_label} roadmap for {goal}",
        target_role=goal,
        focus_area=focus_area,
        pace=pace,
        weekly_hours=weekly_hours,
        description=f"Personal roadmap for {goal} on {track_label}{target_label} with {pace.lower()} and modes: {', '.join(preferred_modes) or 'mixed practice'}.",
        active=True,
    )
    db.add(path)
    db.flush()

    templates = _recommended_templates(domain, level, track_key, target_level)
    slug_prefix = f"u{user.id}-{path.id}-{domain}-{track_key or 'core'}-{target_level or level}"
    courses = [_clone_course_template(db, path.id, template, slug_prefix) for template in templates]
    learning_resources = get_learning_resources(domain, track_key, target_level, limit=6)
    steps = _build_roadmap_steps(db, path.id, templates, intensity_hours, preferred_modes, learning_resources)
    for step in steps:
        step.step_metadata = {
            **(step.step_metadata or {}),
            "domain": domain,
            "track": track_key,
            "target_level": target_level,
            "exam_provider": track_config.get("exam", {}).get("provider", "internal"),
            "exam_duration_minutes": track_config.get("exam", {}).get("duration_minutes"),
            "resource_provider": (step.step_metadata or {}).get("learning_resource", {}).get("provider") if (step.step_metadata or {}).get("learning_resource") else None,
            "resource_source_type": (step.step_metadata or {}).get("learning_resource", {}).get("source_type") if (step.step_metadata or {}).get("learning_resource") else None,
        }

    user.learning_goal = goal
    user.weekly_hours = weekly_hours
    db.add(user)
    db.commit()
    db.refresh(path)
    for step in steps:
        db.refresh(step)
    for course in courses:
        db.refresh(course)
    return path, steps, courses


def seed_interview_questions(db: Session) -> None:
    if db.query(InterviewQuestion).count() > 0:
        return

    questions = [
        InterviewQuestion(
            category="Java Core",
            question="Sự khác biệt giữa abstract class và interface trong Java là gì?",
            answer="Abstract class cho phép khai báo trường dữ liệu (instance fields) và constructor, hỗ trợ đơn kế thừa (single inheritance). Interface từ Java 8 cho phép default/static methods nhưng không có instance fields và không có constructor, hỗ trợ đa kế thừa interface (multiple interface implementation). Nên dùng abstract class khi các class có chung bản chất (is-a), và interface để định nghĩa hành vi chung (can-do).",
            difficulty="Medium",
            order_index=1
        ),
        InterviewQuestion(
            category="Spring Boot",
            question="Spring Bean lifecycle gồm những giai đoạn chính nào?",
            answer="Các giai đoạn chính của Spring Bean lifecycle gồm:\n1. Instantiation: Tạo thực thể Bean.\n2. Populate Properties: Inject các dependency (@Autowired).\n3. BeanNameAware / BeanFactoryAware: Cung cấp metadata về tên bean và factory.\n4. Pre-initialization (BeanPostProcessor).\n5. Initialization: Chạy init-method hoặc @PostConstruct.\n6. Post-initialization (BeanPostProcessor).\n7. Destruction: Chạy destroy-method hoặc @PreDestroy khi ApplicationContext close.",
            difficulty="Hard",
            order_index=2
        ),
        InterviewQuestion(
            category="Python",
            question="Cơ chế GIL (Global Interpreter Lock) trong Python là gì và nó ảnh hưởng thế nào đến multi-threading?",
            answer="GIL là một mutex bảo vệ tài nguyên của CPython, đảm bảo chỉ có một thread thực thi Python bytecode tại một thời điểm. Nó ngăn cản multi-threading tận dụng nhiều nhân CPU cho các tác vụ tính toán (CPU-bound). Đối với CPU-bound, nên dùng multiprocessing thay thế. Đối với I/O-bound (đọc ghi file, gọi mạng), multi-threading vẫn hiệu quả vì GIL được giải phóng khi đợi I/O.",
            difficulty="Hard",
            order_index=3
        ),
        InterviewQuestion(
            category="Python",
            question="Sự khác biệt giữa List và Tuple trong Python là gì?",
            answer="List là kiểu dữ liệu có thể thay đổi (mutable), hỗ trợ thêm/xóa/sửa phần tử và tốn nhiều bộ nhớ hơn do cơ chế dynamic resizing. Tuple là kiểu dữ liệu không thể thay đổi (immutable) sau khi tạo, an toàn hơn và có hiệu năng đọc nhanh hơn list. Tuple thường dùng làm key trong dictionary, còn List thì không.",
            difficulty="Easy",
            order_index=4
        ),
        InterviewQuestion(
            category="SQL & DB",
            question="Phân biệt giữa INNER JOIN, LEFT JOIN, và RIGHT JOIN?",
            answer="1. INNER JOIN: Chỉ trả về các bản ghi có sự trùng khớp ở cả hai bảng.\n2. LEFT JOIN (hoặc LEFT OUTER JOIN): Trả về toàn bộ bản ghi của bảng bên trái, và các bản ghi khớp của bảng bên phải (nếu không khớp, trả về NULL ở cột bảng phải).\n3. RIGHT JOIN: Trả về toàn bộ bản ghi bảng bên phải, và khớp ở bảng bên trái (NULL nếu không khớp ở cột bảng trái).",
            difficulty="Easy",
            order_index=5
        ),
        InterviewQuestion(
            category="SQL & DB",
            question="Database Index là gì và tại sao không nên đánh index trên mọi cột?",
            answer="Index là cấu trúc dữ liệu (thường là B-Tree) giúp tăng tốc độ tìm kiếm bản ghi trong bảng. Tuy nhiên, không nên đánh index cho mọi cột vì:\n1. Tốn dung lượng lưu trữ index.\n2. Làm chậm các thao tác ghi dữ liệu (INSERT, UPDATE, DELETE) vì hệ thống phải cập nhật lại cấu trúc index.\n3. Trình tối ưu truy vấn có thể bối rối khi chọn index tốt nhất.",
            difficulty="Medium",
            order_index=6
        ),
        InterviewQuestion(
            category="Java Core",
            question="Cơ chế Garbage Collection (GC) trong Java hoạt động như thế nào?",
            answer="Garbage Collection tự động thu hồi bộ nhớ của các đối tượng không còn được tham chiếu từ gốc (GC Roots). Nó hoạt động theo nguyên lý Generational Garbage Collection: chia bộ nhớ Heap thành Young Generation (Eden, Survivor spaces) chứa đối tượng mới tạo có vòng đời ngắn, và Old Generation chứa đối tượng sống sót qua nhiều chu kỳ GC. GC dọn dẹp Young bằng Minor GC và Old bằng Major GC/Full GC.",
            difficulty="Hard",
            order_index=7
        ),
        InterviewQuestion(
            category="Spring Boot",
            question="Annotation @Transactional hoạt động thế nào dưới nền tảng Spring?",
            answer="Spring sử dụng cơ chế AOP (Aspect-Oriented Programming) tạo ra một proxy bao quanh class hoặc method có @Transactional. Khi method được gọi, proxy sẽ mở một database transaction mới, thực hiện business logic. Nếu method chạy thành công không có exception (hoặc chỉ có checked exception không cấu hình rollback), proxy sẽ commit transaction. Nếu runtime exception xảy ra, proxy sẽ rollback transaction.",
            difficulty="Medium",
            order_index=8
        )
    ]
    for q in questions:
        db.add(q)
    db.commit()
    print(f"Seeded {len(questions)} interview questions.")


def ensure_default_seed(db: Session) -> None:
    seed_interview_questions(db)

    user = db.query(User).filter(User.role == UserRole.LEARNER).first()
    if not user:
        return

    existing_path = db.query(LearningPath).filter(LearningPath.user_id == user.id).first()
    if existing_path:
        return

    create_personalized_learning_path(
        db,
        user=user,
        domain="programming",
        level="beginner",
        goal="Junior Python developer",
        intensity_hours=1.0,
        preferred_modes=["flashcard", "writing", "quiz"],
    )


def run_seed_if_needed() -> None:
    db = SessionLocal()
    try:
        ensure_default_seed(db)
    finally:
        db.close()


if __name__ == "__main__":
    run_seed_if_needed()
    print("Seed finished.")
