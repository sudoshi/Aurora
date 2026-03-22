"""Volumetric measurement service for imaging AI pipeline.

Computes volume, diameter measurements, and derived metrics from imaging data.
"""

import json
from typing import Any

import httpx

from app.config import settings

VOLUME_ANALYSIS_PROMPT = """You are a radiology AI assistant analyzing volumetric measurements.

Measurement type: {measurement_type}
Study ID: {study_id}
Measurements provided: {measurements}

Analyze these measurements and provide clinical context. Respond in JSON format:
{{
  "interpretation": "clinical interpretation of measurements",
  "volume_cm3": estimated total volume in cm3,
  "longest_diameter_mm": longest diameter in mm,
  "perpendicular_diameter_mm": perpendicular diameter in mm,
  "notes": ["relevant clinical notes"]
}}
"""


async def compute_volume(
    study_id: int,
    measurement_type: str,
    measurements: list[dict[str, Any]] | None = None,
) -> dict[str, Any]:
    """Compute volumetric measurements for a study.

    Uses existing measurement data if available, otherwise generates
    estimates via AI analysis.
    """
    effective_measurements = measurements or []

    # Derive volume from measurements if available
    volume_cm3 = None
    longest_diameter_mm = None
    perpendicular_diameter_mm = None

    for m in effective_measurements:
        val = float(m.get("value_numeric", 0))
        unit = m.get("unit", "mm")
        m_type = m.get("measurement_type", "")

        if "volume" in m_type.lower():
            volume_cm3 = val if unit == "cm3" else val / 1000.0
        elif "longest" in m_type.lower() or "diameter" in m_type.lower():
            if longest_diameter_mm is None:
                longest_diameter_mm = val if unit == "mm" else val * 10.0
            else:
                perpendicular_diameter_mm = val if unit == "mm" else val * 10.0

    # Attempt AI-enhanced volume estimation
    ai_interpretation = None
    try:
        prompt = VOLUME_ANALYSIS_PROMPT.format(
            measurement_type=measurement_type,
            study_id=study_id,
            measurements=json.dumps(effective_measurements),
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
            parsed = json.loads(result.get("response", "{}"))
            ai_interpretation = parsed.get("interpretation")

            if volume_cm3 is None:
                volume_cm3 = parsed.get("volume_cm3")
            if longest_diameter_mm is None:
                longest_diameter_mm = parsed.get("longest_diameter_mm")
            if perpendicular_diameter_mm is None:
                perpendicular_diameter_mm = parsed.get("perpendicular_diameter_mm")
    except Exception:
        ai_interpretation = "AI analysis unavailable; returning raw measurement data."

    return {
        "study_id": study_id,
        "measurement_type": measurement_type,
        "volume_cm3": volume_cm3,
        "longest_diameter_mm": longest_diameter_mm,
        "perpendicular_diameter_mm": perpendicular_diameter_mm,
        "measurement_count": len(effective_measurements),
        "interpretation": ai_interpretation,
    }
