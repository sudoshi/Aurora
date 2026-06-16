"""Tests for BioMcpService — evidence retrieval is degrade-safe and normalized.

Run focused: `venv/bin/python -m pytest tests/test_biomcp_service.py --no-cov -v`
(the repo's pytest.ini enforces --cov-fail-under on a fixed module list).
"""

import json

import pytest

import app.services.biomcp_service as mod
from app.services.biomcp_service import BioMcpService


def _articles_json(n: int = 1) -> str:
    return json.dumps(
        [
            {"pmid": 100 + i, "title": f"Article {i}", "pubmed_url": f"https://pubmed.ncbi.nlm.nih.gov/{100 + i}/"}
            for i in range(n)
        ]
    )


_TRIALS_JSON = json.dumps(
    [{"NCT Number": "NCT01", "Study Title": "A trial", "Study URL": "https://clinicaltrials.gov/study/NCT01"}]
)
_VARIANTS_JSON = json.dumps({"variants": [{"_id": "rs113488022", "hgvsp": "p.V600E"}]})


def _patch_all(monkeypatch, *, articles=None, trials=None, variants=None):
    async def fake_articles(*_a, **_k):
        return articles if articles is not None else _articles_json()

    async def fake_trials(*_a, **_k):
        return trials if trials is not None else _TRIALS_JSON

    async def fake_variants(*_a, **_k):
        return variants if variants is not None else _VARIANTS_JSON

    monkeypatch.setattr(mod, "search_articles", fake_articles)
    monkeypatch.setattr(mod, "search_trials", fake_trials)
    monkeypatch.setattr(mod, "search_variants", fake_variants)


@pytest.mark.asyncio
async def test_gather_normalizes_all_sources(monkeypatch):
    _patch_all(monkeypatch)
    result = await BioMcpService().gather(genes=["BRAF"], conditions=["Melanoma"], drugs=[])

    assert set(result) == {"articles", "trials", "variants"}
    art = result["articles"][0]
    assert art["type"] == "article" and art["id"] == "PMID:100"
    assert art["url"].startswith("https://pubmed.ncbi.nlm.nih.gov/")
    trial = result["trials"][0]
    assert trial["type"] == "trial" and trial["id"] == "NCT01" and trial["url"].endswith("NCT01")
    var = result["variants"][0]
    assert var["type"] == "variant" and "BRAF" in var["title"]


@pytest.mark.asyncio
async def test_failing_source_degrades_to_empty_without_raising(monkeypatch):
    async def boom(*_a, **_k):
        raise RuntimeError("upstream down")

    _patch_all(monkeypatch)
    monkeypatch.setattr(mod, "search_trials", boom)

    result = await BioMcpService().gather(genes=["BRAF"], conditions=["Melanoma"], drugs=[])
    assert result["trials"] == []
    assert result["articles"] and result["variants"]  # other sources unaffected


@pytest.mark.asyncio
async def test_disabled_returns_empty_and_calls_nothing(monkeypatch):
    called = {"n": 0}

    async def tracker(*_a, **_k):
        called["n"] += 1
        return _articles_json()

    monkeypatch.setattr(mod, "search_articles", tracker)
    monkeypatch.setattr(mod, "search_trials", tracker)
    monkeypatch.setattr(mod, "search_variants", tracker)
    monkeypatch.setattr(mod.settings, "biomcp_enabled", False)

    result = await BioMcpService().gather(genes=["BRAF"], conditions=["Melanoma"], drugs=[])
    assert result == {"articles": [], "trials": [], "variants": []}
    assert called["n"] == 0


@pytest.mark.asyncio
async def test_respects_max_per_source(monkeypatch):
    _patch_all(monkeypatch, articles=_articles_json(10))
    result = await BioMcpService().gather(
        genes=["BRAF"], conditions=["Melanoma"], drugs=[], max_per_source=3
    )
    assert len(result["articles"]) == 3
