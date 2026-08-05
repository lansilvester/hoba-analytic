import os
from dataclasses import dataclass
from functools import lru_cache

from dotenv import load_dotenv

load_dotenv()

PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))


@dataclass(frozen=True)
class Settings:
    backend_api_url: str
    crawler_token: str
    source_timeout: int
    user_agent: str
    scheduler_interval_minutes: int
    max_articles_per_source: int
    apify_token: str = ""
    apify_timeout_minutes: int = 15
    twitter_actor_id: str = "apidojo/tweet-scraper"
    twitter_max_items: int = 200
    twitter_language: str = "id"
    youtube_actor_id: str = "apify/youtube-scraper"
    youtube_max_videos: int = 50
    youtube_comments_max_per_video: int = 20
    instagram_actor_id: str = "apify/instagram-hashtag-scraper"
    instagram_max_items: int = 100
    instagram_scrape_comments: bool = True
    instagram_username: str = ""
    instagram_password: str = ""

    @classmethod
    def from_env(cls) -> "Settings":
        return cls(
            backend_api_url=os.getenv("BACKEND_API_URL", "http://127.0.0.1:8000/api"),
            crawler_token=os.getenv("CRAWLER_API_TOKEN", ""),
            source_timeout=int(os.getenv("SOURCE_TIMEOUT_SECONDS", "15")),
            user_agent=os.getenv("USER_AGENT", "Mozilla/5.0 (compatible; PixelJoyCrawler/0.1)"),
            scheduler_interval_minutes=int(os.getenv("SCHEDULER_INTERVAL_MINUTES", "30")),
            max_articles_per_source=int(os.getenv("MAX_ARTICLES_PER_SOURCE", "25")),
            apify_token=os.getenv("APIFY_TOKEN", ""),
            apify_timeout_minutes=int(os.getenv("APIFY_TIMEOUT_MINUTES", "15")),
            twitter_actor_id=os.getenv("TWITTER_ACTOR_ID", "apidojo/tweet-scraper"),
            twitter_max_items=int(os.getenv("TWITTER_MAX_ITEMS", "200")),
            twitter_language=os.getenv("TWITTER_LANGUAGE", "id"),
            youtube_actor_id=os.getenv("YOUTUBE_ACTOR_ID", "apify/youtube-scraper"),
            youtube_max_videos=int(os.getenv("YOUTUBE_MAX_VIDEOS", "50")),
            youtube_comments_max_per_video=int(os.getenv("YOUTUBE_COMMENTS_MAX_PER_VIDEO", "20")),
            instagram_actor_id=os.getenv("INSTAGRAM_ACTOR_ID", "apify/instagram-hashtag-scraper"),
            instagram_max_items=int(os.getenv("INSTAGRAM_MAX_ITEMS", "100")),
            instagram_scrape_comments=os.getenv("INSTAGRAM_SCRAPE_COMMENTS", "true").lower() in ("1", "true", "yes"),
            instagram_username=os.getenv("INSTAGRAM_USERNAME", ""),
            instagram_password=os.getenv("INSTAGRAM_PASSWORD", ""),
        )


@lru_cache(maxsize=1)
def get_settings() -> Settings:
    return Settings.from_env()
