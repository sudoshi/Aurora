"""Shared test fixtures for Aurora AI service."""

from unittest.mock import AsyncMock, MagicMock, patch

import pytest
from fastapi.testclient import TestClient

from app.main import app


@pytest.fixture
def client():
    """FastAPI TestClient using the main app instance."""
    return TestClient(app)


@pytest.fixture
def mock_ollama():
    """Mock Ollama (httpx.AsyncClient.post) with a canned response."""
    mock_response = MagicMock()
    mock_response.status_code = 200
    mock_response.json.return_value = {
        "model": "medgemma-q4:latest",
        "response": "Mock AI response for testing.",
    }
    mock_response.raise_for_status = MagicMock()

    with patch("httpx.AsyncClient.post", new_callable=AsyncMock) as mocked:
        mocked.return_value = mock_response
        yield mocked


@pytest.fixture
def mock_anthropic():
    """Mock Anthropic AsyncAnthropic client."""
    mock_text_block = MagicMock(text="Mock Claude response")
    mock_message = MagicMock()
    mock_message.content = [mock_text_block]

    mock_client = MagicMock()
    mock_client.messages.create = AsyncMock(return_value=mock_message)

    with patch("anthropic.AsyncAnthropic", return_value=mock_client) as mocked:
        yield mock_client
