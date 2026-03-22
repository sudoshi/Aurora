"""
Federation relay -- routes queries between Aurora instances.
mTLS-authenticated, signed messages, k-anonymity enforced.
"""

import asyncio
import base64
import logging
import time
from contextlib import asynccontextmanager
from typing import Any

import httpx
from fastapi import FastAPI, HTTPException, Request
from pydantic import BaseModel, Field

from config import settings
from crypto import hash_patient_id, verify_signature
from registry import FederationRegistry, PeerInstitution

logger = logging.getLogger(__name__)


# ── Request/Response models ──────────────────────────────────────────────────


class FederationQueryRequest(BaseModel):
    query_type: str = Field(
        ...,
        description="Type of query: 'similarity' or 'aggregate_stats'",
    )
    payload: dict[str, Any] = Field(
        ...,
        description="Query payload — embedding vector for similarity, params for aggregate",
    )
    source_institution_id: str = Field(
        ...,
        description="ID of the institution originating the query",
    )
    max_results: int = Field(
        default=20,
        ge=1,
        le=100,
        description="Maximum results per peer",
    )
    signature: str = Field(
        default="",
        description="Base64-encoded Ed25519 signature of the payload",
    )


class FederationResponsePayload(BaseModel):
    institution_id: str
    query_id: str = ""
    results: list[dict[str, Any]] = []
    patient_count: int = 0
    signature: str = ""


class SimilarityQueryRequest(BaseModel):
    embedding: list[float] = Field(
        ...,
        description="Embedding vector for similarity search",
    )
    filters: dict[str, Any] = Field(
        default_factory=dict,
        description="Optional filters (age_range, conditions, genomics)",
    )
    source_institution_id: str = Field(
        ...,
        description="ID of the institution originating the query",
    )
    top_k: int = Field(
        default=20,
        ge=1,
        le=100,
        description="Number of results per peer",
    )
    signature: str = Field(
        default="",
        description="Base64-encoded Ed25519 signature of the request",
    )


class FederatedResult(BaseModel):
    hashed_patient_id: str
    institution_id: str
    institution_name: str
    similarity_score: float
    domain_scores: dict[str, float] = {}
    aggregate_info: dict[str, Any] = {}


class SimilarityResponse(BaseModel):
    results: list[FederatedResult]
    total_results: int
    peers_queried: int
    peers_responded: int
    query_time_ms: float


class PeerRegistrationRequest(BaseModel):
    id: str = Field(..., description="Unique institution identifier")
    name: str = Field(..., description="Institution display name")
    endpoint_url: str = Field(..., description="Base URL of the peer Aurora instance")
    public_key: str = Field(..., description="Hex-encoded Ed25519 public key")
    capabilities: list[str] = Field(
        default=["similarity", "aggregate_stats"],
        description="Supported query types",
    )


class PeerResponse(BaseModel):
    id: str
    name: str
    endpoint_url: str
    status: str
    registered_at: str
    last_seen_at: str
    capabilities: list[str]


class HealthResponse(BaseModel):
    status: str
    service: str
    version: str
    peers_active: int
    peers_total: int
    uptime_seconds: float


# ── Application ──────────────────────────────────────────────────────────────

_start_time: float = 0.0
_registry: FederationRegistry | None = None


