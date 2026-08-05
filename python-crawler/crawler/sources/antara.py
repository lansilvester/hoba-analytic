from crawler.collectors.rss import RSSCollector


class AntaraCollector(RSSCollector):
    source_name = "Antara"

    def __init__(self) -> None:
        super().__init__(self.source_name, "https://www.antaranews.com/rss/terkini")
