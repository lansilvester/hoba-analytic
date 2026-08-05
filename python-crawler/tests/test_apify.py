from datetime import datetime, timezone

from crawler.collectors.instagram import InstagramCollector
from crawler.collectors.twitter import TwitterCollector
from crawler.collectors.youtube import YouTubeCollector
from crawler.config import Settings


def _settings(monkeypatch, **overrides) -> Settings:
    defaults = {
        "backend_api_url": "http://backend/api",
        "crawler_token": "t",
        "source_timeout": 10,
        "user_agent": "test-agent",
        "scheduler_interval_minutes": 30,
        "max_articles_per_source": 25,
        "apify_token": "apify-token",
    }
    defaults.update(overrides)
    settings = Settings(**defaults)
    monkeypatch.setattr("crawler.collectors.apify.get_settings", lambda: settings)
    return settings


class FakeApifyClient:
    """Records run inputs and returns predefined dataset items."""

    def __init__(self, items=None, error=None):
        self.calls = []
        self.items = items or []
        self.error = error

    def actor(self, actor_id):
        self.calls.append(actor_id)
        return self

    def call(self, run_input):
        self.calls.append(run_input)
        if self.error is not None:
            raise self.error
        return {"defaultDatasetId": "ds-1"}

    def dataset(self, dataset_id):
        return self

    def iterate_items(self):
        return iter(self.items)


def _install_client(monkeypatch, items=None, error=None) -> FakeApifyClient:
    client = FakeApifyClient(items=items, error=error)
    monkeypatch.setattr(
        "crawler.collectors.apify.ApifyClient", lambda token=None, timeout=None: client
    )
    return client


def test_twitter_collector_maps_tweets(monkeypatch):
    _settings(monkeypatch)
    client = _install_client(
        monkeypatch,
        [
            {
                "id": "123",
                "full_text": "Pixel Joy meluncurkan fitur baru",
                "created_at": "2026-08-05T09:00:00.000Z",
                "user": {"username": "pixeljoy"},
            }
        ],
    )
    articles = TwitterCollector().collect(keywords=["Pixel Joy"])

    assert len(articles) == 1
    assert articles[0].source == "X (Twitter)"
    assert articles[0].url == "https://x.com/pixeljoy/status/123"
    assert articles[0].type == "social"
    assert client.calls[1]["searchTerms"] == ["Pixel Joy"]
    assert client.calls[1]["maxItems"] == 200


def test_twitter_collector_sends_period(monkeypatch):
    _settings(monkeypatch)
    client = _install_client(monkeypatch, [])
    since = datetime(2026, 8, 1, tzinfo=timezone.utc)
    until = datetime(2026, 8, 31, tzinfo=timezone.utc)

    TwitterCollector().collect(keywords=["Pixel Joy"], since=since, until=until)

    assert client.calls[1]["start"] == "2026-08-01"
    assert client.calls[1]["end"] == "2026-08-31"


def test_youtube_collector_maps_comments(monkeypatch):
    _settings(monkeypatch)
    _install_client(
        monkeypatch,
        [
            {
                "videoId": "abc123",
                "videoTitle": "Demo Pixel Joy",
                "commentId": "c1",
                "commentText": "Komentar bagus",
                "commentPublishDate": "2026-08-04T10:00:00.000Z",
            }
        ],
    )
    articles = YouTubeCollector().collect(keywords=["media monitoring"])

    assert len(articles) == 1
    assert articles[0].url == "https://www.youtube.com/watch?v=abc123&lc=c1"
    assert articles[0].content == "Komentar bagus"
    assert articles[0].type == "social"


def test_youtube_collector_maps_videos(monkeypatch):
    _settings(monkeypatch)
    _install_client(
        monkeypatch,
        [
            {
                "videoId": "abc123",
                "title": "Video Pixel Joy",
                "publishDate": "2026-08-03T10:00:00.000Z",
                "description": "Deskripsi video",
            }
        ],
    )
    articles = YouTubeCollector().collect(keywords=["Pixel Joy"])

    assert len(articles) == 1
    assert articles[0].title == "Video Pixel Joy"
    assert articles[0].url == "https://www.youtube.com/watch?v=abc123"


def test_instagram_collector_maps_posts_and_comments(monkeypatch):
    _settings(monkeypatch)
    _install_client(
        monkeypatch,
        [
            {
                "id": "m1",
                "shortCode": "ABC123",
                "caption": "Kampanye media monitoring",
                "timestamp": "2026-08-02T10:00:00.000Z",
                "comments": [
                    {
                        "id": "cm1",
                        "text": "Setuju sekali",
                        "createdAt": "2026-08-02T11:00:00.000Z",
                    },
                ],
            }
        ],
    )
    articles = InstagramCollector().collect(keywords=["media monitoring"])

    assert len(articles) == 2
    assert articles[0].content == "Kampanye media monitoring"
    assert articles[1].content == "Setuju sekali"
    assert articles[1].url == "https://www.instagram.com/p/ABC123/comment/cm1"
    assert articles[1].published_at == datetime(2026, 8, 2, 11, 0, tzinfo=timezone.utc)


def test_instagram_hashtags_derived_from_keywords(monkeypatch):
    _settings(monkeypatch)
    client = _install_client(monkeypatch, [])

    InstagramCollector().collect(keywords=["Media Monitoring", "Reputasi Brand"])

    assert client.calls[1]["hashtags"] == ["mediamonitoring", "reputasibrand"]


def test_apify_collector_skips_without_token(monkeypatch):
    _settings(monkeypatch, apify_token="")

    assert TwitterCollector().collect(keywords=["Pixel Joy"]) == []
    assert YouTubeCollector().collect(keywords=["Pixel Joy"]) == []
    assert InstagramCollector().collect(keywords=["Pixel Joy"]) == []


def test_apify_collector_skips_without_keywords(monkeypatch):
    _settings(monkeypatch)

    assert TwitterCollector().collect(keywords=None) == []
    assert TwitterCollector().collect(keywords=[]) == []


def test_apify_collector_handles_run_failure(monkeypatch):
    _settings(monkeypatch)
    _install_client(monkeypatch, error=RuntimeError("actor crashed"))

    assert TwitterCollector().collect(keywords=["Pixel Joy"]) == []
