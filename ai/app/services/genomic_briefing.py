"""Genomic briefing service — synthesizes a narrative from variant + therapy data."""

import logging
from datetime import datetime, timezone

from app.models.decision_support import (
    AI_STATUS_DEGRADED,
    AI_STATUS_OK,
    GenomicBriefingRequest,
    GenomicBriefingResponse,
)
from app.services.llm_utils import call_ollama_json

logger = logging.getLogger(__name__)

SYSTEM_PROMPT = (
    "You are a molecular oncology expert writing a clinical genomic briefing for a "
    "treating physician. Synthesize the provided variant data, therapy matches, and "
    "drug exposure history into a concise 3-5 sentence narrative. "
    "Lead with the most actionable finding. Include evidence levels (e.g., Level 1A). "
    "Mention current drug interactions if relevant. "
    "Be direct and clinical — this is for a physician making treatment decisions."
)


async def generate_briefing(request: GenomicBriefingRequest) -> GenomicBriefingResponse:
    """Generate a narrative genomic briefing from structured data."""
    actionable = [
        v
        for v in request.variants
        if v.classification in ("pathogenic", "likely_pathogenic")
    ]

    if not actionable:
        return GenomicBriefingResponse(
            briefing=(
                "No actionable genomic variants identified. "
                "All variants are classified as VUS or benign."
            ),
            generated_at=datetime.now(timezone.utc).isoformat(),
            variant_count=request.total_variant_count,
            actionable_count=0,
        )

    # Build structured context for the LLM
    variant_lines = []
    for v in actionable:
        therapies = ", ".join(v.therapies) if v.therapies else "none identified"
        variant_lines.append(
            f"- {v.gene} {v.variant} ({v.classification}, "
            f"{v.evidence_level or 'unknown level'}): therapies: {therapies}"
        )

    drug_lines = []
    for d in request.drug_exposures:
        period = f"{d.start_date or '?'} to {d.end_date or 'present'}"
        drug_lines.append(f"- {d.drug_name} ({period})")

    interaction_lines = []
    for i in request.interactions:
        interaction_lines.append(
            f"- {i.gene} + {i.drug}: {i.relationship} ({i.evidence_level})"
            f" — {i.mechanism or 'mechanism unknown'}"
        )

    prompt = (
        "Write a clinical genomic briefing (3-5 sentences) for this patient.\n\n"
        f"Total variants: {request.total_variant_count}\n"
        f"Actionable variants: {len(actionable)}\n\n"
        "ACTIONABLE VARIANTS:\n"
        + "\n".join(variant_lines)
        + "\n\nCURRENT/RECENT DRUG EXPOSURES:\n"
        + ("\n".join(drug_lines) if drug_lines else "None recorded")
        + "\n\nGENE-DRUG INTERACTIONS:\n"
        + ("\n".join(interaction_lines) if interaction_lines else "None identified")
        + '\n\nRespond in JSON:\n{"briefing": "your 3-5 sentence clinical narrative here"}'
    )

    ai_status = AI_STATUS_OK
    try:
        data = await call_ollama_json(prompt, system=SYSTEM_PROMPT)
        briefing_text = str(data.get("briefing", "Unable to generate briefing."))
    except Exception as e:
        logger.error("Genomic briefing generation failed: %s", e)
        briefing_text = f"Briefing generation failed: {type(e).__name__}"
        ai_status = AI_STATUS_DEGRADED

    return GenomicBriefingResponse(
        briefing=briefing_text,
        generated_at=datetime.now(timezone.utc).isoformat(),
        variant_count=request.total_variant_count,
        actionable_count=len(actionable),
        ai_status=ai_status,
    )
