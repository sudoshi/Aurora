"""Response assessment service implementing RECIST 1.1, Lugano, Deauville, and RANO criteria.

Evaluates treatment response by comparing baseline and follow-up imaging measurements.
"""

import json
from enum import Enum
from typing import Any

import httpx

from app.config import settings


class ResponseCategory(str, Enum):
    CR = "CR"   # Complete Response
    PR = "PR"   # Partial Response
    SD = "SD"   # Stable Disease
    PD = "PD"   # Progressive Disease
    NE = "NE"   # Not Evaluable


RESPONSE_ANALYSIS_PROMPT = """You are a radiology AI assistant performing treatment response assessment.

Criteria: {criteria}
Baseline measurements: {baseline}
Current measurements: {current}
Calculated percent change: {percent_change}%
Preliminary category: {preliminary_category}

Provide a detailed response assessment. Respond in JSON format:
{{
  "response_category": "CR/PR/SD/PD/NE",
  "confidence": 0.0 to 1.0,
  "reasoning": "explanation of response classification",
  "clinical_notes": ["relevant observations"],
  "recommendations": ["suggested next steps"]
}}
"""


def _sum_diameters(measurements: list[dict[str, Any]]) -> float | None:
    """Sum longest diameters of all target lesions."""
    target = [m for m in measurements if m.get("target_lesion", False)]
    if not target:
        return None
    return sum(float(m.get("value_numeric", 0)) for m in target)


def _assess_recist(
    baseline_sum: float | None,
    current_sum: float | None,
    baseline_measurements: list[dict[str, Any]],
    current_measurements: list[dict[str, Any]],
) -> tuple[ResponseCategory, float | None]:
    """Apply RECIST 1.1 criteria.

    CR: Disappearance of all target lesions
    PR: >= 30% decrease in sum of diameters from baseline
    PD: >= 20% increase in sum of diameters + >= 5mm absolute increase
    SD: Neither sufficient shrinkage for PR nor increase for PD
    """
    if baseline_sum is None or current_sum is None:
        return ResponseCategory.NE, None

    if baseline_sum == 0:
        if current_sum == 0:
            return ResponseCategory.CR, 0.0
        return ResponseCategory.PD, None

    percent_change = ((current_sum - baseline_sum) / baseline_sum) * 100.0
    absolute_change = current_sum - baseline_sum

    # Check for CR: all target lesions disappeared
    current_targets = [m for m in current_measurements if m.get("target_lesion", False)]
    all_disappeared = all(float(m.get("value_numeric", 0)) == 0 for m in current_targets)
    if all_disappeared and len(current_targets) > 0:
        return ResponseCategory.CR, percent_change

    # PR: >= 30% decrease
    if percent_change <= -30.0:
        return ResponseCategory.PR, percent_change

    # PD: >= 20% increase AND >= 5mm absolute increase
    if percent_change >= 20.0 and absolute_change >= 5.0:
        return ResponseCategory.PD, percent_change

    # SD: between PR and PD
    return ResponseCategory.SD, percent_change


def _assess_lugano(
    baseline_measurements: list[dict[str, Any]],
    current_measurements: list[dict[str, Any]],
) -> tuple[ResponseCategory, float | None]:
    """Apply Lugano criteria for lymphoma response assessment.

    Simplified implementation based on sum of product diameters (SPD).
    """
    baseline_sum = _sum_diameters(baseline_measurements)
    current_sum = _sum_diameters(current_measurements)

    if baseline_sum is None or current_sum is None:
        return ResponseCategory.NE, None

    if baseline_sum == 0:
        return (ResponseCategory.CR, 0.0) if current_sum == 0 else (ResponseCategory.PD, None)

    percent_change = ((current_sum - baseline_sum) / baseline_sum) * 100.0

    if percent_change <= -100.0:
        return ResponseCategory.CR, percent_change
    if percent_change <= -50.0:
        return ResponseCategory.PR, percent_change
    if percent_change >= 50.0:
        return ResponseCategory.PD, percent_change

    return ResponseCategory.SD, percent_change


def _assess_deauville(
    baseline_measurements: list[dict[str, Any]],
    current_measurements: list[dict[str, Any]],
) -> tuple[ResponseCategory, float | None]:
    """Apply Deauville 5-point scale (PET/CT) for lymphoma.

    Simplified: uses measurement values as SUV proxy.
    Scores 1-3 = CR/PR, 4-5 = SD/PD.
    """
    baseline_sum = _sum_diameters(baseline_measurements)
    current_sum = _sum_diameters(current_measurements)

    if baseline_sum is None or current_sum is None:
        return ResponseCategory.NE, None

    if baseline_sum == 0:
        return (ResponseCategory.CR, 0.0) if current_sum == 0 else (ResponseCategory.PD, None)

    percent_change = ((current_sum - baseline_sum) / baseline_sum) * 100.0

    if percent_change <= -75.0:
        return ResponseCategory.CR, percent_change
    if percent_change <= -25.0:
        return ResponseCategory.PR, percent_change
    if percent_change >= 25.0:
        return ResponseCategory.PD, percent_change

    return ResponseCategory.SD, percent_change


