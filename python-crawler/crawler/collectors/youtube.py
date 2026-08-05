from datetime import datetime

from crawler.collectors.apify import ApifyCollector, parse_apify_datetime
from crawler.models import RawArticle


class YouTubeCollector(ApifyCollector):
    """Collects YouTube videos & comments by keyword via apify/youtube-scraper."""

    source_name = "YouTube"

    def __init__(self) -> None:
        super().__init__()
        self.actor_id = self.settings.youtube_actor_id
        self.max_videos = self.settings.youtube_max_videos
        self.comments_max_per_video = self.settings.youtube_comments_max_per_video

    def build_input(
        self,
        keywords: list[str],
        since: datetime | None,
        until: datetime | None,
    ) -> dict:
        run_input: dict = {
            "searchKeywords": keywords,
            "maxResults": self.max_videos,
            "extractComments": True,
            "commentsMaxResults": self.comments_max_per_video,
            "extractReplies": False,
            "maxResultsShorts": 0,
        }
        if since is not None:
            run_input["startDate"] = since.strftime("%Y-%m-%d")
        if until is not None:
            run_input["endDate"] = until.strftime("%Y-%m-%d")
        return run_input

    def to_articles(self, item: dict) -> list[RawArticle]:
        video_id = item.get("videoId")
        video_url = item.get("videoUrl")
        if not video_id and not video_url:
            return []

        comment_text = str(item.get("commentText") or "").strip()
        if comment_text:
            comment_id = item.get("commentId")
            url = video_url or f"https://www.youtube.com/watch?v={video_id}"
            if video_id and comment_id:
                url = f"https://www.youtube.com/watch?v={video_id}&lc={comment_id}"
            published = parse_apify_datetime(item.get("commentPublishDate"))
            return [
                RawArticle(
                    source=self.source_name,
                    title=comment_text[:160],
                    url=url,
                    content=comment_text,
                    published_at=published,
                    type="social",
                )
            ]

        title = str(item.get("title") or "").strip()
        if not title:
            return []
        published = parse_apify_datetime(item.get("publishDate"))
        return [
            RawArticle(
                source=self.source_name,
                title=title,
                url=video_url or f"https://www.youtube.com/watch?v={video_id}",
                content=str(item.get("description") or ""),
                published_at=published,
                type="social",
            )
        ]
