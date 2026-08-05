from datetime import datetime
from typing import Any
from urllib.parse import urlparse

from pydantic import BaseModel, field_validator


class RawArticle(BaseModel):
    source: str
    title: str
    url: str
    content: str = ""
    published_at: datetime | None = None
    type: str = "news"

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
        parsed = urlparse(value)
        host = parsed.hostname or ""
        if host == "youtu.be" or "youtube.com" in host:
            query = f"?{parsed.query}" if parsed.query else ""
            return f"{parsed.scheme}://{parsed.netloc}{parsed.path}".rstrip("/") + query
        return value.split("?")[0].rstrip("/")

    def to_ingest_payload(self) -> dict[str, Any]:
        return {
            "source": self.source,
            "title": self.title,
            "url": self.url,
            "content": self.content,
            "published_at": self.published_at.isoformat()
            if self.published_at
            else None,
            "type": self.type,
        }
