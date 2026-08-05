from crawler.cleaners import (
    is_spam_or_promo,
    normalize_url,
    strip_html,
    unique_articles,
)
from crawler.models import RawArticle


def test_strip_html_removes_tags_and_scripts():
    html = "<script>var x = 1;</script><p>Ekonomi <b>membaik</b>  di&nbsp;2026</p>"
    assert strip_html(html) == "Ekonomi membaik di 2026"


def test_strip_html_handles_entities():
    assert strip_html("Harga &amp; pasar naik") == "Harga & pasar naik"


def test_spam_detection():
    assert is_spam_or_promo("Diskon besar", "Beli sekarang juga, ini iklan")
    assert not is_spam_or_promo("Pertumbuhan ekonomi", "Data BPS menunjukkan pertumbuhan")


def test_normalize_url_strips_query_and_fragment():
    assert normalize_url("https://a.com/x?utm_source=1#top") == "https://a.com/x"


def test_unique_articles_dedups():
    articles = [
        RawArticle(source="Kompas", title="A", url="https://a.com/x"),
        RawArticle(source="Kompas", title="A dupe", url="https://a.com/x?utm=2"),
        RawArticle(source="Detik", title="B", url="https://b.com/y"),
    ]
    unique = unique_articles(articles)
    assert len(unique) == 2
    assert unique[0].url == "https://a.com/x"
