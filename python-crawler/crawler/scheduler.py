import argparse
import logging
import time

from crawler.config import get_settings
from crawler.pipeline import run

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s")
logger = logging.getLogger(__name__)


def main() -> None:
    parser = argparse.ArgumentParser(description="Pixel Joy Analytic crawler")
    parser.add_argument("--project-id", type=int, required=True, help="Target project id pada backend")
    parser.add_argument("--once", action="store_true", help="Jalankan satu siklus lalu keluar")
    args = parser.parse_args()

    settings = get_settings()

    if args.once:
        run(args.project_id)
        return

    logger.info("Scheduler dimulai, interval %d menit. Ctrl+C untuk berhenti.", settings.scheduler_interval_minutes)
    while True:
        try:
            run(args.project_id)
        except Exception as exc:  # noqa: BLE001 - loop tak boleh mati
            logger.error("Pipeline error: %s", exc)
        time.sleep(settings.scheduler_interval_minutes * 60)


if __name__ == "__main__":
    main()
