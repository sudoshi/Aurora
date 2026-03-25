"""Genomic briefing endpoint tests for Aurora AI service."""

from unittest.mock import MagicMock

import httpx


BRIEFING_URL = "/api/ai/decision-support/genomic-briefing"


def test_briefing_with_actionable_variants(
    client, mock_ollama, actionable_briefing_payload
):
    """POST with actionable variants returns LLM-generated briefing."""
    # Configure mock to return valid JSON in the Ollama double-JSON pattern
    mock_ollama.return_value.json.return_value = {
        "response": '{"briefing": "BRAF V600E detected with Level 1A evidence..."}'
    }

    response = client.post(BRIEFING_URL, json=actionable_briefing_payload)

    assert response.status_code == 200
    data = response.json()
    assert isinstance(data["briefing"], str)
    assert len(data["briefing"]) > 0
    assert data["actionable_count"] == 1
    assert data["variant_count"] == 5
    assert data["generated_at"] != ""


def test_briefing_no_actionable_variants(client, vus_only_payload):
    """POST with VUS-only variants returns static early-return message (no LLM call)."""
    response = client.post(BRIEFING_URL, json=vus_only_payload)

    assert response.status_code == 200
    data = response.json()
    assert "No actionable" in data["briefing"]
    assert data["actionable_count"] == 0


def test_briefing_empty_variants(client):
    """POST with empty variants list returns no-actionable message."""
    payload = {
        "patient_id": 1,
        "variants": [],
        "total_variant_count": 0,
    }
    response = client.post(BRIEFING_URL, json=payload)

    assert response.status_code == 200
    data = response.json()
    assert "No actionable" in data["briefing"]


def test_briefing_invalid_payload(client):
    """POST with empty JSON body fails Pydantic validation (patient_id required)."""
    response = client.post(BRIEFING_URL, json={})
    assert response.status_code == 422


def test_briefing_llm_failure(client, mock_ollama, actionable_briefing_payload):
    """POST with actionable variants when LLM fails returns error gracefully."""
    mock_ollama.side_effect = httpx.ConnectError("connection refused")

    response = client.post(BRIEFING_URL, json=actionable_briefing_payload)

    assert response.status_code == 200
    data = response.json()
    # The service catches the exception and returns error text in briefing
    assert (
        "failed" in data["briefing"].lower()
        or data.get("error") is not None
    )
