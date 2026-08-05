import os
from dataclasses import dataclass
from functools import lru_cache

PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))


@dataclass(frozen=True)
class Settings:
    app_name: str
    app_env: str
    debug: bool
    host: str
    port: int

    @classmethod
    def from_env(cls) -> "Settings":
        return cls(
            app_name=os.getenv("APP_NAME", "Pixel Joy Analytic - Sentiment API"),
            app_env=os.getenv("APP_ENV", "development"),
            debug=os.getenv("DEBUG", "true").lower() == "true",
            host=os.getenv("HOST", "0.0.0.0"),
            port=int(os.getenv("PORT", "8000")),
        )


@lru_cache(maxsize=1)
def get_settings() -> Settings:
    return Settings.from_env()
