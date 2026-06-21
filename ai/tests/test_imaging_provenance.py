"""Tests for machine-readable provenance/verification flags on imaging AI outputs.

Aurora's imaging AI outputs (segmentation, volume, feature extraction, response
assessment) are NOT verified clinical measurements. Each API response must carry
``data_source``, ``verified`` (False), and a non-empty ``disclaimer`` so no
consumer can mistake mock or AI-derived output for a verified clinical finding.
"""

from unittest.mock import AsyncMock, patch

import pytest

from app.services.segmentation_service import run_segmentation


@pytest.fixture
def mock_no_measurements():
    """Patch the DB measurement fetch to return an empty list (no real DB)."""
    with patch(
        "app.routers.imaging._fetch_study_measurements",
        new_callable=AsyncMock,
    ) as mocked:
        mocked.return_value = []
        yield mocked


@pytest.fixture
def mock_measurements():
    """Patch the DB measurement fetch to return one tumor-volume measurement."""
    with patch(
        "app.routers.imaging._fetch_study_measurements",
        new_callable=AsyncMock,
    ) as mocked:
        mocked.return_value = [
            {
                "measurement_type": "tumor_volume",
                "target_lesion": True,
                "value_numeric": 25.0,
                "unit": "cm3",
                "measured_by": "radiologist",
                "measured_at": "2025-01-01T00:00:00",
            }
        ]
        yield mocked


@pytest.mark.asyncio
async def test_run_segmentation_includes_data_source():
    """The segmentation service itself originates the mock_model provenance."""
    # ollama_base_url is unreachable in tests -> falls back to canned ai_analysis
    result = await run_segmentation(study_id=1, body_site="chest")
    assert result["data_source"] == "mock_model"
    assert result["structure_count"] > 0


@pytest.mark.asyncio
async def test_run_segmentation_degraded_when_ollama_unreachable():
    """When Ollama is unreachable the canned ai_analysis -> ai_status=degraded.

    Structures/volumes are deterministic and must still be returned.
    """
    # ollama_base_url is unreachable in tests -> canned ai_analysis fallback
    result = await run_segmentation(study_id=1, body_site="chest")
    assert result["ai_status"] == "degraded"
    assert result["structure_count"] > 0


@pytest.mark.asyncio
async def test_run_segmentation_ok_when_ollama_succeeds():
    """When the LLM call succeeds, ai_status is 'ok'."""
    mock_response = AsyncMock()
    mock_response.raise_for_status = lambda: None
    mock_response.json = lambda: {
        "response": '{"summary": "ok", "notable_findings": [], "quality_assessment": "good"}'
    }
    with patch("httpx.AsyncClient.post", new_callable=AsyncMock) as mocked:
        mocked.return_value = mock_response
        result = await run_segmentation(study_id=1, body_site="chest")
    assert result["ai_status"] == "ok"


def test_segment_response_degraded_on_ollama_failure(client):
    """Endpoint stays 200 with usable structures but ai_status=degraded.

    No mock_ollama fixture here -> the real (unreachable) Ollama URL is used,
    forcing the canned fallback path.
    """
    resp = client.post(
        "/api/ai/imaging/segment",
        json={"study_id": 1, "body_site": "chest"},
    )
    assert resp.status_code == 200, resp.text
    body = resp.json()
    assert body["ai_status"] == "degraded"
    assert body["structure_count"] > 0
    assert body["data_source"] == "mock_model"


def test_segment_response_ok_when_ollama_succeeds(client, mock_ollama):
    """With a working (mocked) LLM, the endpoint reports ai_status=ok."""
    mock_ollama.return_value.json.return_value = {
        "response": '{"summary": "ok", "notable_findings": [], "quality_assessment": "good"}'
    }
    resp = client.post(
        "/api/ai/imaging/segment",
        json={"study_id": 1, "body_site": "chest"},
    )
    assert resp.status_code == 200, resp.text
    assert resp.json()["ai_status"] == "ok"


def test_segment_response_carries_provenance(client, mock_ollama):
    resp = client.post(
        "/api/ai/imaging/segment",
        json={"study_id": 1, "body_site": "chest"},
    )
    assert resp.status_code == 200, resp.text
    body = resp.json()
    assert body["data_source"] == "mock_model"
    assert body["verified"] is False
    assert isinstance(body["disclaimer"], str) and body["disclaimer"]
    assert "Research use only" in body["disclaimer"]


def test_volume_response_carries_provenance(client, mock_ollama, mock_measurements):
    resp = client.post(
        "/api/ai/imaging/volume",
        json={"study_id": 1, "measurement_type": "tumor_volume"},
    )
    assert resp.status_code == 200, resp.text
    body = resp.json()
    assert body["data_source"] == "computed_from_measurements"
    assert body["verified"] is False
    assert isinstance(body["disclaimer"], str) and body["disclaimer"]
    assert "Research use only" in body["disclaimer"]


def test_feature_response_carries_provenance(client, mock_ollama, mock_measurements):
    resp = client.post(
        "/api/ai/imaging/extract-features",
        json={"study_id": 1},
    )
    assert resp.status_code == 200, resp.text
    body = resp.json()
    assert body["data_source"] == "ai_generated"
    assert body["verified"] is False
    assert isinstance(body["disclaimer"], str) and body["disclaimer"]
    assert "Research use only" in body["disclaimer"]


def test_feature_response_provenance_on_fallback(client, mock_measurements):
    """Even when Ollama is unavailable (fallback path), provenance is present."""
    resp = client.post(
        "/api/ai/imaging/extract-features",
        json={"study_id": 1},
    )
    assert resp.status_code == 200, resp.text
    body = resp.json()
    assert body["data_source"] == "ai_generated"
    assert body["verified"] is False
    assert body["disclaimer"]


def test_response_assessment_carries_provenance(
    client, mock_ollama, mock_measurements
):
    resp = client.post(
        "/api/ai/imaging/response",
        json={
            "patient_id": 1,
            "baseline_study_id": 1,
            "current_study_id": 2,
            "criteria": "recist",
        },
    )
    assert resp.status_code == 200, resp.text
    body = resp.json()
    assert body["data_source"] == "rule_based"
    assert body["verified"] is False
    assert body["disclaimer"]
    # numeric algorithmic output must remain a valid RECIST category
    assert body["response_category"] in {"CR", "PR", "SD", "PD", "NE"}
