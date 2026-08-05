from pydantic import BaseModel, Field


class ArticleRequest(BaseModel):
    article_id: int
    title: str
    content: str = Field(default="")
    source: str | None = None
    published_at: str | None = None


class Sentiment(BaseModel):
    label: str
    confidence: float


class Entity(BaseModel):
    type: str
    text: str


class Topic(BaseModel):
    cluster_id: int | None = None
    label: str


class AnalyzeResponse(BaseModel):
    article_id: int
    sentiment: Sentiment
    topic: Topic
    entities: list[Entity]
