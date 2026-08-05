from crawler.collectors.rss import RSSCollector


class KompasCollector(RSSCollector):
    source_name = "Kompas"

    def __init__(self) -> None:
        super().__init__(self.source_name, "https://indeks.kompas.com/rss.xml")
