from datetime import datetime

from crawler.collectors.apify import ApifyCollector, parse_apify_datetime
from crawler.models import RawArticle


class TwitterCollector(ApifyCollector):
    """Collects tweets by keyword via the apidojo/tweet-scraper Apify actor."""

    source_name = "X (Twitter)"

    def __init__(self) -> None:
        super().__init__()
        self.actor_id = self.settings.twitter_actor_id
        self.max_items = self.settings.twitter_max_items
        self.language = self.settings.twitter_language

    def build_input(
        self,
        keywords: list[str],
        since: datetime | None,
        until: datetime | None,
    ) -> dict:
        run_input: dict = {
            "searchTerms": keywords,
            "maxItems": self.max_items,
            "sort": "Latest",
            "includeSearchTerms": True,
        }
        if self.language:
            run_input["tweetLanguage"] = self.language
        if since is not None:
            run_input["start"] = since.strftime("%Y-%m-%d")
        if until is not None:
            run_input["end"] = until.strftime("%Y-%m-%d")
        return run_input

    def to_articles(self, item: dict) -> list[RawArticle]:
        tweet_id = item.get("id")
        text = str(item.get("full_text") or item.get("text") or "").strip()
        if not tweet_id or not text:
            return []
        user = item.get("user") or {}
        username = str(user.get("username") or user.get("screenName") or "user")
        published = parse_apify_datetime(item.get("created_at"))
        return [
            RawArticle(
                source=self.source_name,
                title=text[:160],
                url=f"https://x.com/{username}/status/{tweet_id}",
                content=text,
                published_at=published,
                type="social",
            )
        ]
