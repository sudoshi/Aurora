"""Shared LLM utility for decision support services.

Provides a reusable async Ollama call with error handling and JSON parsing.
"""

import json
import logging
from typing import Any

import httpx

from app.config import settings

logger = logging.getLogger(__name__)


async def call_ollama(
    prompt: str,
    system: str = "",
    json_mode: bool = True,
) -> str:
    """Call Ollama and return the raw response text.

    Args:
        prompt: The user prompt to send.
        system: Optional system prompt.
        json_mode: If True, request JSON-formatted output.

    Returns:
        The raw response string from the model.

    Raises:
        httpx.HTTPError: If the request fails.
    """
    payload: dict[str, Any] = {
        "model": settings.ollama_model,
        "prompt": prompt,
        "stream": False,
    }
    if system:
        payload["system"] = system
    if json_mode:
        payload["format"] = "json"

    async with httpx.AsyncClient(timeout=settings.ollama_timeout) as client:
        response = await client.post(
            f"{settings.ollama_base_url}/api/generate",
            json=payload,
        )
        response.raise_for_status()
        return response.json().get("response", "")


async def call_ollama_json(
    prompt: str,
    system: str = "",
) -> dict[str, Any]:
    """Call Ollama and parse the response as JSON.

    Returns an empty dict on parse failure (logged as warning).
    """
    raw = await call_ollama(prompt, system, json_mode=True)
    try:
        return json.loads(raw)
    except (json.JSONDecodeError, ValueError) as exc:
        logger.warning(
            "Failed to parse Ollama JSON response: %s — raw: %s",
            exc,
            raw[:300],
        )
        return {}
