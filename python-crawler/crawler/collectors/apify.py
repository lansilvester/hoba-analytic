import logging
from datetime import datetime, timezone
from email.utils import parsedate_to_datetime

from apify_client import ApifyClient

from crawler.collectors.base import Collector
from crawler.config import get_settings
from crawler.models import RawArticle

logger = logging.getLogger(__name__)


def parse_apify_datetime(value: object) -> datetime | None:
    """Parse ISO, twitter-style or epoch timestamps into an aware datetime."""
    if value is None or value == "":
        return None
    if isinstance(value, (int, float)):
        try:
            return datetime.fromtimestamp(value, tz=timezone.utc)
        except (ValueError, OverflowError):
            return None
    text = str(value).strip()
    try:
        return datetime.fromisoformat(text.replace("Z", "+00:00"))
    except ValueError:
        pass
    try:
        return parsedate_to_datetime(text)
    except (TypeError, ValueError):
        return None


def slug_hashtag(keyword: str) -> str:
    return "".join(ch for ch in keyword.lower() if ch.isalnum())


class ApifyCollector(Collector):
    """Runs an Apify actor and maps dataset items to RawArticles."""

    source_name: str = "generic"
    type: str = "social"
    actor_id: str = ""

    def __init__(self) -> None:
        self.settings = get_settings()

    def build_input(
        self,
        keywords: list[str],
        since: datetime | None,
        until: datetime | None,
    ) -> dict:
        raise NotImplementedError

    def to_articles(self, item: dict) -> list[RawArticle]:
        raise NotImplementedError

    def collect(
        self,
        keywords: list[str] | None = None,
        since: datetime | None = None,
        until: datetime | None = None,
    ) -> list[RawArticle]:
        settings = self.settings
        if not settings.apify_token:
            logger.warning("APIFY_TOKEN kosong, kolektor %s dilewati", self.source_name)
            return []
        if not keywords:
            logger.info("Tidak ada keyword untuk kolektor %s", self.source_name)
            return []

        run_input = self.build_input(keywords, since, until)
        try:
            client = ApifyClient(
                settings.apify_token, timeout=settings.apify_timeout_minutes * 60_000
            )
            run = client.actor(self.actor_id).call(run_input=run_input)
            dataset_id = run.get("defaultDatasetId") if isinstance(run, dict) else None
            if not dataset_id:
                logger.warning("Actor %s tidak mengembalikan dataset", self.actor_id)
                return []

            articles: list[RawArticle] = []
            for item in client.dataset(dataset_id).iterate_items():
                if not isinstance(item, dict):
                    continue
                articles.extend(self.to_articles(item))
            return articles
        except Exception as exc:  # noqa: BLE001 - isolasi per collector
            logger.warning("Apify actor %s gagal: %s", self.actor_id, exc)
            return []
