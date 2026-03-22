"""Segmentation service for imaging AI pipeline.

Provides mock segmentation results with plausible clinical structures.
Real DICOM segmentation requires specialized models (TotalSegmentator, nnU-Net, etc.).
"""

import json
import uuid
from typing import Any

import httpx

from app.config import settings

# Body-site to expected structures mapping
BODY_SITE_STRUCTURES: dict[str, list[dict[str, Any]]] = {
    "chest": [
        {"name": "Left Lung", "volume_cm3": 2200.0, "confidence": 0.95},
        {"name": "Right Lung", "volume_cm3": 2600.0, "confidence": 0.96},
        {"name": "Heart", "volume_cm3": 680.0, "confidence": 0.94},
        {"name": "Mediastinum", "volume_cm3": 320.0, "confidence": 0.88},
        {"name": "Aorta", "volume_cm3": 110.0, "confidence": 0.91},
        {"name": "Trachea", "volume_cm3": 35.0, "confidence": 0.93},
    ],
    "abdomen": [
        {"name": "Liver", "volume_cm3": 1500.0, "confidence": 0.94},
        {"name": "Spleen", "volume_cm3": 200.0, "confidence": 0.92},
        {"name": "Left Kidney", "volume_cm3": 150.0, "confidence": 0.93},
        {"name": "Right Kidney", "volume_cm3": 155.0, "confidence": 0.93},
        {"name": "Pancreas", "volume_cm3": 70.0, "confidence": 0.85},
        {"name": "Stomach", "volume_cm3": 300.0, "confidence": 0.87},
    ],
    "head": [
        {"name": "Brain", "volume_cm3": 1400.0, "confidence": 0.96},
        {"name": "Left Lateral Ventricle", "volume_cm3": 12.0, "confidence": 0.89},
        {"name": "Right Lateral Ventricle", "volume_cm3": 12.5, "confidence": 0.89},
        {"name": "Cerebellum", "volume_cm3": 150.0, "confidence": 0.93},
        {"name": "Brainstem", "volume_cm3": 25.0, "confidence": 0.91},
    ],
    "pelvis": [
        {"name": "Bladder", "volume_cm3": 350.0, "confidence": 0.92},
        {"name": "Rectum", "volume_cm3": 60.0, "confidence": 0.86},
        {"name": "Left Femoral Head", "volume_cm3": 75.0, "confidence": 0.94},
        {"name": "Right Femoral Head", "volume_cm3": 76.0, "confidence": 0.94},
    ],
}

DEFAULT_STRUCTURES = [
    {"name": "Soft Tissue", "volume_cm3": 500.0, "confidence": 0.80},
    {"name": "Bone", "volume_cm3": 250.0, "confidence": 0.85},
]

IMAGING_ANALYSIS_PROMPT = """You are a radiology AI assistant analyzing an imaging study segmentation.

Study details:
- Body site: {body_site}
- Algorithm: {algorithm}
- Structures detected: {structures}

Provide a brief clinical summary of the segmentation findings, noting any structures with
volumes that appear outside normal ranges. Respond in JSON format:
{{
  "summary": "brief clinical summary",
  "notable_findings": ["list of notable findings"],
  "quality_assessment": "good/fair/poor"
}}
"""


async def run_segmentation(
    study_id: int,
    body_site: str,
    algorithm: str | None = None,
) -> dict[str, Any]:
    """Run mock segmentation on a study, returning detected structures and volumes.

    In production this would invoke TotalSegmentator, nnU-Net, or similar.
    """
    effective_algorithm = algorithm or "TotalSegmentator-v2"
    segmentation_id = str(uuid.uuid4())

    site_key = body_site.lower().strip()
    structures = []
    for s in BODY_SITE_STRUCTURES.get(site_key, DEFAULT_STRUCTURES):
        structures.append({
            "name": s["name"],
            "volume_cm3": s["volume_cm3"],
            "confidence": s["confidence"],
        })

    # Attempt AI analysis via Ollama
    ai_analysis = None
    try:
        prompt = IMAGING_ANALYSIS_PROMPT.format(
            body_site=body_site,
            algorithm=effective_algorithm,
            structures=json.dumps(structures),
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
            "summary": f"Segmentation completed for {body_site} using {effective_algorithm}",
            "notable_findings": [],
            "quality_assessment": "good",
        }

    return {
        "segmentation_id": segmentation_id,
        "study_id": study_id,
        "body_site": body_site,
        "algorithm": effective_algorithm,
        "structures": structures,
        "structure_count": len(structures),
        "ai_analysis": ai_analysis,
    }
