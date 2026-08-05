from datetime import datetime
from typing import Any

from pydantic import BaseModel, field_validator


class RawArticle(BaseModel):
    source: str
    title: str
    url: str
    content: str = ""
    published_at: datetime | None = None

    @field_validator("title")
    @classmethod
    def title_not_empty(cls, value: str) -> str:
        value = value.strip()
        if not value:
            raise ValueError("title cannot be empty")
        return value

    @field_validator("url")
    @classmethod
    def normalize_url(cls, value: str) -> str:
        value = value.strip()
        if not value.startswith(("http://", "https://")):
            raise ValueError("url must be absolute")
        return value.split("?")[0].rstrip("/")

    def to_ingest_payload(self) -> dict[str, Any]:
        return {
            "source": self.source,
            "title": self.title,
            "url": self.url,
            "content": self.content,
            "published_at": self.published_at.isoformat() if self.published_at else None,
        }
