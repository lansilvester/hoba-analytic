from crawler.collectors.rss import RSSCollector


class DetikCollector(RSSCollector):
    source_name = "Detik"

    def __init__(self) -> None:
        super().__init__(self.source_name, "https://finance.detik.com/rss")
