"""Rare disease matcher service — phenotype-based rare disease matching via LLM."""

import logging

from app.models.decision_support import RareDiseaseMatch
from app.services.llm_utils import call_ollama_json

logger = logging.getLogger(__name__)

SYSTEM_PROMPT = (
    "You are a clinical genetics and rare disease expert. Given a list of patient "
    "symptoms and phenotypic features, suggest possible rare disease diagnoses. "
    "Rank by likelihood, note matching and distinguishing features, and recommend "
    "confirmatory workup including genetic testing. Use OMIM IDs where possible."
)


async def match_phenotype(
    symptoms: list[str],
    patient_context: dict | None = None,
) -> list[RareDiseaseMatch]:
    """Match patient phenotype to possible rare diseases.

    Args:
        symptoms: List of observed symptoms/phenotypic features.
        patient_context: Optional dict with age, sex, family history, etc.

    Returns:
        Ranked list of rare disease matches.

    Raises:
        Exception: Propagated from Ollama if service is unavailable.
    """
    symptom_list = ", ".join(symptoms)
    context_str = ""
    if patient_context:
        context_str = "\nAdditional context:\n" + "\n".join(
            f"{k}: {v}" for k, v in patient_context.items()
        )

    prompt = f"""Given these patient symptoms/phenotypic features, suggest up to 5 possible rare disease diagnoses ranked by likelihood.

Symptoms: {symptom_list}{context_str}

Respond in JSON with this exact structure:
{{
  "matches": [
    {{
      "disease_name": "disease name",
      "omim_id": "OMIM ID or null if unknown",
      "confidence": "high or medium or low",
      "matching_features": ["symptom that matches"],
      "distinguishing_features": ["expected feature not observed"],
      "recommended_workup": ["test to confirm or rule out"],
      "genetic_testing": ["gene to test"]
    }}
  ]
}}"""

    data = await call_ollama_json(prompt, system=SYSTEM_PROMPT)

    raw_matches = data.get("matches", [])
    matches: list[RareDiseaseMatch] = []
    for item in raw_matches:
        try:
            confidence = str(item.get("confidence", "low")).lower()
            if confidence not in ("high", "medium", "low"):
                confidence = "low"

            omim_id = item.get("omim_id")
            if omim_id is not None:
                omim_id = str(omim_id)
                # Treat null-like strings as None
                if omim_id.lower() in ("null", "none", "unknown", ""):
                    omim_id = None

            matches.append(
                RareDiseaseMatch(
                    disease_name=str(item.get("disease_name", "Unknown")),
                    omim_id=omim_id,
                    confidence=confidence,
                    matching_features=[
                        str(f) for f in item.get("matching_features", [])
                    ],
                    distinguishing_features=[
                        str(f) for f in item.get("distinguishing_features", [])
                    ],
                    recommended_workup=[
                        str(t) for t in item.get("recommended_workup", [])
                    ],
                    genetic_testing=[
                        str(g) for g in item.get("genetic_testing", [])
                    ],
                )
            )
        except (ValueError, TypeError) as exc:
            logger.warning("Skipping malformed rare disease match: %s", exc)

    return matches
