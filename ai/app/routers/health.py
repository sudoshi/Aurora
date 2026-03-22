from typing import Any

from fastapi import APIRouter

from app.config import settings
from app.services.ollama_client import check_ollama_health

router = APIRouter()


@router.get("/health")
async def health_check() -> dict[str, Any]:
    ollama_status = await check_ollama_health()
    return {
        "status": "ok",
        "service": "aurora-ai",
        "version": "2.0.0",
        "llm": {
            "provider": "ollama",
            "model": settings.ollama_model,
            "status": ollama_status,
        },
    }
