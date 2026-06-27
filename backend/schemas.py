"""
Pydantic schemas for request/response validation
"""
from pydantic import BaseModel, Field
from typing import Optional, List
from datetime import datetime
from enum import Enum


# ============= User Schemas =============
class UserRole(str, Enum):
    ADMIN = "admin"
    EDITOR = "editor"
    LEARNER = "learner"
    VIEWER = "viewer"


class UserBase(BaseModel):
    username: str = Field(..., min_length=3, max_length=50)
    email: str = Field(..., max_length=255)


class UserCreate(BaseModel):
    username: str = Field(..., min_length=3, max_length=50)
    email: str = Field(..., max_length=255)
    password: str = Field(..., min_length=6)
    full_name: str = Field(..., max_length=120)


class UserLogin(BaseModel):
    username: str
    password: str


class UserResponse(UserBase):
    id: int
    full_name: str
    learning_goal: Optional[str] = None
    avatar_url: Optional[str] = None
    role: UserRole = UserRole.LEARNER
    created_at: datetime

    class Config:
        from_attributes = True


class Token(BaseModel):
    access_token: str
    token_type: str = "bearer"


class PasswordChange(BaseModel):
    current_password: str = Field(..., min_length=1)
    new_password: str = Field(..., min_length=6)


# ============= Article Schemas =============
class ArticleStatus(str, Enum):
    PUBLISHED = "published"
    DRAFT = "draft"


class ArticleBase(BaseModel):
    title: str = Field(..., max_length=255)
    content: str
    excerpt: Optional[str] = None
    status: ArticleStatus = ArticleStatus.DRAFT


class ArticleCreate(ArticleBase):
    slug: Optional[str] = None


class ArticleUpdate(ArticleBase):
    title: Optional[str] = None
    content: Optional[str] = None
    slug: Optional[str] = None


class ArticleResponse(ArticleBase):
    id: int
    slug: str
    created_at: datetime
    updated_at: Optional[datetime]

    class Config:
        from_attributes = True


# ============= Project Schemas =============
class ProjectStatus(str, Enum):
    ACTIVE = "active"
    COMPLETED = "completed"
    ARCHIVED = "archived"


class ProjectBase(BaseModel):
    title: str = Field(..., max_length=255)
    description: str
    short_description: Optional[str] = Field(None, max_length=500)
    technologies: Optional[List[str]] = []
    project_url: Optional[str] = None
    github_url: Optional[str] = None
    featured_image: Optional[str] = None
    gallery_images: Optional[List[str]] = []
    status: ProjectStatus = ProjectStatus.ACTIVE
    featured: bool = False
    sort_order: int = 0


class ProjectCreate(ProjectBase):
    pass


class ProjectUpdate(ProjectBase):
    title: Optional[str] = None
    description: Optional[str] = None


class ProjectResponse(ProjectBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime]

    class Config:
        from_attributes = True


# ============= About Schemas =============
class AboutBase(BaseModel):
    name: str = Field(..., max_length=100)
    title: str = Field(..., max_length=255)
    bio: str
    email: Optional[str] = None
    phone: Optional[str] = None
    location: Optional[str] = None
    avatar: Optional[str] = None
    resume_url: Optional[str] = None


class AboutCreate(AboutBase):
    pass


class AboutUpdate(AboutBase):
    name: Optional[str] = None
    title: Optional[str] = None
    bio: Optional[str] = None


class AboutResponse(AboutBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime]

    class Config:
        from_attributes = True


# ============= Skill Schemas =============
class SkillBase(BaseModel):
    name: str = Field(..., max_length=100)
    category: Optional[str] = None
    proficiency: int = Field(50, ge=0, le=100)
    icon: Optional[str] = None
    sort_order: int = 0


class SkillCreate(SkillBase):
    pass


class SkillUpdate(SkillBase):
    name: Optional[str] = None


class SkillResponse(SkillBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime]

    class Config:
        from_attributes = True


# ============= Social Link Schemas =============
class SocialLinkBase(BaseModel):
    platform: str = Field(..., max_length=50)
    url: str = Field(..., max_length=255)
    icon: Optional[str] = None
    sort_order: int = 0


class SocialLinkCreate(SocialLinkBase):
    pass


class SocialLinkUpdate(SocialLinkBase):
    platform: Optional[str] = None
    url: Optional[str] = None


class SocialLinkResponse(SocialLinkBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime]

    class Config:
        from_attributes = True


# ============= SEO Setting Schemas =============
class SEOSettingBase(BaseModel):
    page: str = Field(..., max_length=50)
    title: str = Field(..., max_length=255)
    description: Optional[str] = None
    keywords: Optional[str] = None
    og_image: Optional[str] = None
    og_title: Optional[str] = None
    og_description: Optional[str] = None


class SEOSettingCreate(SEOSettingBase):
    pass


class SEOSettingUpdate(SEOSettingBase):
    page: Optional[str] = None
    title: Optional[str] = None


class SEOSettingResponse(SEOSettingBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime]

    class Config:
        from_attributes = True


# ============= Setting Schemas =============
class SettingBase(BaseModel):
    key: str = Field(..., max_length=100)
    value: Optional[str] = None
    description: Optional[str] = None


class SettingCreate(SettingBase):
    pass


class SettingUpdate(BaseModel):
    value: Optional[str] = None
    description: Optional[str] = None


class SettingResponse(SettingBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime]

    class Config:
        from_attributes = True


# ============= Learning Schemas =============
class LearningPathCreate(BaseModel):
    title: str = Field(..., max_length=255)
    target_role: str = Field(..., max_length=120)
    focus_area: str = Field(..., max_length=120)
    pace: str = Field(..., max_length=80)
    weekly_hours: int = Field(..., ge=1, le=40)
    description: Optional[str] = None


