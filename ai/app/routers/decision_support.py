"""Decision support router — clinical intelligence endpoints for Abby."""

import logging

from fastapi import APIRouter

from app.models.decision_support import (
    ConcordanceResult,
    DrugInteraction,
    DrugInteractionRequest,
    DrugInteractionResponse,
    GuidelineCheckRequest,
    GuidelineCheckResponse,
    PrognosticScore,
    PrognosticScoreRequest,
    PrognosticScoreResponse,
    RareDiseaseMatch,
    RareDiseaseMatchRequest,
    RareDiseaseMatchResponse,
    TrialMatchRequest,
    TrialMatchResponse,
    TrialSuggestion,
    VariantInterpretation,
    VariantInterpretRequest,
    VariantInterpretResponse,
)
from app.services.drug_interaction_checker import check_interactions
from app.services.guideline_checker import check_concordance
from app.services.prognostic_scorer import calculate_scores
from app.services.rare_disease_matcher import match_phenotype
from app.services.trial_matching import match_trials
from app.services.variant_interpreter import interpret_variant

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/decision-support", tags=["decision-support"])


@router.post("/trial-match", response_model=TrialMatchResponse)
async def trial_match_endpoint(request: TrialMatchRequest) -> TrialMatchResponse:
    """Find matching clinical trials for a patient profile."""
    try:
        suggestions = await match_trials(request)
        return TrialMatchResponse(
            patient_id=request.patient_id,
            suggestions=suggestions,
        )
    except Exception as exc:
        logger.error("Trial matching failed: %s", exc)
        return TrialMatchResponse(
            patient_id=request.patient_id,
            suggestions=[],
            error=f"Trial matching service unavailable: {type(exc).__name__}",
        )


@router.post("/guidelines", response_model=GuidelineCheckResponse)
async def guideline_check_endpoint(
    request: GuidelineCheckRequest,
) -> GuidelineCheckResponse:
    """Check guideline concordance for a clinical recommendation."""
    try:
        result = await check_concordance(
            recommendation=request.recommendation,
            patient_context=request.patient_context,
            guideline=request.guideline,
        )
        return GuidelineCheckResponse(result=result)
    except Exception as exc:
        logger.error("Guideline check failed: %s", exc)
        return GuidelineCheckResponse(
            error=f"Guideline checker service unavailable: {type(exc).__name__}",
        )


@router.post("/drug-interactions", response_model=DrugInteractionResponse)
async def drug_interaction_endpoint(
    request: DrugInteractionRequest,
) -> DrugInteractionResponse:
    """Check drug-drug interactions for a medication list."""
    try:
        interactions = await check_interactions(
            medications=request.medications,
            proposed_medication=request.proposed_medication,
        )
        return DrugInteractionResponse(interactions=interactions)
    except Exception as exc:
        logger.error("Drug interaction check failed: %s", exc)
        return DrugInteractionResponse(
            interactions=[],
            error=f"Drug interaction service unavailable: {type(exc).__name__}",
        )


@router.post("/variant-interpret", response_model=VariantInterpretResponse)
async def variant_interpret_endpoint(
    request: VariantInterpretRequest,
) -> VariantInterpretResponse:
    """Interpret a genomic variant in clinical context."""
    try:
        interpretation = await interpret_variant(
            gene=request.gene,
            variant=request.variant,
            cancer_type=request.cancer_type,
        )
        return VariantInterpretResponse(interpretation=interpretation)
    except Exception as exc:
        logger.error("Variant interpretation failed: %s", exc)
        return VariantInterpretResponse(
            error=f"Variant interpreter service unavailable: {type(exc).__name__}",
        )


@router.post("/prognosis", response_model=PrognosticScoreResponse)
async def prognosis_endpoint(
    request: PrognosticScoreRequest,
) -> PrognosticScoreResponse:
    """Calculate prognostic scores for a patient."""
    try:
        scores = await calculate_scores(request.patient_data)
        return PrognosticScoreResponse(scores=scores)
    except Exception as exc:
        logger.error("Prognostic scoring failed: %s", exc)
        return PrognosticScoreResponse(
            scores=[],
            error=f"Prognostic scorer service unavailable: {type(exc).__name__}",
        )


@router.post("/rare-disease", response_model=RareDiseaseMatchResponse)
async def rare_disease_endpoint(
    request: RareDiseaseMatchRequest,
) -> RareDiseaseMatchResponse:
    """Match patient phenotype to possible rare diseases."""
    try:
        matches = await match_phenotype(
            symptoms=request.symptoms,
            patient_context=request.patient_context,
        )
        return RareDiseaseMatchResponse(matches=matches)
    except Exception as exc:
        logger.error("Rare disease matching failed: %s", exc)
        return RareDiseaseMatchResponse(
            matches=[],
            error=f"Rare disease matcher service unavailable: {type(exc).__name__}",
        )
