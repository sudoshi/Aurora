"""Drug interaction checker service — identifies drug-drug interactions via LLM."""

import logging

from app.models.decision_support import DrugInteraction
from app.services.llm_utils import call_ollama_json

logger = logging.getLogger(__name__)

SYSTEM_PROMPT = (
    "You are a clinical pharmacology expert specializing in drug-drug interactions. "
    "Given a list of medications, identify clinically significant interactions. "
    "Focus on interactions with major or moderate severity. "
    "Provide mechanism, clinical significance, and management recommendations."
)


async def check_interactions(
    medications: list[str],
    proposed_medication: str | None = None,
) -> list[DrugInteraction]:
    """Check for drug-drug interactions among a medication list.

    Args:
        medications: List of current medications.
        proposed_medication: Optional new medication being considered.

    Returns:
        List of identified drug interactions.

    Raises:
        Exception: Propagated from Ollama if service is unavailable.
    """
    med_list = ", ".join(medications)
    proposed_str = (
        f"\nProposed new medication to add: {proposed_medication}"
        if proposed_medication
        else ""
    )

    prompt = f"""Identify clinically significant drug-drug interactions.

Current medications: {med_list}{proposed_str}

Respond in JSON with this exact structure:
{{
  "interactions": [
    {{
      "drug_a": "medication name",
      "drug_b": "medication name",
      "severity": "major or moderate or minor",
      "mechanism": "pharmacological mechanism",
      "clinical_significance": "what this means clinically",
      "recommendation": "how to manage this interaction"
    }}
  ]
}}"""

    data = await call_ollama_json(prompt, system=SYSTEM_PROMPT)

    raw_interactions = data.get("interactions", [])
    interactions: list[DrugInteraction] = []
    for item in raw_interactions:
        try:
            severity = str(item.get("severity", "moderate")).lower()
            if severity not in ("major", "moderate", "minor"):
                severity = "moderate"
            interactions.append(
                DrugInteraction(
                    drug_a=str(item.get("drug_a", "")),
                    drug_b=str(item.get("drug_b", "")),
                    severity=severity,
                    mechanism=str(item.get("mechanism", "")),
                    clinical_significance=str(
                        item.get("clinical_significance", "")
                    ),
                    recommendation=str(item.get("recommendation", "")),
                )
            )
        except (ValueError, TypeError) as exc:
            logger.warning("Skipping malformed drug interaction: %s", exc)

    return interactions
