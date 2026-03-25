"""Service-level tests for genomic briefing generation."""

from unittest.mock import AsyncMock, patch

import pytest

from app.models.decision_support import (
    DrugExposureSummary,
    GenomicBriefingRequest,
    InteractionSummary,
    VariantSummary,
)
from app.services.genomic_briefing import generate_briefing


@pytest.mark.asyncio
async def test_no_actionable_variants_returns_static_message():
    """VUS-only request returns static early-return message without LLM call."""
    request = GenomicBriefingRequest(
        patient_id=1,
        variants=[
            VariantSummary(gene="TP53", variant="R175H", classification="vus"),
        ],
        total_variant_count=1,
    )
    result = await generate_briefing(request)

    assert "No actionable" in result.briefing
    assert result.actionable_count == 0
    assert result.variant_count == 1
    assert result.generated_at != ""


@pytest.mark.asyncio
@patch("app.services.genomic_briefing.call_ollama_json", new_callable=AsyncMock)
async def test_actionable_variants_calls_llm(mock_llm):
    """Pathogenic variant triggers LLM call and returns generated briefing."""
    mock_llm.return_value = {"briefing": "Test narrative about BRAF"}

    request = GenomicBriefingRequest(
        patient_id=1,
        variants=[
            VariantSummary(
                gene="BRAF",
                variant="V600E",
                classification="pathogenic",
                evidence_level="1A",
                therapies=["vemurafenib"],
            ),
        ],
        total_variant_count=5,
    )
    result = await generate_briefing(request)

    assert result.briefing == "Test narrative about BRAF"
    assert result.actionable_count == 1
    mock_llm.assert_called_once()


@pytest.mark.asyncio
@patch("app.services.genomic_briefing.call_ollama_json", new_callable=AsyncMock)
async def test_prompt_includes_variant_data(mock_llm):
    """Prompt sent to LLM contains variant gene, mutation, and therapy info."""
    mock_llm.return_value = {"briefing": "narrative"}

    request = GenomicBriefingRequest(
        patient_id=1,
        variants=[
            VariantSummary(
                gene="BRAF",
                variant="V600E",
                classification="pathogenic",
                evidence_level="1A",
                therapies=["vemurafenib"],
            ),
        ],
        total_variant_count=5,
    )
    await generate_briefing(request)

    prompt = mock_llm.call_args[0][0]
    assert "BRAF" in prompt
    assert "V600E" in prompt
    assert "vemurafenib" in prompt


@pytest.mark.asyncio
@patch("app.services.genomic_briefing.call_ollama_json", new_callable=AsyncMock)
async def test_prompt_includes_drug_exposures(mock_llm):
    """Prompt includes drug exposure information."""
    mock_llm.return_value = {"briefing": "narrative"}

    request = GenomicBriefingRequest(
        patient_id=1,
        variants=[
            VariantSummary(
                gene="BRAF",
                variant="V600E",
                classification="pathogenic",
            ),
        ],
        drug_exposures=[
            DrugExposureSummary(
                drug_name="carboplatin",
                start_date="2025-01-01",
            ),
        ],
        total_variant_count=3,
    )
    await generate_briefing(request)

    prompt = mock_llm.call_args[0][0]
    assert "carboplatin" in prompt


@pytest.mark.asyncio
@patch("app.services.genomic_briefing.call_ollama_json", new_callable=AsyncMock)
async def test_prompt_includes_interactions(mock_llm):
    """Prompt includes gene-drug interaction data."""
    mock_llm.return_value = {"briefing": "narrative"}

    request = GenomicBriefingRequest(
        patient_id=1,
        variants=[
            VariantSummary(
                gene="BRAF",
                variant="V600E",
                classification="pathogenic",
            ),
        ],
        interactions=[
            InteractionSummary(
                gene="BRAF",
                drug="vemurafenib",
                relationship="sensitivity",
                evidence_level="1A",
            ),
        ],
        total_variant_count=3,
    )
    await generate_briefing(request)

    prompt = mock_llm.call_args[0][0]
    assert "sensitivity" in prompt


@pytest.mark.asyncio
@patch("app.services.genomic_briefing.call_ollama_json", new_callable=AsyncMock)
async def test_llm_failure_returns_error_text(mock_llm):
    """LLM exception is caught and returned as error text in briefing."""
    mock_llm.side_effect = Exception("connection refused")

    request = GenomicBriefingRequest(
        patient_id=1,
        variants=[
            VariantSummary(
                gene="BRAF",
                variant="V600E",
                classification="pathogenic",
            ),
        ],
        total_variant_count=3,
    )
    result = await generate_briefing(request)

    assert "failed" in result.briefing.lower() or "exception" in result.briefing.lower()
