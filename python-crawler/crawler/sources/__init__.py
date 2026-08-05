from crawler.sources.antara import AntaraCollector
from crawler.sources.detik import DetikCollector
from crawler.sources.kompas import KompasCollector
from crawler.sources.tempo import TempoCollector

SOURCE_REGISTRY: dict[str, type] = {
    "kompas": KompasCollector,
    "detik": DetikCollector,
    "tempo": TempoCollector,
    "antara": AntaraCollector,
}


def get_collector(source_key: str):
    collector_cls = SOURCE_REGISTRY[source_key]
    return collector_cls()


def all_collectors() -> list:
    return [collector_cls() for collector_cls in SOURCE_REGISTRY.values()]
