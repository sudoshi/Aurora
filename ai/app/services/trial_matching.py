"""Trial matching service — matches patients to clinical trials via LLM reasoning."""

import logging

from app.models.decision_support import TrialMatchRequest, TrialSuggestion
from app.services.llm_utils import call_ollama_json

logger = logging.getLogger(__name__)

SYSTEM_PROMPT = (
    "You are an oncology clinical trial matching specialist. "
    "Given a patient profile, suggest relevant clinical trial types the patient "
    "may be eligible for. Base your reasoning on standard eligibility criteria used "
    "in cancer clinical trials. Be specific and evidence-based."
)


def _build_patient_profile(request: TrialMatchRequest) -> str:
    """Build a structured patient eligibility profile string."""
    lines: list[str] = []
    if request.diagnosis:
        lines.append(f"Diagnosis: {request.diagnosis}")
    if request.condition_focus:
        lines.append(f"Condition focus: {request.condition_focus}")
    if request.stage:
        lines.append(f"Stage: {request.stage}")
    if request.age is not None:
        lines.append(f"Age: {request.age}")
    if request.sex:
        lines.append(f"Sex: {request.sex}")
    if request.prior_treatments:
        lines.append(f"Prior treatments: {', '.join(request.prior_treatments)}")
    if request.biomarkers:
        marker_strs = [f"{k}: {v}" for k, v in request.biomarkers.items()]
        lines.append(f"Biomarkers: {'; '.join(marker_strs)}")
    return "\n".join(lines) if lines else "No patient data provided."


async def match_trials(
    request: TrialMatchRequest,
) -> list[TrialSuggestion]:
    """Match a patient to potential clinical trial types.

    Args:
        request: Patient profile data for trial matching.

    Returns:
        List of trial suggestions with rationale.

    Raises:
        Exception: Propagated from Ollama call if service is unavailable.
    """
    profile = _build_patient_profile(request)

    prompt = f"""Given this patient profile, suggest up to 5 relevant clinical trial types.

Patient Profile:
{profile}

Respond in JSON with this exact structure:
{{
  "suggestions": [
    {{
      "trial_type": "e.g., Phase III Immunotherapy + Chemotherapy",
      "rationale": "why this patient may qualify",
      "key_criteria_met": ["criterion 1", "criterion 2"],
      "potential_exclusions": ["concern 1"],
      "confidence": "high or medium or low"
    }}
  ]
}}"""

    data = await call_ollama_json(prompt, system=SYSTEM_PROMPT)

    raw_suggestions = data.get("suggestions", [])
    suggestions: list[TrialSuggestion] = []
    for item in raw_suggestions:
        try:
            confidence = str(item.get("confidence", "low")).lower()
            if confidence not in ("high", "medium", "low"):
                confidence = "low"
            suggestions.append(
                TrialSuggestion(
                    trial_type=str(item.get("trial_type", "Unknown")),
                    rationale=str(item.get("rationale", "")),
                    key_criteria_met=[
                        str(c) for c in item.get("key_criteria_met", [])
                    ],
                    potential_exclusions=[
                        str(e) for e in item.get("potential_exclusions", [])
                    ],
                    confidence=confidence,
                )
            )
        except (ValueError, TypeError) as exc:
            logger.warning("Skipping malformed trial suggestion: %s", exc)

    return suggestions