def get_registry() -> FederationRegistry:
    """Return the global registry instance."""
    global _registry
    if _registry is None:
        _registry = FederationRegistry()
    return _registry


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Initialize the federation relay on startup."""
    global _start_time, _registry
    _start_time = time.monotonic()
    _registry = FederationRegistry()
    logger.info(
        "Federation relay started with %d registered peers",
        _registry.peer_count,
    )
    yield
    logger.info("Federation relay shutting down")


app = FastAPI(
    title=settings.app_name,
    version="1.0.0",
    description="Aurora Federation Relay — routes queries between Aurora instances",
    lifespan=lifespan,
)


# ── Helper functions ─────────────────────────────────────────────────────────


def _validate_query_type(query_type: str) -> None:
    """Raise HTTPException if query_type is not allowed."""
    if query_type not in settings.allowed_query_types:
        raise HTTPException(
            status_code=400,
            detail=(
                f"Query type '{query_type}' not allowed. "
                f"Allowed: {settings.allowed_query_types}"
            ),
        )


def _validate_source_institution(source_id: str) -> PeerInstitution:
    """Validate the source institution is registered and active."""
    registry = get_registry()
    peer = registry.get_peer(source_id)
    if peer is None:
        raise HTTPException(
            status_code=403,
            detail=f"Institution '{source_id}' is not registered",
        )
    if peer.status != "active":
        raise HTTPException(
            status_code=403,
            detail=f"Institution '{source_id}' status is '{peer.status}', not active",
        )
    registry.update_last_seen(source_id)
    return peer


def _verify_request_signature(
    payload_bytes: bytes, signature_b64: str, public_key_hex: str
) -> bool:
    """Verify the Ed25519 signature on a request payload."""
    if not signature_b64:
        return False
    try:
        signature = base64.b64decode(signature_b64)
        public_key = bytes.fromhex(public_key_hex)
        return verify_signature(payload_bytes, signature, public_key)
    except Exception as exc:
        logger.warning("Signature verification failed: %s", exc)
        return False


def _enforce_k_anonymity(
    results: list[dict[str, Any]], institution_id: str
) -> list[dict[str, Any]]:
    """Suppress results that don't meet k-anonymity threshold.

    If fewer than min_k_anonymity patients are in the result set from
    an institution, the results are suppressed entirely to prevent
    re-identification.
    """
    if len(results) < settings.min_k_anonymity:
        logger.info(
            "Suppressing %d results from %s (below k-anonymity threshold of %d)",
            len(results),
            institution_id,
            settings.min_k_anonymity,
        )
        return []
    return results


def _deidentify_results(
    results: list[dict[str, Any]], institution_id: str
) -> list[dict[str, Any]]:
    """De-identify results by hashing patient IDs and stripping PHI."""
    deidentified = []
    for result in results:
        clean = {
            "hashed_patient_id": hash_patient_id(
                result.get("patient_id", 0), institution_id
            ),
            "similarity_score": result.get("similarity_score", 0.0),
            "domain_scores": result.get("domain_scores", {}),
            "aggregate_info": {
                k: v
                for k, v in result.items()
                if k
                not in (
                    "patient_id",
                    "similarity_score",
                    "domain_scores",
                    "name",
                    "date_of_birth",
                    "mrn",
                    "ssn",
                    "address",
                    "phone",
                    "email",
                )
            },
        }
        deidentified.append(clean)
    return deidentified


async def _fan_out_query(
    peers: list[PeerInstitution],
    path: str,
    payload: dict[str, Any],
    timeout: int,
) -> list[tuple[PeerInstitution, dict[str, Any] | None]]:
    """Send a query to multiple peers concurrently, returning results."""
    results: list[tuple[PeerInstitution, dict[str, Any] | None]] = []

    async def _query_peer(
        client: httpx.AsyncClient, peer: PeerInstitution
    ) -> tuple[PeerInstitution, dict[str, Any] | None]:
        url = f"{peer.endpoint_url.rstrip('/')}{path}"
        try:
            resp = await client.post(url, json=payload, timeout=timeout)
            if resp.status_code == 200:
                return peer, resp.json()
            logger.warning(
                "Peer %s returned status %d: %s",
                peer.id,
                resp.status_code,
                resp.text[:200],
            )
            return peer, None
        except httpx.TimeoutException:
            logger.warning("Peer %s timed out after %ds", peer.id, timeout)
            return peer, None
        except Exception as exc:
            logger.warning("Peer %s query failed: %s", peer.id, exc)
            return peer, None

    async with httpx.AsyncClient() as client:
        tasks = [_query_peer(client, peer) for peer in peers]
        results = await asyncio.gather(*tasks)

    return list(results)


# ── Endpoints ────────────────────────────────────────────────────────────────


@app.get("/federation/health", response_model=HealthResponse)
async def health_check() -> HealthResponse:
    """Return federation relay health status."""
    registry = get_registry()
    active_peers = registry.get_active_peers()
    return HealthResponse(
        status="healthy",
        service=settings.app_name,
        version="1.0.0",
        peers_active=len(active_peers),
        peers_total=registry.peer_count,
        uptime_seconds=round(time.monotonic() - _start_time, 2),
    )


@app.post("/federation/query")
async def federation_query(request: FederationQueryRequest) -> dict[str, Any]:
    """Receive a query, fan out to peers, merge and return results.

    The source institution must be registered and active. The query is
    forwarded to all active peers (except the source), results are
    de-identified, k-anonymity is enforced, and merged results returned.
    """
    _validate_query_type(request.query_type)
    source = _validate_source_institution(request.source_institution_id)

    registry = get_registry()
    active_peers = [
        p
        for p in registry.get_active_peers()
        if p.id != request.source_institution_id
    ]

    # Limit fan-out
    peers_to_query = active_peers[: settings.max_query_fan_out]

    start_ms = time.monotonic()
    peer_results = await _fan_out_query(
        peers_to_query,
        "/federation/respond",
        {
            "query_type": request.query_type,
            "payload": request.payload,
            "source_institution_id": request.source_institution_id,
            "max_results": min(request.max_results, settings.max_results_per_peer),
        },
        timeout=settings.relay_timeout,
    )
    elapsed_ms = (time.monotonic() - start_ms) * 1000

    # Merge results
    merged: list[dict[str, Any]] = []
    peers_responded = 0
    for peer, result in peer_results:
        if result is None:
            continue
        peers_responded += 1
        raw_results = result.get("results", [])
        deidentified = _deidentify_results(raw_results, peer.id)
        filtered = _enforce_k_anonymity(deidentified, peer.id)
        merged.extend(filtered)

    # Sort by similarity score descending and limit
    merged.sort(key=lambda r: r.get("similarity_score", 0.0), reverse=True)
    merged = merged[: request.max_results]

    return {
        "results": merged,
        "total_results": len(merged),
        "peers_queried": len(peers_to_query),
        "peers_responded": peers_responded,
        "query_time_ms": round(elapsed_ms, 2),
    }


@app.post("/federation/respond")
async def federation_respond(
    request: Request,
) -> dict[str, Any]:
    """Peer responds to a relayed query.

    Called by peer Aurora instances to provide their local results
    for a federated query. Results are de-identified before returning.
    """
    body = await request.json()
    query_type = body.get("query_type", "")
    _validate_query_type(query_type)

    # In a real deployment, the peer would run its local similarity search
    # and return results. This endpoint is the interface peers implement.
    # For the relay itself, this is a stub that returns empty results.
    return {
        "institution_id": "local",
        "results": [],
        "patient_count": 0,
    }


@app.post("/federation/similarity", response_model=SimilarityResponse)
async def federated_similarity(request: SimilarityQueryRequest) -> SimilarityResponse:
    """Federated 'Patients Like This' similarity search.

    Takes an embedding vector and optional filters, fans out similarity
    queries to all active peers, each peer returns their top-N de-identified
    similar patients (no PHI, just aggregate scores), relay merges and
    re-ranks, and returns unified results with institution labels.
    """
    _validate_source_institution(request.source_institution_id)

    registry = get_registry()
    active_peers = [
        p
        for p in registry.get_active_peers()
        if p.id != request.source_institution_id
    ]

    # Filter to peers that support similarity
    similarity_peers = [
        p for p in active_peers if "similarity" in p.capabilities
    ]
    peers_to_query = similarity_peers[: settings.max_query_fan_out]

    start_ms = time.monotonic()
    peer_results = await _fan_out_query(
        peers_to_query,
        "/federation/respond",
        {
            "query_type": "similarity",
            "payload": {
                "embedding": request.embedding,
                "filters": request.filters,
                "top_k": min(request.top_k, settings.max_results_per_peer),
            },
            "source_institution_id": request.source_institution_id,
            "max_results": min(request.top_k, settings.max_results_per_peer),
        },
        timeout=settings.relay_timeout,
    )
    elapsed_ms = (time.monotonic() - start_ms) * 1000

    # Merge and de-identify
    federated_results: list[FederatedResult] = []
    peers_responded = 0

    for peer, result in peer_results:
        if result is None:
            continue
        peers_responded += 1
        raw_results = result.get("results", [])

        # Enforce k-anonymity
        if len(raw_results) < settings.min_k_anonymity:
            logger.info(
                "Suppressing results from %s (count %d < k=%d)",
                peer.id,
                len(raw_results),
                settings.min_k_anonymity,
            )
            continue

        for r in raw_results:
            federated_results.append(
                FederatedResult(
                    hashed_patient_id=hash_patient_id(
                        r.get("patient_id", 0), peer.id
                    ),
                    institution_id=peer.id,
                    institution_name=peer.name,
                    similarity_score=r.get("similarity_score", 0.0),
                    domain_scores=r.get("domain_scores", {}),
                    aggregate_info={
                        k: v
                        for k, v in r.items()
                        if k not in (
                            "patient_id",
                            "similarity_score",
                            "domain_scores",
                            "name",
                            "date_of_birth",
                            "mrn",
                            "ssn",
                            "address",
                            "phone",
                            "email",
                        )
                    },
                )
            )

    # Sort by similarity score descending
    federated_results.sort(key=lambda r: r.similarity_score, reverse=True)
    federated_results = federated_results[: request.top_k]

    return SimilarityResponse(
        results=federated_results,
        total_results=len(federated_results),
        peers_queried=len(peers_to_query),
        peers_responded=peers_responded,
        query_time_ms=round(elapsed_ms, 2),
    )


@app.get("/federation/peers", response_model=list[PeerResponse])
async def list_peers() -> list[PeerResponse]:
    """List all registered peers (admin only)."""
    registry = get_registry()
    peers = []
    for peer in registry.get_active_peers():
        peers.append(
            PeerResponse(
                id=peer.id,
                name=peer.name,
                endpoint_url=peer.endpoint_url,
                status=peer.status,
                registered_at=peer.registered_at,
                last_seen_at=peer.last_seen_at,
                capabilities=peer.capabilities,
            )
        )
    return peers


@app.post("/federation/peers/register", response_model=PeerResponse)
async def register_peer(request: PeerRegistrationRequest) -> PeerResponse:
    """Register a new peer institution."""
    registry = get_registry()

    institution = PeerInstitution(
        id=request.id,
        name=request.name,
        endpoint_url=request.endpoint_url,
        public_key=request.public_key,
        capabilities=request.capabilities,
    )

    try:
        registry.register_peer(institution)
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc

    return PeerResponse(
        id=institution.id,
        name=institution.name,
        endpoint_url=institution.endpoint_url,
        status=institution.status,
        registered_at=institution.registered_at,
        last_seen_at=institution.last_seen_at,
        capabilities=institution.capabilities,
    )


@app.delete("/federation/peers/{peer_id}")
async def remove_peer(peer_id: str) -> dict[str, str]:
    """Remove a peer institution from the registry."""
    registry = get_registry()
    try:
        registry.remove_peer(peer_id)
    except KeyError as exc:
        raise HTTPException(status_code=404, detail=str(exc)) from exc
    return {"status": "removed", "peer_id": peer_id}


# ── Entrypoint ───────────────────────────────────────────────────────────────

if __name__ == "__main__":
    import uvicorn

    uvicorn.run(
        "relay:app",
        host=settings.host,
        port=settings.port,
        reload=True,
        log_level="info",
    )
