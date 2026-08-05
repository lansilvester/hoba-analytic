from crawler.collectors.rss import RSSCollector


class TempoCollector(RSSCollector):
    source_name = "Tempo"

    def __init__(self) -> None:
        super().__init__(self.source_name, "https://rss.tempo.co/feed")
