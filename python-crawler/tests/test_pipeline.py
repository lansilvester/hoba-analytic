from unittest.mock import patch

from crawler.models import RawArticle
from crawler.pipeline import run


class FakeDispatcher:
    def __init__(self):
        self.received = []

    def fetch_keywords(self, project_id):
        return ["Pixel Joy"]

    def dispatch(self, project_id, articles):
        self.received = list(articles)
        return {"created": len(articles), "skipped": 0}


def test_pipeline_collects_cleans_and_dispatches(monkeypatch):
    fake = FakeDispatcher()

    class FakeCollectorA:
        source_name = "Kompas"

        def collect(self, keywords=None, since=None, until=None):
            return [
                RawArticle(
                    source="Kompas",
                    title="Berita A",
                    url="https://a.com/1",
                    content="<p>baik</p>",
                ),
                RawArticle(
                    source="Kompas",
                    title="Berita Iklan",
                    url="https://a.com/2",
                    content="Promosi, iklan",
                ),
            ]

    class FakeCollectorB:
        source_name = "Detik"

        def collect(self, keywords=None, since=None, until=None):
            return [
                RawArticle(
                    source="Detik",
                    title="Berita A dupe",
                    url="https://a.com/1?utm=1",
                    content="baik",
                ),
            ]

    with patch(
        "crawler.pipeline.all_collectors",
        return_value=[FakeCollectorA(), FakeCollectorB()],
    ):
        result = run(project_id=1, dispatcher=fake)

    assert result == {"created": 1, "skipped": 0}
    assert len(fake.received) == 1
    assert fake.received[0].title == "Berita A"
    assert fake.received[0].content == "baik"


def test_pipeline_passes_keywords_and_filters_by_period(monkeypatch):
    fake = FakeDispatcher()
    from datetime import datetime, timezone

    period_start = datetime(2026, 8, 1, tzinfo=timezone.utc)
    period_end = datetime(2026, 8, 31, tzinfo=timezone.utc)

    class FakeCollector:
        source_name = "Kompas"

        def collect(self, keywords=None, since=None, until=None):
            assert keywords == ["Pixel Joy"]
            assert since == period_start and until == period_end
            return [
                RawArticle(
                    source="Kompas",
                    title="Dalam periode",
                    url="https://a.com/1",
                    published_at=datetime(2026, 8, 10, tzinfo=timezone.utc),
                ),
                RawArticle(
                    source="Kompas",
                    title="Di luar periode",
                    url="https://a.com/2",
                    published_at=datetime(2026, 7, 1, tzinfo=timezone.utc),
                ),
            ]

    with patch("crawler.pipeline.all_collectors", return_value=[FakeCollector()]):
        result = run(
            project_id=1, dispatcher=fake, since=period_start, until=period_end
        )

    assert result["created"] == 1
    assert fake.received[0].title == "Dalam periode"


def test_pipeline_filters_by_source(monkeypatch):
    fake = FakeDispatcher()

    class FakeNews:
        source_name = "Kompas"

        def collect(self, keywords=None, since=None, until=None):
            return [RawArticle(source="Kompas", title="Berita", url="https://a.com/1")]

    class FakeTwitter:
        source_name = "X (Twitter)"

        def collect(self, keywords=None, since=None, until=None):
            return [
                RawArticle(
                    source="X (Twitter)",
                    title="Tweet",
                    url="https://x.com/u/status/1",
                    type="social",
                )
            ]

    with patch(
        "crawler.pipeline.all_collectors", return_value=[FakeNews(), FakeTwitter()]
    ):
        result = run(project_id=1, dispatcher=fake, sources=["twitter"])

    assert result["created"] == 1
    assert fake.received[0].source == "X (Twitter)"
