import logging
from datetime import datetime

from crawler.cleaners import is_spam_or_promo, strip_html, unique_articles
from crawler.dispatch.laravel import LaravelDispatcher
from crawler.models import RawArticle
from crawler.sources import all_collectors

logger = logging.getLogger(__name__)


def _clean(articles: list[RawArticle]) -> list[RawArticle]:
    cleaned: list[RawArticle] = []
    for article in articles:
        content = strip_html(article.content)
        if is_spam_or_promo(article.title, content):
            continue
        cleaned.append(
            RawArticle(
                source=article.source,
                title=article.title,
                url=article.url,
                content=content,
                published_at=article.published_at,
                type=article.type,
            )
        )
    return cleaned


def _within_period(
    published: datetime | None,
    since: datetime | None,
    until: datetime | None,
) -> bool:
    if since is None and until is None:
        return True
    if published is None:
        return True
    return not (since is not None and published < since) and not (
        until is not None and published > until
    )


def _filter_period(
    articles: list[RawArticle],
    since: datetime | None,
    until: datetime | None,
) -> list[RawArticle]:
    return [
        article
        for article in articles
        if _within_period(article.published_at, since, until)
    ]


def _matches_sources(source_name: str, sources: list[str] | None) -> bool:
    if not sources:
        return True
    lowered = source_name.lower()
    return any(
        alias.lower() in lowered or lowered in alias.lower() for alias in sources
    )


def run(
    project_id: int,
    dispatcher: object | None = None,
    keywords: list[str] | None = None,
    since: datetime | None = None,
    until: datetime | None = None,
    sources: list[str] | None = None,
) -> dict[str, int]:
    dispatcher = dispatcher or LaravelDispatcher()

    if keywords is None:
        fetch = getattr(dispatcher, "fetch_keywords", lambda pid: [])
        keywords = fetch(project_id)
        logger.info("Keyword dari backend: %s", keywords or "(kosong)")

    collected: list[RawArticle] = []
    for collector in all_collectors():
        if not _matches_sources(collector.source_name, sources):
            continue
        try:
            articles = collector.collect(keywords=keywords, since=since, until=until)
            logger.info(
                "Collected %d articles from %s", len(articles), collector.source_name
            )
            collected.extend(articles)
        except Exception as exc:  # noqa: BLE001 - isolasi per collector
            logger.warning("Collector %s failed: %s", collector.source_name, exc)

    cleaned = _clean(collected)
    deduped = unique_articles(cleaned)
    deduped = _filter_period(deduped, since, until)
    logger.info(
        "Cleaned %d -> %d unique articles (dalam periode)", len(collected), len(deduped)
    )

    result = dispatcher.dispatch(project_id, deduped)
    logger.info(
        "Dispatch result: created=%s skipped=%s", result["created"], result["skipped"]
    )
    return result
