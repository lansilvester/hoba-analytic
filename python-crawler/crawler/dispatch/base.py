from abc import ABC, abstractmethod

from crawler.models import RawArticle


class Dispatcher(ABC):
    """Pushes collected articles to the backend storage."""

    @abstractmethod
    def dispatch(self, project_id: int, articles: list[RawArticle]) -> dict[str, int]:
        """Persist articles; returns {"created": n, "skipped": n}."""
