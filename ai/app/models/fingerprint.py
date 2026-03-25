"""Pydantic request/response models for the fingerprint encoding system."""

from __future__ import annotations

from pydantic import BaseModel, Field


# ── Genomic Encoding ──────────────────────────────────────────────────


class VariantInput(BaseModel):
    gene: str
    variant: str | None = None
    variant_type: str | None = None
    allele_frequency: float | None = None
    clinical_significance: str | None = None
    zygosity: str | None = None
    actionability: str | None = None


class GenomicEncodeRequest(BaseModel):
    patient_id: int
    variants: list[VariantInput]


class EncodeResponse(BaseModel):
    patient_id: int
    vector: str  # pgvector-compatible string: "[0.1, 0.2, ...]"
    confidence: float = Field(ge=0.0, le=1.0)
    dimension: str


# ── Volumetric Encoding ──────────────────────────────────────────────


class MeasurementInput(BaseModel):
    measurement_type: str | None = None
    value_numeric: float | None = None
    unit: str | None = None
    target_lesion: bool = False
    measured_at: str | None = None


class SegmentationInput(BaseModel):
    volume_mm3: float | None = None
    label: str | None = None


class StudyInput(BaseModel):
    modality: str | None = None
    body_part: str | None = None
    study_date: str | None = None
    measurements: list[MeasurementInput] = []
    segmentations: list[SegmentationInput] = []


class VolumetricEncodeRequest(BaseModel):
    patient_id: int
    studies: list[StudyInput]


# ── Clinical Encoding ────────────────────────────────────────────────


class ConditionInput(BaseModel):
    concept_name: str
    concept_code: str | None = None
    domain: str | None = None
    status: str | None = None
    severity: str | None = None


class MedicationInput(BaseModel):
    drug_name: str
    dose_value: float | None = None
    dose_unit: str | None = None
    frequency: str | None = None
    status: str | None = None
    start_date: str | None = None
    end_date: str | None = None


class DrugEraInput(BaseModel):
    drug_name: str
    era_start: str | None = None
    era_end: str | None = None
    gap_days: int | None = None


class VisitInput(BaseModel):
    visit_type: str | None = None
    admission_date: str | None = None
    discharge_date: str | None = None


class ClinicalEncodeRequest(BaseModel):
    patient_id: int
    conditions: list[ConditionInput] = []
    medications: list[MedicationInput] = []
    drug_eras: list[DrugEraInput] = []
    measurements: list[dict] = []
    visits: list[VisitInput] = []


# ── Outcome Computation ──────────────────────────────────────────────


class OutcomeComputeRequest(BaseModel):
    patient_id: int


class OutcomeComputeResponse(BaseModel):
    patient_id: int
    tumor_response: float | None = None
    treatment_tolerance: float | None = None
    lab_trajectory: float | None = None
    disease_stability: float | None = None
    care_intensity: float | None = None
    composite: float | None = None


# ── Explanation ──────────────────────────────────────────────────────


class ExplainRequest(BaseModel):
    query_patient_id: int
    similar_patient_ids: list[int]


class ExplainResponse(BaseModel):
    explanations: list[str | None]
