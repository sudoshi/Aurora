"""Health endpoint tests for Aurora AI service."""


def test_health_endpoint(client):
    response = client.get("/api/ai/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "ok"
    assert data["service"] == "aurora-ai"


def test_health_returns_full_payload(client):
    """Verify health endpoint returns complete payload shape."""
    response = client.get("/api/ai/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "ok"
    assert data["service"] == "aurora-ai"
    assert data["version"] == "2.0.0"
    assert data["llm"]["provider"] == "ollama"
    assert "medgemma" in data["llm"]["model"]
    assert "status" in data["llm"]


def test_health_ollama_available(client, mock_ollama_health):
    """Verify health reports ollama ok when available."""
    mock_ollama_health.return_value = "ok"
    response = client.get("/api/ai/health")
    assert response.status_code == 200
    assert response.json()["llm"]["status"] == "ok"


def test_health_ollama_unavailable(client, mock_ollama_health):
    """Verify health reports ollama unavailable."""
    mock_ollama_health.return_value = "unavailable"
    response = client.get("/api/ai/health")
    assert response.status_code == 200
    assert response.json()["llm"]["status"] == "unavailable"


def test_health_ollama_model_not_found(client, mock_ollama_health):
    """Verify health reports model_not_found with available models."""
    mock_ollama_health.return_value = "model_not_found (available: llama3)"
    response = client.get("/api/ai/health")
    assert response.status_code == 200
    assert "model_not_found" in response.json()["llm"]["status"]
