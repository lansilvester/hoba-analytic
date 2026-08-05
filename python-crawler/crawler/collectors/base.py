from abc import ABC, abstractmethod

from crawler.models import RawArticle


class Collector(ABC):
    """Fetches raw articles from a single source."""

    source_name: str = "generic"

    @abstractmethod
    def collect(self) -> list[RawArticle]:
        """Return a list of raw articles. Must not raise on network errors."""
