import logging
from datetime import datetime, timezone
from time import mktime

import feedparser
import httpx

from crawler.collectors.base import Collector
from crawler.config import get_settings
from crawler.models import RawArticle

logger = logging.getLogger(__name__)


class RSSCollector(Collector):
    """Collects articles from an RSS/Atom feed."""

    def __init__(self, source_name: str, feed_url: str) -> None:
        self.source_name = source_name
        self.feed_url = feed_url

    def collect(self) -> list[RawArticle]:
        settings = get_settings()
        headers = {"User-Agent": settings.user_agent}
        try:
            response = httpx.get(self.feed_url, headers=headers, timeout=settings.source_timeout, follow_redirects=True)
            response.raise_for_status()
            parsed = feedparser.parse(response.content)
        except httpx.HTTPError as exc:
            logger.warning("Feed request failed for %s: %s", self.feed_url, exc)
            return []

        articles: list[RawArticle] = []
        for entry in parsed.entries[: settings.max_articles_per_source]:
            title = entry.get("title") or ""
            link = entry.get("link") or ""
            summary = entry.get("summary") or entry.get("description") or ""
            if not title or not link:
                continue
            published_struct = entry.get("published_parsed") or entry.get("updated_parsed")
            published = datetime.fromtimestamp(mktime(published_struct), tz=timezone.utc) if published_struct else None
            articles.append(
                RawArticle(
                    source=self.source_name,
                    title=title,
                    url=link,
                    content=summary,
                    published_at=published,
                )
            )
        return articles
