import logging

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
            )
        )
    return cleaned


def run(project_id: int, dispatcher: object | None = None) -> dict[str, int]:
    dispatcher = dispatcher or LaravelDispatcher()

    collected: list[RawArticle] = []
    for collector in all_collectors():
        try:
            articles = collector.collect()
            logger.info("Collected %d articles from %s", len(articles), collector.source_name)
            collected.extend(articles)
        except Exception as exc:  # noqa: BLE001 - isolasi per collector
            logger.warning("Collector %s failed: %s", collector.source_name, exc)

    cleaned = _clean(collected)
    deduped = unique_articles(cleaned)
    logger.info("Cleaned %d -> %d unique articles", len(collected), len(deduped))

    result = dispatcher.dispatch(project_id, deduped)
    logger.info("Dispatch result: created=%s skipped=%s", result["created"], result["skipped"])
    return result
