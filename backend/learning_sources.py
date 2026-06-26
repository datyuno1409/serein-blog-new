from __future__ import annotations

from dataclasses import asdict, dataclass
import os
from urllib.parse import quote_plus

import httpx


@dataclass(frozen=True)
class LearningResource:
    title: str
    url: str
    provider: str
    source_type: str
    description: str = ""


CURATED_RESOURCES: dict[str, list[LearningResource]] = {
    "programming:python": [
        LearningResource("Python documentation tutorial", "https://docs.python.org/3/tutorial/", "python.org", "curated-fallback", "Official Python tutorial for core syntax and language flow."),
        LearningResource("Real Python learning paths", "https://realpython.com/learning-paths/", "realpython.com", "curated-fallback", "Practical Python projects and explanations."),
    ],
    "programming:javascript": [
        LearningResource("MDN JavaScript guide", "https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide", "mdn", "curated-fallback", "Modern JavaScript language guide."),
        LearningResource("web.dev JavaScript", "https://web.dev/learn/javascript/", "web.dev", "curated-fallback", "Browser-focused JavaScript learning path."),
    ],
    "programming:fullstack": [
        LearningResource("Full Stack Open", "https://fullstackopen.com/en/", "fullstackopen.com", "curated-fallback", "Applied full-stack web development curriculum."),
        LearningResource("MDN Web Docs", "https://developer.mozilla.org/en-US/docs/Learn", "mdn", "curated-fallback", "Frontend and web fundamentals."),
    ],
    "java-backend:spring-boot": [
        LearningResource("Spring Boot reference", "https://docs.spring.io/spring-boot/index.html", "spring.io", "curated-fallback", "Official Spring Boot documentation."),
        LearningResource("Spring guides", "https://spring.io/guides", "spring.io", "curated-fallback", "Task-oriented Spring backend guides."),
    ],
    "java-backend:microservices": [
        LearningResource("Spring Cloud guides", "https://spring.io/projects/spring-cloud", "spring.io", "curated-fallback", "Spring Cloud project documentation."),
        LearningResource("Microservices patterns", "https://microservices.io/patterns/index.html", "microservices.io", "curated-fallback", "Architecture patterns for microservice systems."),
    ],
    "java-backend:realtime": [
        LearningResource("Spring WebSocket reference", "https://docs.spring.io/spring-framework/reference/web/websocket.html", "spring.io", "curated-fallback", "Official Spring WebSocket reference."),
        LearningResource("Redis Pub/Sub docs", "https://redis.io/docs/latest/develop/pubsub/", "redis.io", "curated-fallback", "Redis messaging documentation."),
    ],
    "english:toeic": [
        LearningResource("ETS TOEIC overview", "https://www.ets.org/toeic.html", "ets.org", "curated-fallback", "Official TOEIC program information."),
        LearningResource("British Council English skills", "https://learnenglish.britishcouncil.org/skills", "britishcouncil.org", "curated-fallback", "Reading, listening, writing, and speaking practice."),
    ],
    "english:ielts": [
        LearningResource("IELTS official preparation", "https://ielts.org/take-a-test/preparation-resources", "ielts.org", "curated-fallback", "Official IELTS preparation resources."),
        LearningResource("British Council IELTS preparation", "https://takeielts.britishcouncil.org/take-ielts/prepare", "britishcouncil.org", "curated-fallback", "IELTS preparation guidance and practice."),
    ],
    "english:communication": [
        LearningResource("BBC Learning English", "https://www.bbc.co.uk/learningenglish", "bbc.co.uk", "curated-fallback", "Everyday and workplace English practice."),
        LearningResource("British Council Business English", "https://learnenglish.britishcouncil.org/business-english", "britishcouncil.org", "curated-fallback", "Professional English lessons."),
    ],
    "chinese:hsk": [
        LearningResource("HSK information", "https://www.chinesetest.cn/", "chinesetest.cn", "curated-fallback", "Official Chinese test information portal."),
        LearningResource("Mandarin Chinese resources", "https://resources.allsetlearning.com/chinese/grammar/", "allsetlearning.com", "curated-fallback", "Structured Mandarin grammar references."),
    ],
    "chinese:conversation": [
        LearningResource("Chinese grammar wiki", "https://resources.allsetlearning.com/chinese/grammar/", "allsetlearning.com", "curated-fallback", "Mandarin grammar and examples."),
        LearningResource("Mandarin pronunciation guide", "https://resources.allsetlearning.com/chinese/pronunciation/", "allsetlearning.com", "curated-fallback", "Pinyin and pronunciation guidance."),
    ],
}


