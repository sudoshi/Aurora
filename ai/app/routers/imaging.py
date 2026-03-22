"""Imaging AI router for segmentation, volumetrics, response assessment, and feature extraction."""

import json
from typing import Any

from fastapi import APIRouter, HTTPException, Query
from pydantic import BaseModel, Field

import httpx

from app.config import settings
from app.services.segmentation_service import run_segmentation
from app.services.volumetric_service import compute_volume
from app.services.response_assessment import assess_response

router = APIRouter()


# ── Request / Response Models ────────────────────────────────────────────────


class SegmentRequest(BaseModel):
    study_id: int
    body_site: str = Field(..., min_length=1, max_length=100)
    algorithm: str | None = None


class StructureResult(BaseModel):
    name: str
    volume_cm3: float
    confidence: float


class SegmentResponse(BaseModel):
    segmentation_id: str
    study_id: int
    body_site: str
    algorithm: str
    structures: list[StructureResult]
    structure_count: int
    ai_analysis: dict[str, Any] | None = None


class VolumeRequest(BaseModel):
    study_id: int
    measurement_type: str = Field(..., pattern="^(tumor_volume|organ_volume)$")


class VolumeResponse(BaseModel):
    study_id: int
    measurement_type: str
    volume_cm3: float | None = None
    longest_diameter_mm: float | None = None
    perpendicular_diameter_mm: float | None = None
    measurement_count: int
    interpretation: str | None = None


class ResponseRequest(BaseModel):
    patient_id: int
    baseline_study_id: int
    current_study_id: int
    criteria: str = Field(..., pattern="^(recist|lugano|deauville|rano)$")


class MeasurementComparison(BaseModel):
    baseline_sum_diameters: float | None = None
    current_sum_diameters: float | None = None
    baseline_target_count: int = 0
    current_target_count: int = 0


class ResponseResult(BaseModel):
    patient_id: int
    baseline_study_id: int
    current_study_id: int
    criteria: str
    response_category: str  # CR, PR, SD, PD, NE
    percent_change: float | None = None
    measurements_comparison: MeasurementComparison
    ai_analysis: dict[str, Any] | None = None


class TrendPoint(BaseModel):
    date: str
    value: float
    unit: str
    study_id: int


class TrendsResponse(BaseModel):
    patient_id: int
    measurement_type: str
    trends: list[TrendPoint]


class FeatureRequest(BaseModel):
    study_id: int


class ExtractedFeature(BaseModel):
    feature_name: str
    category: str
    value: str
    confidence: float


class FeatureResponse(BaseModel):
    study_id: int
    features: list[ExtractedFeature]
    feature_count: int


# ── Helper: Fetch measurements from Aurora backend DB via internal API ───────


async def _fetch_study_measurements(study_id: int) -> list[dict[str, Any]]:
    """Fetch imaging measurements for a study from the Aurora database.

    Queries the PostgreSQL database directly via asyncpg for performance.
    Falls back to empty list if unavailable.
    """
    try:
        import asyncpg

        conn = await asyncpg.connect(settings.database_url)
        try:
            rows = await conn.fetch(
                """
                SELECT measurement_type, target_lesion, value_numeric, unit,
                       measured_by, measured_at
                FROM clinical.imaging_measurements
                WHERE imaging_study_id = $1
                ORDER BY measured_at ASC
                """,
                study_id,
            )
            return [
                {
                    "measurement_type": row["measurement_type"],
                    "target_lesion": row["target_lesion"],
                    "value_numeric": float(row["value_numeric"]) if row["value_numeric"] else 0.0,
                    "unit": row["unit"],
                    "measured_by": row["measured_by"],
                    "measured_at": row["measured_at"].isoformat() if row["measured_at"] else None,
                }
                for row in rows
            ]
        finally:
            await conn.close()
    except Exception:
        return []


async def _fetch_patient_trends(
    patient_id: int,
    measurement_type: str | None = None,
) -> list[dict[str, Any]]:
    """Fetch longitudinal measurement data for a patient."""
    try:
        import asyncpg

        conn = await asyncpg.connect(settings.database_url)
        try:
            query = """
                SELECT im.value_numeric, im.unit, im.measured_at, im.measurement_type,
                       ist.id as study_id, ist.study_date
                FROM clinical.imaging_measurements im
                JOIN clinical.imaging_studies ist ON ist.id = im.imaging_study_id
                WHERE ist.patient_id = $1
            """
            params: list[Any] = [patient_id]

            if measurement_type:
                query += " AND im.measurement_type = $2"
                params.append(measurement_type)

            query += " ORDER BY COALESCE(im.measured_at, ist.study_date::timestamp) ASC"

            rows = await conn.fetch(query, *params)
            return [
                {
                    "date": (row["measured_at"] or row["study_date"]).isoformat() if (row["measured_at"] or row["study_date"]) else None,
                    "value": float(row["value_numeric"]) if row["value_numeric"] else 0.0,
                    "unit": row["unit"],
                    "study_id": row["study_id"],
                }
                for row in rows
                if (row["measured_at"] or row["study_date"]) is not None
            ]
        finally:
            await conn.close()
    except Exception:
        return []


