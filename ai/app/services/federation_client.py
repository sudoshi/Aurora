"""
Federation client -- sends queries to the federation relay service
and merges results with local similarity search results.
"""

import logging
import os
from typing import Any

import httpx

logger = logging.getLogger(__name__)

# Federation relay URL — configurable via environment variable
FEDERATION_RELAY_URL = os.environ.get(
    "FEDERATION_RELAY_URL", "http://localhost:8200"
)

# Institution ID for this Aurora instance
INSTITUTION_ID = os.environ.get("AURORA_INSTITUTION_ID", "local")


async def query_federation(
    embedding: list[float],
    filters: dict[str, Any] | None = None,
    top_k: int = 20,
    timeout: float = 30.0,
) -> list[dict[str, Any]]:
    """Send a similarity query to the federation relay.

    Args:
        embedding: The embedding vector for the query patient.
        filters: Optional search filters (age_range, conditions, genomics).
        top_k: Maximum number of results to request per peer.
        timeout: HTTP timeout in seconds.

    Returns:
        List of de-identified results from remote Aurora instances.
        Each result contains: hashed_patient_id, institution_id,
        institution_name, similarity_score, domain_scores.

    Returns an empty list on any error (federation is best-effort).
    """
    url = f"{FEDERATION_RELAY_URL.rstrip('/')}/federation/similarity"
    payload = {
        "embedding": embedding,
        "filters": filters or {},
        "source_institution_id": INSTITUTION_ID,
        "top_k": top_k,
    }

    try:
        async with httpx.AsyncClient() as client:
            resp = await client.post(url, json=payload, timeout=timeout)

        if resp.status_code != 200:
            logger.warning(
                "Federation relay returned status %d: %s",
                resp.status_code,
                resp.text[:200],
            )
            return []

        data = resp.json()
        return data.get("results", [])

    except httpx.TimeoutException:
        logger.warning("Federation relay timed out after %.1fs", timeout)
        return []
    except httpx.ConnectError:
        logger.debug(
            "Federation relay not available at %s (federation is optional)",
            FEDERATION_RELAY_URL,
        )
        return []
    except Exception as exc:
        logger.warning("Federation query failed: %s", exc)
        return []


def merge_results(
    local_results: list[dict[str, Any]],
    remote_results: list[dict[str, Any]],
    top_k: int = 20,
) -> list[dict[str, Any]]:
    """Merge local similarity results with federated remote results.

    Local results are tagged with the local institution ID.
    Remote results already have institution labels from the relay.
    Results are re-ranked by similarity_score descending.

    Args:
        local_results: Results from the local similarity search.
        remote_results: De-identified results from federation relay.
        top_k: Maximum number of merged results to return.

    Returns:
        Merged and re-ranked list of results, each containing:
        - patient_id or hashed_patient_id
        - institution_id / institution_name
        - similarity_score
        - domain_scores
        - is_local (bool)
    """
    merged: list[dict[str, Any]] = []

    # Tag local results
    for result in local_results:
        merged.append(
            {
                **result,
                "institution_id": INSTITUTION_ID,
                "institution_name": "Local",
                "is_local": True,
            }
        )

    # Add remote results
    for result in remote_results:
        merged.append(
            {
                "hashed_patient_id": result.get("hashed_patient_id", ""),
                "institution_id": result.get("institution_id", "unknown"),
                "institution_name": result.get("institution_name", "Remote"),
                "similarity_score": result.get("similarity_score", 0.0),
                "domain_scores": result.get("domain_scores", {}),
                "aggregate_info": result.get("aggregate_info", {}),
                "is_local": False,
            }
        )

    # Sort by similarity score descending
    merged.sort(key=lambda r: r.get("similarity_score", 0.0), reverse=True)
    return merged[:top_k]
