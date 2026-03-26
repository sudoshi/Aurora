"""Fingerprint router — encoding, outcome computation, and explanation endpoints."""

import logging

from fastapi import APIRouter

from app.models.fingerprint import (
    ClinicalEncodeRequest,
    EncodeResponse,
    ExplainRequest,
    ExplainResponse,
    GenomicEncodeRequest,
    OutcomeComputeRequest,
    OutcomeComputeResponse,
    VolumetricEncodeRequest,
)
from app.services.fingerprint_encoder import encode_clinical, encode_genomic, encode_volumetric
from app.services.fingerprint_explainer import explain_similarity
from app.services.outcome_computer import compute_outcome

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/fingerprint", tags=["fingerprint"])


@router.post("/encode/genomic", response_model=EncodeResponse)
async def encode_genomic_endpoint(request: GenomicEncodeRequest) -> EncodeResponse:
    """Encode a patient's genomic profile into a 256-dim vector."""
    try:
        vector_str, confidence = await encode_genomic(
            patient_id=request.patient_id,
            variants=[v.model_dump() for v in request.variants],
        )
        return EncodeResponse(
            patient_id=request.patient_id,
            vector=vector_str,
            confidence=confidence,
            dimension="genomic",
        )
    except ValueError as exc:
        logger.warning("Genomic encoding failed: %s", exc)
        return EncodeResponse(
            patient_id=request.patient_id,
            vector="",
            confidence=0.0,
            dimension="genomic",
        )
    except Exception as exc:
        logger.error("Genomic encoding error: %s", exc)
        return EncodeResponse(
            patient_id=request.patient_id,
            vector="",
            confidence=0.0,
            dimension="genomic",
        )


@router.post("/encode/volumetric", response_model=EncodeResponse)
async def encode_volumetric_endpoint(request: VolumetricEncodeRequest) -> EncodeResponse:
    """Encode a patient's imaging/volumetric data into a 256-dim vector."""
    try:
        vector_str, confidence = await encode_volumetric(
            patient_id=request.patient_id,
            studies=[s.model_dump() for s in request.studies],
        )
        return EncodeResponse(
            patient_id=request.patient_id,
            vector=vector_str,
            confidence=confidence,
            dimension="volumetric",
        )
    except (ValueError, Exception) as exc:
        logger.error("Volumetric encoding error: %s", exc)
        return EncodeResponse(
            patient_id=request.patient_id,
            vector="",
            confidence=0.0,
            dimension="volumetric",
        )


@router.post("/encode/clinical", response_model=EncodeResponse)
async def encode_clinical_endpoint(request: ClinicalEncodeRequest) -> EncodeResponse:
    """Encode a patient's clinical trajectory into a 256-dim vector."""
    try:
        vector_str, confidence = await encode_clinical(
            patient_id=request.patient_id,
            conditions=[c.model_dump() for c in request.conditions],
            medications=[m.model_dump() for m in request.medications],
            drug_eras=[d.model_dump() for d in request.drug_eras],
            measurements=request.measurements,
            visits=[v.model_dump() for v in request.visits],
        )
        return EncodeResponse(
            patient_id=request.patient_id,
            vector=vector_str,
            confidence=confidence,
            dimension="clinical",
        )
    except (ValueError, Exception) as exc:
        logger.error("Clinical encoding error: %s", exc)
        return EncodeResponse(
            patient_id=request.patient_id,
            vector="",
            confidence=0.0,
            dimension="clinical",
        )


@router.post("/outcome/compute", response_model=OutcomeComputeResponse)
async def compute_outcome_endpoint(request: OutcomeComputeRequest) -> OutcomeComputeResponse:
    """Compute trajectory sub-scores for a patient."""
    try:
        # compute_outcome is synchronous (uses `with get_session()`, not async)
        scores = compute_outcome(request.patient_id)
        return OutcomeComputeResponse(patient_id=request.patient_id, **scores)
    except Exception as exc:
        logger.error("Outcome computation error: %s", exc)
        return OutcomeComputeResponse(patient_id=request.patient_id)


@router.post("/explain", response_model=ExplainResponse)
async def explain_endpoint(request: ExplainRequest) -> ExplainResponse:
    """Generate natural language similarity explanations."""
    try:
        explanations = await explain_similarity(
            query_patient_id=request.query_patient_id,
            similar_patient_ids=request.similar_patient_ids,
        )
        return ExplainResponse(explanations=explanations)
    except Exception as exc:
        logger.error("Explanation generation error: %s", exc)
        return ExplainResponse(
            explanations=[None] * len(request.similar_patient_ids),
        )
