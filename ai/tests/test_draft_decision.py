"""Tests for POST /api/ai/abby/draft-decision.

Run focused: `python -m pytest tests/test_draft_decision.py --no-cov -v`
"""

import json
from types import SimpleNamespace

import pytest
from fastapi.testclient import TestClient

import app.routers.decisions as mod
from app.config import settings
from app.main import app

client = TestClient(app)

_SNAPSHOT = {
    "patient": {
        "first_name": "Jane",
        "last_name": "Doe",
        "date_of_birth": "1970-05-01",
        "gender": "female",
    },
    "conditions": [{"name": "Melanoma", "status": "active"}],
    "medications": [{"name": "Dabrafenib", "dosage": "150mg"}],
    "measurements": [{"name": "LDH", "value": 250, "unit": "U/L"}],
    "observations": [],
}
_EVIDENCE = {
    "articles": [
        {"type": "article", "id": "PMID:100", "title": "BRAF in melanoma", "url": "https://pubmed.ncbi.nlm.nih.gov/100/"}
    ],
    "trials": [
        {"type": "trial", "id": "NCT01", "title": "A melanoma trial", "url": "https://clinicaltrials.gov/study/NCT01"}
    ],
    "variants": [],
}
_DRAFT_JSON = json.dumps(
    {
        "decision_type": "treatment_recommendation",
        "recommendation": "Start combination BRAF/MEK inhibitor therapy.",
        "rationale": "Per PMID:100 and NCT01, targeted therapy is indicated.",
        "confidence": 0.82,
        "guideline_references": ["NCCN Melanoma v2026"],
    }
)


class _FakeBioMcp:
    async def gather(self, genes, conditions, drugs, max_per_source=5):
        return _EVIDENCE


class _FakeClaude:
    last: dict = {}

    def __init__(self, *, api_key, **_kw):
        assert api_key  # constructed only when configured

    def chat(self, *, system_prompt, message, history=None):
        _FakeClaude.last = {"system_prompt": system_prompt, "message": message}
        return SimpleNamespace(reply=_DRAFT_JSON)


@pytest.fixture(autouse=True)
def _patch(monkeypatch):
    monkeypatch.setattr(mod, "_fetch_clinical_context", lambda pid: _SNAPSHOT)
    monkeypatch.setattr(mod, "_fetch_patient_genes", lambda pid: ["BRAF"])
    monkeypatch.setattr(mod, "BioMcpService", _FakeBioMcp)
    monkeypatch.setattr(mod, "ClaudeClient", _FakeClaude)
    monkeypatch.setattr(settings, "claude_api_key", "test-key")


def test_returns_grounded_structured_draft():
    r = client.post("/api/ai/abby/draft-decision", json={"case_id": 1, "patient_id": 5})
    assert r.status_code == 200
    body = r.json()
    assert body["decision_type"] == "treatment_recommendation"
    assert body["recommendation"]
    assert body["confidence"] == 0.82
    assert body["guideline_references"] == ["NCCN Melanoma v2026"]
    # Sources are the REAL BioMCP evidence, not whatever the model emitted.
    assert {s["id"] for s in body["sources"]} == {"PMID:100", "NCT01"}
    assert body["evidence_counts"] == {"articles": 1, "trials": 1, "variants": 0}
    assert body["model"]


def test_no_phi_reaches_claude():
    client.post("/api/ai/abby/draft-decision", json={"case_id": 1, "patient_id": 5})
    sent = _FakeClaude.last["message"] + _FakeClaude.last["system_prompt"]
    assert "Jane" not in sent and "Doe" not in sent  # name never leaves the building
    assert "1970-05-01" not in sent  # raw DOB never sent (age is derived instead)
    assert "55" in sent or "56" in sent  # derived age IS sent (born 1970)


def test_502_when_claude_unconfigured(monkeypatch):
    monkeypatch.setattr(settings, "claude_api_key", "")
    r = client.post("/api/ai/abby/draft-decision", json={"case_id": 1, "patient_id": 5})
    assert r.status_code == 502