def _resource_key(domain: str, track: str) -> str:
    key = f"{domain}:{track}"
    if key in CURATED_RESOURCES:
        return key
    if domain == "english":
        return "english:toeic"
    if domain == "chinese":
        return "chinese:hsk"
    if domain == "java-backend":
        return "java-backend:spring-boot"
    return "programming:python"


def _external_api_resources(domain: str, track: str, target_level: str | None, limit: int) -> list[LearningResource]:
    base_url = os.getenv("LEARNING_SOURCE_API_URL")
    if not base_url:
        return []

    headers = {"Accept": "application/json"}
    api_key = os.getenv("LEARNING_SOURCE_API_KEY")
    if api_key:
        headers["Authorization"] = f"Bearer {api_key}"

    try:
        with httpx.Client(timeout=8.0) as client:
            response = client.get(
                base_url.rstrip("/") + "/resources",
                params={
                    "domain": domain,
                    "track": track,
                    "targetLevel": target_level or "",
                    "limit": limit,
                },
                headers=headers,
            )
            response.raise_for_status()
            payload = response.json()
    except Exception:
        return []

    raw_items = payload.get("resources") if isinstance(payload, dict) else payload
    if not isinstance(raw_items, list):
        return []

    resources: list[LearningResource] = []
    for item in raw_items[:limit]:
        if not isinstance(item, dict) or not item.get("title") or not item.get("url"):
            continue
        resources.append(
            LearningResource(
                title=str(item["title"]),
                url=str(item["url"]),
                provider=str(item.get("provider") or "external-learning-api"),
                source_type="external-api-live",
                description=str(item.get("description") or ""),
            )
        )
    return resources


def _public_api_resources(domain: str, track: str, target_level: str | None, limit: int) -> list[LearningResource]:
    if os.getenv("LEARNING_SOURCE_PUBLIC_API", "").lower() not in {"1", "true", "yes"}:
        return []

    query = " ".join(part for part in [domain, track, target_level, "learning resources"] if part)
    try:
        with httpx.Client(timeout=6.0) as client:
            response = client.get(
                "https://en.wikipedia.org/w/api.php",
                params={
                    "action": "opensearch",
                    "search": query,
                    "limit": limit,
                    "namespace": 0,
                    "format": "json",
                },
                headers={"Accept": "application/json", "User-Agent": "SereinLearning/1.0"},
            )
            response.raise_for_status()
            payload = response.json()
    except Exception:
        return []

    if not isinstance(payload, list) or len(payload) < 4:
        return []

    titles = payload[1] if isinstance(payload[1], list) else []
    descriptions = payload[2] if isinstance(payload[2], list) else []
    urls = payload[3] if isinstance(payload[3], list) else []
    resources = []
    for index, title in enumerate(titles[:limit]):
        url = urls[index] if index < len(urls) else ""
        if not title or not url:
            continue
        resources.append(
            LearningResource(
                title=str(title),
                url=str(url),
                provider="wikipedia-opensearch",
                source_type="public-api-live",
                description=str(descriptions[index] if index < len(descriptions) else ""),
            )
        )
    return resources


def get_learning_resources(domain: str, track: str, target_level: str | None = None, limit: int = 6) -> list[dict]:
    resources = _external_api_resources(domain, track, target_level, limit)
    if not resources:
        resources = _public_api_resources(domain, track, target_level, limit)
    if not resources:
        resources = CURATED_RESOURCES[_resource_key(domain, track)]

    return [asdict(resource) for resource in resources[:limit]]


def build_resource_search_url(domain: str, track: str, target_level: str | None = None) -> str:
    query = quote_plus(" ".join(part for part in [domain, track, target_level, "learning resources"] if part))
    return f"https://www.google.com/search?q={query}"
