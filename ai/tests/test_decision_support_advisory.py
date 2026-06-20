"""Tests asserting LLM decision-support responses are labelled advisory.

The decision_support endpoints are Ollama LLM reasoning with no backing
knowledge base. Every response model must carry an explicit advisory evidence
grade and a non-empty disclaimer so consumers never mistake them for
database-verified clinical decision support.
"""

import pytest

from app.models.decision_support import (
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
