from fastapi import FastAPI

from app.api import analyze, health
from app.core.config import get_settings

settings = get_settings()

app = FastAPI(
    title=settings.app_name,
    version="0.1.0",
    debug=settings.debug,
)

app.include_router(health.router)
app.include_router(analyze.router)
