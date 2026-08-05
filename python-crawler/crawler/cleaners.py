import re
import unicodedata
from urllib.parse import urlparse

from crawler.models import RawArticle

HTML_TAG_RE = re.compile(r"<[^>]+>")
WHITESPACE_RE = re.compile(r"\s+")
SCRIPT_RE = re.compile(r"<(script|style)[^>]*>.*?</\1>", re.IGNORECASE | re.DOTALL)


def strip_html(text: str) -> str:
    text = SCRIPT_RE.sub(" ", text)
    text = HTML_TAG_RE.sub(" ", text)
    text = html_unescape_entities(text)
    text = unicodedata.normalize("NFKC", text)
    return WHITESPACE_RE.sub(" ", text).strip()


def html_unescape_entities(text: str) -> str:
    import html

    return html.unescape(text)


def is_spam_or_promo(title: str, content: str) -> bool:
    lowered = f"{title} {content}".lower()
    spam_markers = (
        "iklan",
        "sponsored",
        "promosi",
        "langganan",
        "berlangganan",
        "buletin",
    )
    return any(marker in lowered for marker in spam_markers)


def normalize_url(url: str) -> str:
    parsed = urlparse(url)
    host = parsed.hostname or ""
    if host == "youtu.be" or "youtube.com" in host:
        query = f"?{parsed.query}" if parsed.query else ""
        return f"{parsed.scheme}://{parsed.netloc}{parsed.path}".rstrip("/") + query
    return f"{parsed.scheme}://{parsed.netloc}{parsed.path}".rstrip("/")


def unique_articles(articles: list[RawArticle]) -> list[RawArticle]:
    seen: set[str] = set()
    unique: list[RawArticle] = []
    for article in articles:
        key = normalize_url(article.url)
        if key in seen:
            continue
        seen.add(key)
        unique.append(article)
    return unique
