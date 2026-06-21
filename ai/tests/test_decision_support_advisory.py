"""Tests asserting LLM decision-support responses are labelled advisory.

The decision_support endpoints are Ollama LLM reasoning with no backing
knowledge base. Every response model must carry an explicit advisory evidence
grade and a non-empty disclaimer so consumers never mistake them for
database-verified clinical decision support.
"""

import httpx
import pytest

from app.models.decision_support import (
    AI_STATUS_DEGRADED,
    AI_STATUS_OK,
    LLM_ADVISORY_DISCLAIMER,
    LLM_ADVISORY_GRADE,
    DrugInteractionResponse,
    GenomicBriefingResponse,
    GuidelineCheckResponse,
    PrognosticScoreResponse,
    RareDiseaseMatchResponse,
    TrialMatchResponse,
    VariantInterpretResponse,
)

ADVISORY_RESPONSE_FACTORIES = [
    lambda: TrialMatchResponse(patient_id=1, suggestions=[]),
    lambda: GuidelineCheckResponse(),
    lambda: DrugInteractionResponse(interactions=[]),
    lambda: VariantInterpretResponse(),
    lambda: PrognosticScoreResponse(scores=[]),
    lambda: RareDiseaseMatchResponse(matches=[]),
    lambda: GenomicBriefingResponse(),
]


@pytest.mark.parametrize("factory", ADVISORY_RESPONSE_FACTORIES)
def test_advisory_response_defaults(factory):
    """Each advisory response defaults to llm_advisory grade + non-empty disclaimer."""
    response = factory()

    assert response.evidence_grade == "llm_advisory"
    assert response.evidence_grade == LLM_ADVISORY_GRADE
    assert isinstance(response.disclaimer, str)
    assert response.disclaimer.strip() != ""
    assert response.disclaimer == LLM_ADVISORY_DISCLAIMER
    assert "verify independently" in response.disclaimer.lower()


@pytest.mark.parametrize("factory", ADVISORY_RESPONSE_FACTORIES)
def test_advisory_fields_serialized(factory):
    """The advisory fields are present in the serialized JSON payload."""
    payload = factory().model_dump()

    assert payload["evidence_grade"] == "llm_advisory"
    assert payload["disclaimer"].strip() != ""


def test_disclaimer_constant_is_meaningful():
    """The shared disclaimer constant warns it is not database-verified CDS."""
    lowered = LLM_ADVISORY_DISCLAIMER.lower()
    assert "not database-verified" in lowered
    assert "language model" in lowered


def test_advisory_response_defaults_ai_status_ok():
    """Advisory responses default to ai_status='ok' (success path)."""
    assert TrialMatchResponse(patient_id=1, suggestions=[]).ai_status == AI_STATUS_OK
    assert AI_STATUS_OK == "ok"
    assert AI_STATUS_DEGRADED == "degraded"


def test_trial_match_ai_status_ok_on_success(client, mock_ollama):
    """A working LLM yields ai_status='ok' with usable suggestions."""
    mock_ollama.return_value.json.return_value = {
        "response": (
            '{"suggestions": [{"trial_type": "Phase III", '
            '"rationale": "fits", "key_criteria_met": ["a"], '
            '"potential_exclusions": [], "confidence": "high"}]}'
        )
    }

    response = client.post(
        "/api/ai/decision-support/trial-match",
        json={"patient_id": 1, "diagnosis": "NSCLC"},
    )

    assert response.status_code == 200
    data = response.json()
    assert data["ai_status"] == "ok"


def test_trial_match_ai_status_degraded_on_ollama_failure(client, mock_ollama):
    """When the LLM call raises, endpoint stays 200 and ai_status='degraded'."""
    mock_ollama.side_effect = httpx.ConnectError("connection refused")

    response = client.post(
        "/api/ai/decision-support/trial-match",
        json={"patient_id": 1, "diagnosis": "NSCLC"},
    )

    assert response.status_code == 200
    data = response.json()
    assert data["ai_status"] == "degraded"
    assert data["suggestions"] == []  # graceful fallback content still returned
    assert data["error"] is not None
    # provenance/advisory labelling preserved on the degraded path
    assert data["evidence_grade"] == "llm_advisory"
    assert data["disclaimer"].strip() != ""


def test_genomic_briefing_ai_status_degraded_on_ollama_failure(
    client, mock_ollama, actionable_briefing_payload
):
    """Genomic briefing reports ai_status='degraded' when the LLM fails."""
    mock_ollama.side_effect = httpx.ConnectError("connection refused")

    response = client.post(
        "/api/ai/decision-support/genomic-briefing",
        json=actionable_briefing_payload,
    )

    assert response.status_code == 200
    data = response.json()
    assert data["ai_status"] == "degraded"
    assert data["actionable_count"] == 1  # structured data still computed


def test_genomic_briefing_ai_status_ok_on_success(
    client, mock_ollama, actionable_briefing_payload
):
    """Genomic briefing reports ai_status='ok' when the LLM succeeds."""
    mock_ollama.return_value.json.return_value = {
        "response": '{"briefing": "BRAF V600E, Level 1A."}'
    }

    response = client.post(
        "/api/ai/decision-support/genomic-briefing",
        json=actionable_briefing_payload,
    )

    assert response.status_code == 200
    data = response.json()
    assert data["ai_status"] == "ok"


def test_prognosis_endpoint_response_is_advisory(client, mock_ollama):
    """The prognosis endpoint emits the advisory grade despite rule-based scores.

    ECOG/Charlson are computed deterministically, but the response can also
    contain an LLM risk-stratification fallback, so the whole payload is graded
    advisory.
    """
    mock_ollama.return_value.json.return_value = {
        "response": (
            '{"score_name": "Risk Stratification Assessment", "value": 5, '
            '"interpretation": "moderate risk", "category": "intermediate", '
            '"components": {}}'
        )
    }

    response = client.post(
        "/api/ai/decision-support/prognosis",
        json={"patient_data": {"ecog": 1, "conditions": ["diabetes"], "age": 60}},
    )

    assert response.status_code == 200
    data = response.json()
    assert data["evidence_grade"] == "llm_advisory"
    assert data["disclaimer"].strip() != ""
