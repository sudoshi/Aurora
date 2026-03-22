"""Guideline checker service — assesses concordance with clinical guidelines."""

import logging

from app.models.decision_support import ConcordanceResult
from app.services.llm_utils import call_ollama_json

logger = logging.getLogger(__name__)

SYSTEM_PROMPT = (
    "You are a clinical guidelines expert with deep knowledge of NCCN, ASCO, ESMO, "
    "and other major oncology and medical guidelines. Assess whether a proposed "
    "clinical recommendation aligns with current evidence-based guidelines. "
    "Be specific about which guideline you are referencing."
)


def _build_context_summary(patient_context: dict) -> str:
    """Summarize patient context dict into readable text."""
    if not patient_context:
        return "No additional patient context provided."
    lines = [f"{k}: {v}" for k, v in patient_context.items()]
    return "\n".join(lines)


async def check_concordance(
    recommendation: str,
    patient_context: dict,
    guideline: str | None = None,
) -> ConcordanceResult:
    """Check whether a recommendation aligns with clinical guidelines.

    Args:
        recommendation: The proposed clinical decision/recommendation.
        patient_context: Dict of relevant patient data.
        guideline: Optional specific guideline to check against.

    Returns:
        ConcordanceResult with assessment details.

    Raises:
        Exception: Propagated from Ollama if service is unavailable.
    """
    context_str = _build_context_summary(patient_context)
    guideline_instruction = (
        f"Specifically evaluate against: {guideline}"
        if guideline
        else "Reference the most relevant major guideline."
    )

    prompt = f"""Evaluate this clinical recommendation for guideline concordance.

Recommendation: {recommendation}

Patient Context:
{context_str}

{guideline_instruction}

Respond in JSON with this exact structure:
{{
  "concordant": true or false,
  "guideline_referenced": "e.g., NCCN Non-Small Cell Lung Cancer v4.2026",
  "supporting_evidence": ["reason 1", "reason 2"],
  "concerns": ["concern 1"],
  "alternative_recommendations": ["alternative 1"],
  "confidence": "high or medium or low"
}}"""

    data = await call_ollama_json(prompt, system=SYSTEM_PROMPT)

    confidence = str(data.get("confidence", "low")).lower()
    if confidence not in ("high", "medium", "low"):
        confidence = "low"

    return ConcordanceResult(
        concordant=bool(data.get("concordant", False)),
        guideline_referenced=str(
            data.get("guideline_referenced", "Unable to determine")
        ),
        supporting_evidence=[
            str(e) for e in data.get("supporting_evidence", [])
        ],
        concerns=[str(c) for c in data.get("concerns", [])],
        alternative_recommendations=[
            str(a) for a in data.get("alternative_recommendations", [])
        ],
        confidence=confidence,
    )
