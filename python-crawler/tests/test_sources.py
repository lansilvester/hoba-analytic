from unittest.mock import patch

import httpx

from crawler.collectors.rss import RSSCollector
from crawler.config import Settings
from crawler.sources.kompas import KompasCollector

SAMPLE_FEED = b"""<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
  <title>Kompas</title>
  <item>
    <title>Pertumbuhan Ekonomi Membaik</title>
    <link>https://www.kompas.com/ekonomi/1?utm=abc</link>
    <description><![CDATA[<p>Ekonomi Indonesia <b>membaik</b>.</p>]]></description>
    <pubDate>Wed, 05 Aug 2026 09:00:00 GMT</pubDate>
  </item>
  <item>
    <title>Rupiah Anjlok di Pasar</title>
    <link>https://www.kompas.com/ekonomi/2</link>
    <pubDate>Wed, 05 Aug 2026 10:00:00 GMT</pubDate>
  </item>
  <item>
    <title>Tanpa Link</title>
    <description>Harus di-skip</description>
  </item>
</channel>
</rss>
"""


def _fake_settings(monkeypatch) -> Settings:
    settings = Settings(
        backend_api_url="http://backend/api",
        crawler_token="t",
        source_timeout=10,
        user_agent="test-agent",
        scheduler_interval_minutes=30,
        max_articles_per_source=25,
    )
    monkeypatch.setattr("crawler.collectors.rss.get_settings", lambda: settings)
    return settings


class _FakeResponse:
    def __init__(self, content: bytes):
        self.content = content

    def raise_for_status(self):
        pass


def test_rss_collector_parses_feed(monkeypatch):
    _fake_settings(monkeypatch)

    def fake_get(url, **kwargs):
        assert url == "https://indeks.kompas.com/rss.xml"
        return _FakeResponse(SAMPLE_FEED)

    with patch("crawler.collectors.rss.httpx.get", side_effect=fake_get):
        collector = KompasCollector()
        articles = collector.collect()

    assert len(articles) == 2
    assert articles[0].source == "Kompas"
    assert articles[0].title == "Pertumbuhan Ekonomi Membaik"
    assert articles[0].url == "https://www.kompas.com/ekonomi/1"
    assert articles[0].published_at is not None


def test_rss_collector_handles_network_error(monkeypatch):
    _fake_settings(monkeypatch)

    def fake_get(url, **kwargs):
        raise httpx.ConnectError("connection refused")

    with patch("crawler.collectors.rss.httpx.get", side_effect=fake_get):
        collector = RSSCollector("Kompas", "https://indeks.kompas.com/rss.xml")
        assert collector.collect() == []
