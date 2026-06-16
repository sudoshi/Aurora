"""AI decision-draft router.

`POST /api/ai/abby/draft-decision` produces an evidence-grounded structured MDT
decision draft for a case. It gathers a *de-identified* clinical snapshot (no
name/MRN/DOB ever leaves for the cloud LLM), retrieves grounding evidence from
BioMCP, and asks Claude for the clinical fields only. Sources are taken from the
real BioMCP evidence (never from the model) so citations cannot be fabricated.
Non-device CDS: the draft is advisory; a clinician reviews and records.
"""

from __future__ import annotations

import json
import logging
from datetime import date
from typing import Any

from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from sqlalchemy import text

from app.config import settings
from app.db import get_session
from app.routers.copilot import _fetch_patient_summary_data
from app.routing.claude_client import ClaudeClient
from app.services.biomcp_service import BioMcpService

logger = logging.getLogger(__name__)
router = APIRouter(tags=["decisions"])

_SYSTEM_PROMPT = (
    "You are a multidisciplinary-team (MDT) clinical decision-support assistant. "
    "Given a de-identified patient snapshot, a clinical question, and retrieved "
    "biomedical EVIDENCE, draft a single structured decision. Respond with ONLY a "
    "valid JSON object and nothing else, using exactly these keys: "
    '{"decision_type": one of '
    '["treatment_recommendation","diagnostic_workup","referral","monitoring_plan","palliative","other"], '
    '"recommendation": string, "rationale": string that grounds itself in and '
    "references the supplied EVIDENCE items by their id, "
    '"confidence": number between 0 and 1, "guideline_references": list of strings}. '
    "Do not invent citations and do not include any patient identifiers. This is "
    "advisory, non-device decision support; a clinician will review and decide."
)


class DraftDecisionRequest(BaseModel):
    case_id: int
    patient_id: int
    clinical_question: str | None = None
    decision_type: str | None = None


def _fetch_patient_genes(patient_id: int) -> list[str]:
    with get_session() as session:
        rows = session.execute(
            text(
                "SELECT DISTINCT gene FROM clinical.genomic_variants "
                "WHERE patient_id = :pid AND gene IS NOT NULL"
            ),
            {"pid": patient_id},
        ).fetchall()
    return [r[0] for r in rows if r[0]]


def _age(dob: Any) -> int | None:
    if not dob:
        return None
    try:
        d = dob if isinstance(dob, date) else date.fromisoformat(str(dob)[:10])
        today = date.today()
        return today.year - d.year - ((today.month, today.day) < (d.month, d.day))
    except Exception:  # noqa: BLE001
        return None


def _deidentified_context(snapshot: dict, genes: list[str], evidence: dict, question: str | None) -> str:
    """Build the LLM message from clinical facts only — never identifiers."""
    patient = snapshot.get("patient") or {}
    lines: list[str] = ["# Patient (de-identified)"]
    age = _age(patient.get("date_of_birth"))
    lines.append(f"- Age: {age if age is not None else 'unknown'}; Sex: {patient.get('gender') or 'unknown'}")
    if genes:
        lines.append(f"- Genes with variants: {', '.join(genes)}")

    def _section(title: str, items: list, fmt) -> None:
        if items:
            lines.append(f"\n## {title}")
            lines.extend(f"- {fmt(i)}" for i in items[:15])

    _section("Conditions", snapshot.get("conditions") or [], lambda c: f"{c.get('name')} ({c.get('status')})")
    _section("Medications", snapshot.get("medications") or [], lambda m: f"{m.get('name')} {m.get('dosage') or ''}".strip())
    _section("Recent measurements", snapshot.get("measurements") or [], lambda x: f"{x.get('name')}: {x.get('value')} {x.get('unit') or ''}".strip())
    _section("Observations", snapshot.get("observations") or [], lambda o: f"{o.get('name')}: {o.get('value')}")

    lines.append("\n# Clinical question")
    lines.append(question or "What is the recommended next step for this patient?")

    lines.append("\n# Evidence (cite these by id)")
    for kind in ("articles", "trials", "variants"):
        for e in evidence.get(kind, []):
            lines.append(f"- [{e.get('id')}] ({e.get('type')}) {e.get('title')}")
    if not any(evidence.get(k) for k in ("articles", "trials", "variants")):
        lines.append("- (no external evidence retrieved)")

    return "\n".join(lines)


def _extract_json(reply: str) -> dict:
    start, end = reply.find("{"), reply.rfind("}")
    if start == -1 or end == -1 or end <= start:
        raise ValueError("no JSON object in model reply")
    return json.loads(reply[start : end + 1])


@router.post("/draft-decision")
async def draft_decision(req: DraftDecisionRequest) -> dict:
    snapshot = _fetch_patient_summary_data(req.patient_id)
    genes = _fetch_patient_genes(req.patient_id)
    conditions = [c.get("name") for c in (snapshot.get("conditions") or []) if c.get("name")]
    drugs = [m.get("name") for m in (snapshot.get("medications") or []) if m.get("name")]

    evidence = await BioMcpService().gather(genes=genes, conditions=conditions, drugs=drugs)
    sources = [e for kind in ("articles", "trials", "variants") for e in evidence.get(kind, [])]

    message = _deidentified_context(snapshot, genes, evidence, req.clinical_question)

    if not settings.claude_api_key:
        raise HTTPException(status_code=502, detail="Claude is not configured (CLAUDE_API_KEY).")
    try:
        resp = ClaudeClient(api_key=settings.claude_api_key).chat(
            system_prompt=_SYSTEM_PROMPT, message=message
        )
        draft = _extract_json(resp.reply)
    except HTTPException:
        raise
    except Exception as exc:  # noqa: BLE001
        logger.exception("draft-decision generation failed")
        raise HTTPException(status_code=502, detail=f"Decision draft failed: {exc}") from exc

    return {
        "decision_type": req.decision_type or draft.get("decision_type") or "treatment_recommendation",
        "recommendation": draft.get("recommendation") or "",
        "rationale": draft.get("rationale") or "",
        "confidence": float(draft.get("confidence") or 0.0),
        "guideline_references": draft.get("guideline_references") or [],
        "sources": sources,
        "model": settings.claude_model,
        "evidence_counts": {k: len(evidence.get(k, [])) for k in ("articles", "trials", "variants")},
    }
