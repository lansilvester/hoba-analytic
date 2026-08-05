import argparse
import logging
import time
from datetime import datetime, timedelta, timezone

from crawler.config import get_settings
from crawler.pipeline import run

logging.basicConfig(
    level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s"
)
logger = logging.getLogger(__name__)


def parse_datetime(value: str, name: str) -> datetime:
    text = value.strip()
    try:
        if len(text) == 10:
            return datetime.strptime(text, "%Y-%m-%d").replace(tzinfo=timezone.utc)
        dt = datetime.fromisoformat(text)
        return dt if dt.tzinfo is not None else dt.replace(tzinfo=timezone.utc)
    except ValueError as exc:
        raise argparse.ArgumentTypeError(f"{name} tidak valid: {value}") from exc


def main() -> None:
    parser = argparse.ArgumentParser(description="Pixel Joy Analytic crawler")
    parser.add_argument(
        "--project-id", type=int, required=True, help="Target project id pada backend"
    )
    parser.add_argument(
        "--once", action="store_true", help="Jalankan satu siklus lalu keluar"
    )
    parser.add_argument(
        "--keywords", nargs="*", help="Override keyword (default: ambil dari backend)"
    )
    parser.add_argument(
        "--since",
        type=lambda v: parse_datetime(v, "since"),
        help="Periode mulai (YYYY-MM-DD atau ISO)",
    )
    parser.add_argument(
        "--until",
        type=lambda v: parse_datetime(v, "until"),
        help="Periode akhir (YYYY-MM-DD atau ISO)",
    )
    parser.add_argument(
        "--days", type=int, help="Kumpulkan data N hari terakhir (menggantikan --since)"
    )
    parser.add_argument(
        "--sources",
        nargs="*",
        help="Filter sumber (mis. twitter youtube instagram kompas)",
    )
    args = parser.parse_args()

    settings = get_settings()

    since = args.since
    until = args.until
    if args.days and since is None:
        since = datetime.now(timezone.utc).replace(
            hour=0, minute=0, second=0, microsecond=0
        ) - timedelta(days=args.days - 1)

    keywords = args.keywords

    if args.once:
        run(
            args.project_id,
            keywords=keywords,
            since=since,
            until=until,
            sources=args.sources,
        )
        return

    logger.info(
        "Scheduler dimulai, interval %d menit. Ctrl+C untuk berhenti.",
        settings.scheduler_interval_minutes,
    )
    while True:
        try:
            run(
                args.project_id,
                keywords=keywords,
                since=since,
                until=until,
                sources=args.sources,
            )
        except Exception as exc:  # noqa: BLE001 - loop tak boleh mati
            logger.error("Pipeline error: %s", exc)
        time.sleep(settings.scheduler_interval_minutes * 60)


if __name__ == "__main__":
    main()
