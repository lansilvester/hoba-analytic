from unittest.mock import patch

from crawler.models import RawArticle
from crawler.pipeline import run


class FakeDispatcher:
    def __init__(self):
        self.received = []

    def dispatch(self, project_id, articles):
        self.received = list(articles)
        return {"created": len(articles), "skipped": 0}


def test_pipeline_collects_cleans_and_dispatches(monkeypatch):
    fake = FakeDispatcher()

    class FakeCollectorA:
        source_name = "Kompas"

        def collect(self):
            return [
                RawArticle(source="Kompas", title="Berita A", url="https://a.com/1", content="<p>baik</p>"),
                RawArticle(source="Kompas", title="Berita Iklan", url="https://a.com/2", content="Promosi, iklan"),
            ]

    class FakeCollectorB:
        source_name = "Detik"

        def collect(self):
            return [
                RawArticle(source="Detik", title="Berita A dupe", url="https://a.com/1?utm=1", content="baik"),
            ]

    with patch("crawler.pipeline.all_collectors", return_value=[FakeCollectorA(), FakeCollectorB()]):
        result = run(project_id=1, dispatcher=fake)

    assert result == {"created": 1, "skipped": 0}
    assert len(fake.received) == 1
    assert fake.received[0].title == "Berita A"
    assert fake.received[0].content == "baik"
