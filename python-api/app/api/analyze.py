from fastapi import APIRouter, HTTPException

from app.schemas.analyze import (
    AnalyzeResponse,
    ArticleRequest,
    Entity,
    Sentiment,
    Topic,
)
from app.services.sentiment import (
    TOPIC_CLUSTER_IDS,
    analyze_sentiment,
    detect_topic,
    extract_entities,
)

router = APIRouter(prefix="/analyze", tags=["analyze"])


@router.post("", response_model=AnalyzeResponse)
def analyze_article(payload: ArticleRequest) -> AnalyzeResponse:
    if not payload.title.strip() and not payload.content.strip():
        raise HTTPException(status_code=422, detail="title and content cannot both be empty")

    text = f"{payload.title}. {payload.content}"

    label, confidence = analyze_sentiment(text)
    topic_label = detect_topic(text)
    entities = [Entity(**entity) for entity in extract_entities(text)]

    return AnalyzeResponse(
        article_id=payload.article_id,
        sentiment=Sentiment(label=label, confidence=confidence),
        topic=Topic(cluster_id=TOPIC_CLUSTER_IDS.get(topic_label), label=topic_label),
        entities=entities,
    )