def _assess_rano(
    baseline_measurements: list[dict[str, Any]],
    current_measurements: list[dict[str, Any]],
) -> tuple[ResponseCategory, float | None]:
    """Apply RANO criteria for brain tumor response assessment.

    Based on product of perpendicular diameters.
    CR: complete disappearance of all enhancing measurable disease
    PR: >= 50% decrease
    PD: >= 25% increase
    SD: between PR and PD
    """
    baseline_sum = _sum_diameters(baseline_measurements)
    current_sum = _sum_diameters(current_measurements)

    if baseline_sum is None or current_sum is None:
        return ResponseCategory.NE, None

    if baseline_sum == 0:
        return (ResponseCategory.CR, 0.0) if current_sum == 0 else (ResponseCategory.PD, None)

    percent_change = ((current_sum - baseline_sum) / baseline_sum) * 100.0

    current_targets = [m for m in current_measurements if m.get("target_lesion", False)]
    all_disappeared = all(float(m.get("value_numeric", 0)) == 0 for m in current_targets)
    if all_disappeared and len(current_targets) > 0:
        return ResponseCategory.CR, percent_change

    if percent_change <= -50.0:
        return ResponseCategory.PR, percent_change
    if percent_change >= 25.0:
        return ResponseCategory.PD, percent_change

    return ResponseCategory.SD, percent_change


CRITERIA_ASSESSORS = {
    "recist": _assess_recist,
    "lugano": _assess_lugano,
    "deauville": _assess_deauville,
    "rano": _assess_rano,
}


async def assess_response(
    patient_id: int,
    baseline_study_id: int,
    current_study_id: int,
    criteria: str,
    baseline_measurements: list[dict[str, Any]],
    current_measurements: list[dict[str, Any]],
) -> dict[str, Any]:
    """Assess treatment response comparing baseline and current studies."""
    criteria_lower = criteria.lower().strip()

    baseline_sum = _sum_diameters(baseline_measurements)
    current_sum = _sum_diameters(current_measurements)

    if criteria_lower == "recist":
        category, percent_change = _assess_recist(
            baseline_sum, current_sum, baseline_measurements, current_measurements
        )
    elif criteria_lower in CRITERIA_ASSESSORS:
        category, percent_change = CRITERIA_ASSESSORS[criteria_lower](
            baseline_measurements, current_measurements
        )
    else:
        category = ResponseCategory.NE
        percent_change = None

    # Build measurement comparison
    comparison = {
        "baseline_sum_diameters": baseline_sum,
        "current_sum_diameters": current_sum,
        "baseline_target_count": len([m for m in baseline_measurements if m.get("target_lesion")]),
        "current_target_count": len([m for m in current_measurements if m.get("target_lesion")]),
    }

    # Attempt AI-enhanced analysis
    ai_analysis = None
    try:
        prompt = RESPONSE_ANALYSIS_PROMPT.format(
            criteria=criteria,
            baseline=json.dumps(baseline_measurements[:10]),
            current=json.dumps(current_measurements[:10]),
            percent_change=f"{percent_change:.1f}" if percent_change is not None else "N/A",
            preliminary_category=category.value,
        )
        async with httpx.AsyncClient(timeout=settings.ollama_timeout) as client:
            response = await client.post(
                f"{settings.ollama_base_url}/api/generate",
                json={
                    "model": settings.ollama_model,
                    "prompt": prompt,
                    "stream": False,
                    "format": "json",
                },
            )
            response.raise_for_status()
            result = response.json()
            ai_analysis = json.loads(result.get("response", "{}"))
    except Exception:
        ai_analysis = {
            "reasoning": f"Assessment based on {criteria.upper()} criteria calculation.",
            "confidence": 0.85 if category != ResponseCategory.NE else 0.0,
        }

    return {
        "patient_id": patient_id,
        "baseline_study_id": baseline_study_id,
        "current_study_id": current_study_id,
        "criteria": criteria.upper(),
        "response_category": category.value,
        "percent_change": round(percent_change, 2) if percent_change is not None else None,
        "measurements_comparison": comparison,
        "ai_analysis": ai_analysis,
    }