class LearningPathResponse(BaseModel):
    id: int
    user_id: int
    title: str
    target_role: str
    focus_area: str
    pace: str
    weekly_hours: int
    description: Optional[str]
    active: bool
    created_at: datetime
    updated_at: Optional[datetime]

    class Config:
        from_attributes = True


class RoadmapWizardRequest(BaseModel):
    domain: str = Field(..., pattern="^(programming|java-backend)$")
    level: str = Field(..., pattern="^(beginner|intermediate|advanced)$")
    track: Optional[str] = Field(None, max_length=80)
    target_level: Optional[str] = Field(None, max_length=40)
    goal: str = Field(..., min_length=3, max_length=120)
    intensity_hours: float = Field(..., ge=0.5, le=8)
    preferred_modes: List[str] = Field(default_factory=list)


class RoadmapStepResponse(BaseModel):
    id: int
    title: str
    description: str
    phase: str
    step_type: str
    estimated_days: int
    order_index: int
    is_completed: bool
    resource_hint: Optional[str]
    step_metadata: dict = Field(default_factory=dict)

    class Config:
        from_attributes = True


class RoadmapWizardResponse(BaseModel):
    learning_path: LearningPathResponse
    roadmap_steps: List[RoadmapStepResponse]
    recommended_course_slugs: List[str] = Field(default_factory=list)


class ExamSourceInfo(BaseModel):
    provider: str
    source_type: str
    freshness: str
    source_url: Optional[str] = None


class ExamQuestionResponse(BaseModel):
    id: str
    section: str
    question_type: str
    prompt: str
    options: List[str] = Field(default_factory=list)
    points: int = 1


class ExamSessionResponse(BaseModel):
    session_id: str
    domain: str
    track: str
    target_level: str
    duration_minutes: int
    source: ExamSourceInfo
    questions: List[ExamQuestionResponse]


class ExamStartRequest(BaseModel):
    domain: str = Field(..., pattern="^(english|chinese|programming|java-backend)$")
    track: str = Field(..., max_length=80)
    target_level: str = Field(..., max_length=40)
    question_count: int = Field(20, ge=5, le=100)


class ExamSubmitRequest(BaseModel):
    session_id: str
    answers: dict[str, str] = Field(default_factory=dict)


class ExamResultResponse(BaseModel):
    session_id: str
    score: int
    total: int
    percentage: int
    passed: bool
    feedback: str


class ExamHistoryItemResponse(BaseModel):
    session_id: str
    domain: str
    track: str
    target_level: str
    score: int
    total: int
    percentage: int
    status: str
    source: ExamSourceInfo
    started_at: Optional[datetime] = None
    submitted_at: Optional[datetime] = None


class ProfileUpdate(BaseModel):
    full_name: Optional[str] = Field(None, max_length=120)
    email: Optional[str] = Field(None, max_length=255)
    bio: Optional[str] = None
    learning_goal: Optional[str] = Field(None, max_length=255)
    avatar_url: Optional[str] = Field(None, max_length=255)
    weekly_hours: Optional[int] = Field(None, ge=1, le=40)


# ============= Interaction & Chatbot Schemas =============
class InterviewQuestionResponse(BaseModel):
    id: int
    category: str
    question: str
    answer: str
    difficulty: str
    order_index: int
    user_status: Optional[str] = None  # "mastered", "review", or None

    class Config:
        from_attributes = True


class UserInterviewProgressUpdate(BaseModel):
    status: str = Field(..., pattern="^(mastered|review)$")


class QnaAnswerCreate(BaseModel):
    content: str


class QnaAnswerResponse(BaseModel):
    id: int
    question_id: int
    user_id: int
    username: str
    content: str
    is_accepted: bool
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class QnaQuestionCreate(BaseModel):
    title: str = Field(..., max_length=255)
    content: str
    tags: Optional[str] = ""


class QnaQuestionResponse(BaseModel):
    id: int
    user_id: int
    username: str
    title: str
    content: str
    tags: str
    created_at: datetime
    updated_at: datetime
    answers_count: int = 0

    class Config:
        from_attributes = True


class QnaQuestionDetailResponse(QnaQuestionResponse):
    answers: List[QnaAnswerResponse] = []

    class Config:
        from_attributes = True


class ChatbotRequest(BaseModel):
    message: str


class ChatbotResponse(BaseModel):
    reply: str


# ============= HSK Learning Schemas =============
class HSKVocabularyResponse(BaseModel):
    id: int
    source: str
    hsk_level: int
    chinese: str
    pinyin: str
    vietnamese: Optional[str] = None
    english: Optional[str] = None
    example_sentences: Optional[dict] = None
    audio_url: Optional[str] = None
    stroke_order: Optional[str] = None
    created_at: datetime

    class Config:
        from_attributes = True


class HSKVocabularySearch(BaseModel):
    keyword: str
    hsk_level: Optional[int] = None


class HSKExamResponse(BaseModel):
    id: int
    source: str
    hsk_level: int
    exam_code: Optional[str] = None
    skill_type: str
    question_type: Optional[str] = None
    question_text: str
    options: Optional[List[str]] = None
    correct_answer: Optional[str] = None
    explanation: Optional[str] = None
    audio_url: Optional[str] = None
    image_url: Optional[str] = None

    class Config:
        from_attributes = True


class HSKExamDetailResponse(HSKExamResponse):
    pass


class HSKReadingResponse(BaseModel):
    id: int
    source: str
    hsk_level: int
    title: str
    content: str
    translation: Optional[str] = None
    vocabulary_list: Optional[dict] = None
    audio_url: Optional[str] = None
    url: Optional[str] = None
    created_at: datetime

    class Config:
        from_attributes = True

