from crawler.collectors.instagram import InstagramCollector
from crawler.collectors.twitter import TwitterCollector
from crawler.collectors.youtube import YouTubeCollector
from crawler.sources.antara import AntaraCollector
from crawler.sources.detik import DetikCollector
from crawler.sources.kompas import KompasCollector
from crawler.sources.tempo import TempoCollector

SOURCE_REGISTRY: dict[str, type] = {
    "kompas": KompasCollector,
    "detik": DetikCollector,
    "tempo": TempoCollector,
    "antara": AntaraCollector,
    "twitter": TwitterCollector,
    "x": TwitterCollector,
    "youtube": YouTubeCollector,
    "instagram": InstagramCollector,
}


def get_collector(source_key: str):
    collector_cls = SOURCE_REGISTRY[source_key]
    return collector_cls()


def all_collectors() -> list:
    seen: set[type] = set()
    collectors = []
    for collector_cls in SOURCE_REGISTRY.values():
        if collector_cls in seen:
            continue
        seen.add(collector_cls)
        collectors.append(collector_cls())
    return collectors
