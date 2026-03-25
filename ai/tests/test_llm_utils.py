"""Unit tests for LLM utility functions."""

import pytest

from app.services.llm_utils import call_ollama, call_ollama_json


@pytest.mark.asyncio
async def test_call_ollama_json_success(mock_ollama):
    """Valid JSON response is parsed and returned as dict."""
    mock_ollama.return_value.json.return_value = {
        "response": '{"briefing": "test"}'
    }

    result = await call_ollama_json("test prompt")

    assert result == {"briefing": "test"}


@pytest.mark.asyncio
async def test_call_ollama_json_parse_failure(mock_ollama):
    """Invalid JSON response returns empty dict."""
    mock_ollama.return_value.json.return_value = {
        "response": "not valid json {{{"
    }

    result = await call_ollama_json("test prompt")

    assert result == {}


@pytest.mark.asyncio
async def test_call_ollama_json_empty_response(mock_ollama):
    """Empty string response returns empty dict."""
    mock_ollama.return_value.json.return_value = {
        "response": ""
    }

    result = await call_ollama_json("test prompt")

    assert result == {}


@pytest.mark.asyncio
async def test_call_ollama_includes_system_prompt(mock_ollama):
    """System prompt and json_mode are passed in the request payload."""
    mock_ollama.return_value.json.return_value = {
        "response": '{"ok": true}'
    }

    await call_ollama("test", system="system prompt", json_mode=True)

    mock_ollama.assert_called_once()
    payload = mock_ollama.call_args.kwargs.get("json") or mock_ollama.call_args[1].get("json")
    assert payload["system"] == "system prompt"
    assert payload["format"] == "json"
