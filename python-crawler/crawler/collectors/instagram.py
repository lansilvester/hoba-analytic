from datetime import datetime

from crawler.collectors.apify import ApifyCollector, parse_apify_datetime, slug_hashtag
from crawler.models import RawArticle


class InstagramCollector(ApifyCollector):
    """Collects Instagram posts & comments by keyword (hashtag) via Apify."""

    source_name = "Instagram"

    def __init__(self) -> None:
        super().__init__()
        self.actor_id = self.settings.instagram_actor_id
        self.max_items = self.settings.instagram_max_items
        self.scrape_comments = self.settings.instagram_scrape_comments

    def build_input(
        self,
        keywords: list[str],
        since: datetime | None,
        until: datetime | None,
    ) -> dict:
        run_input: dict = {
            "hashtags": [slug_hashtag(kw) for kw in keywords if slug_hashtag(kw)],
            "resultsLimit": self.max_items,
            "resultsType": "recent",
        }
        if self.settings.instagram_username:
            run_input["instagramUsername"] = self.settings.instagram_username
            run_input["instagramPassword"] = self.settings.instagram_password
            run_input["scrapeComments"] = self.scrape_comments
            run_input["maxComments"] = 5
        if since is not None:
            run_input["dateSince"] = since.strftime("%Y-%m-%d")
        if until is not None:
            run_input["dateUntil"] = until.strftime("%Y-%m-%d")
        return run_input

    def to_articles(self, item: dict) -> list[RawArticle]:
        short_code = item.get("shortCode")
        media_id = item.get("id")
        post_url = (
            item.get("url")
            or f"https://www.instagram.com/p/{short_code or media_id or ''}/"
        )
        if not short_code and not media_id:
            return []

        published = parse_apify_datetime(item.get("timestamp"))
        articles: list[RawArticle] = []

        caption = str(item.get("caption") or "").strip()
        if caption:
            articles.append(
                RawArticle(
                    source=self.source_name,
                    title=caption[:160],
                    url=post_url,
                    content=caption,
                    published_at=published,
                    type="social",
                )
            )

        for comment in item.get("comments") or []:
            text = str(
                comment.get("text") if isinstance(comment, dict) else comment or ""
            ).strip()
            if not text:
                continue
            comment_id = comment.get("id") if isinstance(comment, dict) else None
            url = f"{post_url}comment/{comment_id}/" if comment_id else post_url
            comment_published = (
                parse_apify_datetime(
                    comment.get("createdAt") or comment.get("timestamp")
                )
                if isinstance(comment, dict)
                else published
            )
            articles.append(
                RawArticle(
                    source=self.source_name,
                    title=text[:160],
                    url=url,
                    content=text,
                    published_at=comment_published,
                    type="social",
                )
            )

        if not articles:
            return [
                RawArticle(
                    source=self.source_name,
                    title="Postingan Instagram",
                    url=post_url,
                    content=caption,
                    published_at=published,
                    type="social",
                )
            ]
        return articles
