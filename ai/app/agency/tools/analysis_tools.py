"""Analysis tools — agency executors for clinical patient analysis and risk assessment.

Each function follows the standard tool executor signature::

    async def execute_*(api_client, params, auth_token) -> dict

Return value schema:

* Success: ``{"success": True, "analysis_id": <int>, "message": <str>}``
* Failure: ``{"success": False, "error": <str>}``
"""
from __future__ import annotations

import logging
from typing import Any

logger = logging.getLogger(__name__)


async def execute_run_patient_analysis(
    api_client: Any,
    params: dict[str, Any],
    auth_token: str,
) -> dict[str, Any]:
    """Submit a comprehensive patient analysis for asynchronous execution.

    Posts to ``/analyses`` with ``type="patient_analysis"`` and returns the
    analysis ID once the backend has accepted the job (HTTP 202).

    Parameters
    ----------
    api_client:
        :class:`~app.agency.api_client.AgencyApiClient` instance.
    params:
        Expected keys:

        * ``patient_id`` (int, required) — Patient to analyze.
        * ``analysis_type`` (str, optional) — Type of analysis (default
          ``"comprehensive"``).
        * ``include_sections`` (list[str], optional) — Sections to include
          (e.g. ``["conditions", "medications", "procedures"]``).
        * ``name`` (str, optional) — Display name for the analysis.
    auth_token:
        Sanctum Bearer token for the acting user.

    Returns
    -------
    dict
        ``{"success": True, "analysis_id": <int>, "message": <str>}`` on
        success, or ``{"success": False, "error": <str>}`` on failure.
    """
    payload: dict[str, Any] = {
        "type": "patient_analysis",
        "patient_id": params["patient_id"],
    }
    for optional in ("analysis_type", "include_sections", "name"):
        if optional in params:
            payload[optional] = params[optional]

    result = await api_client.call(
        "POST",
        "/analyses",
        auth_token,
        data=payload,
    )
    if not result.get("success"):
        return {
            "success": False,
            "error": result.get("error", "Failed to submit patient analysis"),
        }

    analysis_id: int = result["data"]["id"]
    return {
        "success": True,
        "analysis_id": analysis_id,
        "message": (
            f"Patient analysis submitted for patient "
            f"{params['patient_id']} (analysis_id={analysis_id})"
        ),
    }


async def execute_run_risk_assessment(
    api_client: Any,
    params: dict[str, Any],
    auth_token: str,
) -> dict[str, Any]:
    """Submit a clinical risk assessment for asynchronous execution.

    Posts to ``/analyses`` with ``type="risk_assessment"`` and returns the
    analysis ID once the backend has accepted the job (HTTP 202).

    Parameters
    ----------
    api_client:
        :class:`~app.agency.api_client.AgencyApiClient` instance.
    params:
        Expected keys:

        * ``patient_id`` (int, required) — Patient to assess.
        * ``risk_model`` (str, optional) — Risk model to use (default
          ``"comprehensive"``).
        * ``name`` (str, optional) — Display name for the assessment.
    auth_token:
        Sanctum Bearer token for the acting user.

    Returns
    -------
    dict
        ``{"success": True, "analysis_id": <int>, "message": <str>}`` on
        success, or ``{"success": False, "error": <str>}`` on failure.
    """
    payload: dict[str, Any] = {
        "type": "risk_assessment",
        "patient_id": params["patient_id"],
    }
    for optional in ("risk_model", "name"):
        if optional in params:
            payload[optional] = params[optional]

    result = await api_client.call(
        "POST",
        "/analyses",
        auth_token,
        data=payload,
    )
    if not result.get("success"):
        return {
            "success": False,
            "error": result.get("error", "Failed to submit risk assessment"),
        }

    analysis_id: int = result["data"]["id"]
    return {
        "success": True,
        "analysis_id": analysis_id,
        "message": (
            f"Risk assessment submitted for patient {params['patient_id']} "
            f"(model={params.get('risk_model', 'comprehensive')}, "
            f"analysis_id={analysis_id})"
        ),
    }