# ── Endpoints ────────────────────────────────────────────────────────────────


@router.post("/imaging/segment", response_model=SegmentResponse)
async def segment_study(request: SegmentRequest) -> SegmentResponse:
    """Run segmentation on an imaging study.

    Returns detected structures with approximate volumes for the specified body site.
    Uses AI reasoning for clinical interpretation.
    """
    result = await run_segmentation(
        study_id=request.study_id,
        body_site=request.body_site,
        algorithm=request.algorithm,
    )

    return SegmentResponse(
        segmentation_id=result["segmentation_id"],
        study_id=result["study_id"],
        body_site=result["body_site"],
        algorithm=result["algorithm"],
        structures=[StructureResult(**s) for s in result["structures"]],
        structure_count=result["structure_count"],
        ai_analysis=result.get("ai_analysis"),
    )


@router.post("/imaging/volume", response_model=VolumeResponse)
async def compute_volume_endpoint(request: VolumeRequest) -> VolumeResponse:
    """Compute volumetric measurements for a study.

    Pulls existing measurements from the database and computes derived metrics.
    """
    measurements = await _fetch_study_measurements(request.study_id)

    result = await compute_volume(
        study_id=request.study_id,
        measurement_type=request.measurement_type,
        measurements=measurements,
    )

    return VolumeResponse(**result)


@router.post("/imaging/response", response_model=ResponseResult)
async def response_assessment(request: ResponseRequest) -> ResponseResult:
    """Assess treatment response by comparing baseline and current imaging studies.

    Supports RECIST 1.1, Lugano, Deauville, and RANO criteria.
    """
    baseline_measurements = await _fetch_study_measurements(request.baseline_study_id)
    current_measurements = await _fetch_study_measurements(request.current_study_id)

    result = await assess_response(
        patient_id=request.patient_id,
        baseline_study_id=request.baseline_study_id,
        current_study_id=request.current_study_id,
        criteria=request.criteria,
        baseline_measurements=baseline_measurements,
        current_measurements=current_measurements,
    )

    return ResponseResult(
        patient_id=result["patient_id"],
        baseline_study_id=result["baseline_study_id"],
        current_study_id=result["current_study_id"],
        criteria=result["criteria"],
        response_category=result["response_category"],
        percent_change=result["percent_change"],
        measurements_comparison=MeasurementComparison(**result["measurements_comparison"]),
        ai_analysis=result.get("ai_analysis"),
    )


@router.get("/imaging/trends/{patient_id}", response_model=TrendsResponse)
async def get_trends(
    patient_id: int,
    measurement_type: str | None = Query(default=None, description="Filter by measurement type"),
) -> TrendsResponse:
    """Get longitudinal measurement trends for a patient.

    Returns chronologically sorted measurements across all imaging studies.
    """
    trends_data = await _fetch_patient_trends(patient_id, measurement_type)

    trends = [
        TrendPoint(
            date=t["date"],
            value=t["value"],
            unit=t["unit"],
            study_id=t["study_id"],
        )
        for t in trends_data
    ]

    return TrendsResponse(
        patient_id=patient_id,
        measurement_type=measurement_type or "all",
        trends=trends,
    )


FEATURE_EXTRACTION_PROMPT = """You are a radiology AI assistant extracting imaging features from a study.

Study ID: {study_id}
Available measurements: {measurements}

Extract clinical imaging features including morphological characteristics, density/intensity
patterns, and any notable findings. Respond in JSON format:
{{
  "features": [
    {{
      "feature_name": "descriptive name",
      "category": "morphology|density|enhancement|other",
      "value": "description or value",
      "confidence": 0.0 to 1.0
    }}
  ]
}}
"""


@router.post("/imaging/extract-features", response_model=FeatureResponse)
async def extract_features(request: FeatureRequest) -> FeatureResponse:
    """Extract imaging features from a study using NLP/AI analysis.

    Combines measurement data with AI reasoning to identify clinically
    relevant imaging features.
    """
    measurements = await _fetch_study_measurements(request.study_id)

    features: list[ExtractedFeature] = []

    try:
        prompt = FEATURE_EXTRACTION_PROMPT.format(
            study_id=request.study_id,
            measurements=json.dumps(measurements[:20]),
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

            for f in parsed.get("features", []):
                features.append(ExtractedFeature(
                    feature_name=f.get("feature_name", "unknown"),
                    category=f.get("category", "other"),
                    value=f.get("value", ""),
                    confidence=float(f.get("confidence", 0.0)),
                ))
    except Exception:
        # Graceful fallback: generate features from available measurements
        for m in measurements:
            features.append(ExtractedFeature(
                feature_name=f"{m['measurement_type']} measurement",
                category="measurement",
                value=f"{m['value_numeric']} {m['unit']}",
                confidence=0.9,
            ))

        if not features:
            features.append(ExtractedFeature(
                feature_name="No measurements available",
                category="other",
                value="Study has no recorded measurements for feature extraction",
                confidence=0.0,
            ))

    return FeatureResponse(
        study_id=request.study_id,
        features=features,
        feature_count=len(features),
    )
