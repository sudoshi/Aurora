"""
Similarity router -- "Patients Like This" endpoints.

Provides patient embedding computation, similarity search, batch embedding,
and embedding coverage statistics.
"""

import logging
from typing import Any

from fastapi import APIRouter, HTTPException
from pydantic import BaseModel, Field

from app.services import embedding_service, similarity_service

logger = logging.getLogger(__name__)
router = APIRouter(tags=["similarity"])


# ── Request/Response models ──────────────────────────────────────────────────


class EmbedRequest(BaseModel):
    patient_id: int = Field(..., description="ID of the patient to embed")


class EmbedResponse(BaseModel):
    patient_id: int
    embedding_dim: int
    success: bool
    message: str = ""


class SearchFilters(BaseModel):
    age_range: dict[str, int | None] | None = Field(
        default=None,
        description="Age filter: {min: 40, max: 80}",
    )
    conditions: list[str] | None = Field(
        default=None,
        description="Required conditions (patient must have at least one)",
    )
    genomics: list[str] | None = Field(
        default=None,
        description="Required genomic variants (patient must have at least one)",
    )


class SearchRequest(BaseModel):
    patient_id: int = Field(..., description="Query patient ID")
    top_k: int = Field(default=20, ge=1, le=100, description="Number of results")
    filters: SearchFilters | None = Field(
        default=None,
        description="Optional filters to narrow the search",
    )


class SimilarPatientResult(BaseModel):
    patient_id: int
    score: float
    shared_conditions: list[str] = []
    shared_medications: list[str] = []
    key_differences: list[str] = []
    outcome_summary: str | None = None
    domain_scores: dict[str, float] = {}


class SearchResponse(BaseModel):
    query_patient_id: int
    results: list[SimilarPatientResult]
    total_results: int


class BatchEmbedRequest(BaseModel):
    patient_ids: list[int] | None = Field(
        default=None,
        description="Specific patient IDs to embed. Null = all unembedded patients.",
    )


class BatchEmbedResponse(BaseModel):
    total: int
    embedded: int
    failed: int
    skipped: int


class EmbeddingStatsResponse(BaseModel):
    total_patients: int
    embedded_patients: int
    coverage_pct: float
    models: dict[str, int]
    oldest_embedding: str | None
    newest_embedding: str | None


# ── Endpoints ────────────────────────────────────────────────────────────────


@router.post("/similarity/embed", response_model=EmbedResponse)
async def embed_patient(request: EmbedRequest) -> EmbedResponse:
    """Compute and store an embedding for a single patient.

    Fetches the patient's clinical data (conditions, medications, procedures,
    labs, genomics), builds a text representation, generates an embedding
    via SapBERT or Ollama, and stores it in clinical.patient_embeddings.
    """
    try:
        embedding = await embedding_service.embed_patient(request.patient_id)
        return EmbedResponse(
            patient_id=request.patient_id,
            embedding_dim=len(embedding),
            success=True,
            message="Embedding computed and stored",
        )
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e)) from e
    except RuntimeError as e:
        raise HTTPException(status_code=503, detail=str(e)) from e
    except Exception as e:
        logger.error("Embedding failed for patient %d: %s", request.patient_id, e)
        raise HTTPException(
            status_code=500, detail="Embedding computation failed"
        ) from e


@router.post("/similarity/search", response_model=SearchResponse)
async def search_similar(request: SearchRequest) -> SearchResponse:
    """Find patients clinically similar to the given patient.

    Uses pgvector cosine distance for initial ANN retrieval, then re-ranks
    results with domain-specific weights (diagnosis 30%, genomics 25%,
    treatment 20%, labs 15%, demographics 10%).

    The query patient must have a stored embedding (call /similarity/embed first).
    """
    filters: dict[str, Any] | None = None
    if request.filters:
        filters = {}
        if request.filters.age_range:
            filters["age_range"] = request.filters.age_range
        if request.filters.conditions:
            filters["conditions"] = request.filters.conditions
        if request.filters.genomics:
            filters["genomics"] = request.filters.genomics

    try:
        results = similarity_service.search_similar(
            patient_id=request.patient_id,
            top_k=request.top_k,
            filters=filters if filters else None,
        )
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e)) from e
    except Exception as e:
        logger.error("Similarity search failed for patient %d: %s", request.patient_id, e)
        raise HTTPException(
            status_code=500, detail="Similarity search failed"
        ) from e

    result_models = [
        SimilarPatientResult(
            patient_id=sp.patient_id,
            score=sp.similarity_score,
            shared_conditions=sp.shared_conditions,
            shared_medications=sp.shared_medications,
            key_differences=sp.key_differences,
            outcome_summary=sp.outcome_summary,
            domain_scores=sp.domain_scores,
        )
        for sp in results
    ]

    return SearchResponse(
        query_patient_id=request.patient_id,
        results=result_models,
        total_results=len(result_models),
    )


@router.post("/similarity/batch-embed", response_model=BatchEmbedResponse)
async def batch_embed(request: BatchEmbedRequest) -> BatchEmbedResponse:
    """Batch-embed multiple patients.

    If patient_ids is null/empty, embeds all patients that do not yet
    have embeddings. Otherwise, embeds only the specified patients.
    """
    try:
        if request.patient_ids:
            counts = await embedding_service.embed_patients_by_ids(
                request.patient_ids
            )
        else:
            counts = await embedding_service.embed_all_patients()
    except Exception as e:
        logger.error("Batch embedding failed: %s", e)
        raise HTTPException(
            status_code=500, detail="Batch embedding failed"
        ) from e

    return BatchEmbedResponse(
        total=counts["total"],
        embedded=counts["embedded"],
        failed=counts["failed"],
        skipped=counts["skipped"],
    )


@router.get("/similarity/stats", response_model=EmbeddingStatsResponse)
async def embedding_stats() -> EmbeddingStatsResponse:
    """Return embedding coverage statistics.

    Shows total patients, how many have embeddings, coverage percentage,
    which models were used, and the date range of embeddings.
    """
    try:
        stats = similarity_service.get_embedding_stats()
    except Exception as e:
        logger.error("Failed to fetch embedding stats: %s", e)
        raise HTTPException(
            status_code=500, detail="Failed to fetch embedding stats"
        ) from e

    return EmbeddingStatsResponse(
        total_patients=stats["total_patients"],
        embedded_patients=stats["embedded_patients"],
        coverage_pct=stats["coverage_pct"],
        models=stats["models"],
        oldest_embedding=stats["oldest_embedding"],
        newest_embedding=stats["newest_embedding"],
    )
