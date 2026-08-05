import logging

import httpx

from crawler.config import get_settings
from crawler.dispatch.base import Dispatcher
from crawler.models import RawArticle

logger = logging.getLogger(__name__)


class LaravelDispatcher(Dispatcher):
    """Posts articles to the Laravel backend ingest endpoint."""

    def fetch_keywords(self, project_id: int) -> list[str]:
        settings = get_settings()
        headers = {
            "X-Crawler-Token": settings.crawler_token,
            "Content-Type": "application/json",
        }
        try:
            response = httpx.get(
                f"{settings.backend_api_url.rstrip('/')}/crawler/keywords",
                params={"project_id": project_id},
                headers=headers,
                timeout=15,
            )
            response.raise_for_status()
            return [str(keyword) for keyword in response.json().get("data", [])]
        except httpx.HTTPError as exc:
            logger.error("Fetch keywords failed: %s", exc)
            return []

    def dispatch(self, project_id: int, articles: list[RawArticle]) -> dict[str, int]:
        if not articles:
            return {"created": 0, "skipped": 0}

        settings = get_settings()
        payload = {
            "project_id": project_id,
            "articles": [article.to_ingest_payload() for article in articles],
        }
        headers = {
            "X-Crawler-Token": settings.crawler_token,
            "Content-Type": "application/json",
        }
        try:
            response = httpx.post(
                f"{settings.backend_api_url.rstrip('/')}/ingest/articles",
                json=payload,
                headers=headers,
                timeout=30,
            )
            response.raise_for_status()
            data = response.json().get("data", {})
            return {
                "created": int(data.get("created", 0)),
                "skipped": int(data.get("skipped", 0)),
            }
        except httpx.HTTPError as exc:
            logger.error("Backend ingest failed: %s", exc)
            return {"created": 0, "skipped": len(articles)}
