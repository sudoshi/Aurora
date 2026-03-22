"""
Copilot router -- AI-assisted clinical document generation.

Provides endpoints for:
- Patient/case summarization
- Post-session clinical note generation
- Case brief generation for presentations

Uses Ollama (MedGemma) for generation with clinical-domain prompts.
"""

import json
import logging
from typing import Any

import httpx
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel, Field

from app.config import settings
from app.db import get_session
from sqlalchemy import text

logger = logging.getLogger(__name__)
router = APIRouter(tags=["copilot"])


# ── Ollama helper ────────────────────────────────────────────────────────────


async def _generate(system_prompt: str, user_message: str, temperature: float = 0.2) -> str:
    """Call Ollama for text generation with retry logic."""
    messages = [
        {"role": "system", "content": system_prompt},
        {"role": "user", "content": user_message},
    ]

    max_retries = 2
    for attempt in range(max_retries + 1):
        attempt_timeout = 180 if attempt == 0 else 60
        try:
            async with httpx.AsyncClient(timeout=attempt_timeout) as client:
                resp = await client.post(
                    f"{settings.ollama_base_url}/api/chat",
                    json={
                        "model": settings.ollama_model,
                        "messages": messages,
                        "stream": False,
                        "keep_alive": 3600,
                        "options": {"temperature": temperature},
                    },
                )
                resp.raise_for_status()
                data = resp.json()
                return data["message"]["content"]  # type: ignore[no-any-return]
        except httpx.TimeoutException:
            if attempt < max_retries:
                logger.warning(
                    "Ollama attempt %d/%d timed out, retrying...",
                    attempt + 1,
                    max_retries + 1,
                )
                continue
            raise HTTPException(
                status_code=504, detail="LLM service timed out after retries."
            )
        except httpx.HTTPStatusError as e:
            if e.response.status_code == 500 and attempt < max_retries:
                logger.warning(
                    "Ollama returned 500 on attempt %d, retrying...", attempt + 1
                )
                continue
            raise HTTPException(
                status_code=503, detail=f"LLM service error: {e}"
            )
        except Exception as e:
            logger.error("Ollama call failed: %s", e)
            raise HTTPException(
                status_code=503, detail=f"LLM service unavailable: {e}"
            )

    raise HTTPException(
        status_code=503, detail="LLM service unavailable: all retries exhausted"
    )


# ── Data fetchers ────────────────────────────────────────────────────────────


def _fetch_patient_summary_data(patient_id: int) -> dict[str, Any]:
    """Fetch a comprehensive data snapshot for patient summarization."""
    with get_session() as session:
        patient = session.execute(
            text("""
                SELECT id, first_name, last_name, date_of_birth, gender,
                       race, ethnicity
                FROM clinical.patients
                WHERE id = :pid
            """),
            {"pid": patient_id},
        ).fetchone()

        if patient is None:
            raise ValueError(f"Patient {patient_id} not found")

        conditions = [
            {"name": r.condition_name, "onset": str(r.onset_date) if r.onset_date else None, "status": r.status}
            for r in session.execute(
                text("""
                    SELECT condition_name, onset_date, status
                    FROM clinical.conditions
                    WHERE patient_id = :pid
                    ORDER BY onset_date DESC NULLS LAST
                """),
                {"pid": patient_id},
            ).fetchall()
        ]

        medications = [
            {"name": r.medication_name, "dosage": r.dosage, "status": r.status}
            for r in session.execute(
                text("""
                    SELECT medication_name, dosage, status
                    FROM clinical.medications
                    WHERE patient_id = :pid
                    ORDER BY start_date DESC NULLS LAST
                """),
                {"pid": patient_id},
            ).fetchall()
        ]

        procedures = [
            {"name": r.procedure_name, "date": str(r.procedure_date) if r.procedure_date else None}
            for r in session.execute(
                text("""
                    SELECT procedure_name, procedure_date
                    FROM clinical.procedures
                    WHERE patient_id = :pid
                    ORDER BY procedure_date DESC NULLS LAST
                """),
                {"pid": patient_id},
            ).fetchall()
        ]

        recent_labs = [
            {"name": r.measurement_name, "value": r.value_numeric, "unit": r.unit,
             "date": str(r.measurement_date) if r.measurement_date else None}
            for r in session.execute(
                text("""
                    SELECT measurement_name, value_numeric, unit, measurement_date
                    FROM clinical.measurements
                    WHERE patient_id = :pid
                    ORDER BY measurement_date DESC NULLS LAST
                    LIMIT 20
                """),
                {"pid": patient_id},
            ).fetchall()
        ]

        observations = [
            {"name": r.observation_name, "value": r.value_as_string, "category": r.category}
            for r in session.execute(
                text("""
                    SELECT observation_name, value_as_string, category
                    FROM clinical.observations
                    WHERE patient_id = :pid
                    ORDER BY observation_date DESC NULLS LAST
                """),
                {"pid": patient_id},
            ).fetchall()
        ]

        recent_notes = [
            {"type": r.note_type, "text": r.note_text[:500], "date": str(r.note_date) if r.note_date else None}
            for r in session.execute(
                text("""
                    SELECT note_type, note_text, note_date
                    FROM clinical.notes
                    WHERE patient_id = :pid
                    ORDER BY note_date DESC NULLS LAST
                    LIMIT 5
                """),
                {"pid": patient_id},
            ).fetchall()
        ]

    age = None
    if patient.date_of_birth:
        from datetime import datetime

        today = datetime.now().date()
        dob = patient.date_of_birth
        age = today.year - dob.year - ((today.month, today.day) < (dob.month, dob.day))

    return {
        "patient_id": patient.id,
        "name": f"{patient.first_name} {patient.last_name}",
        "age": age,
        "gender": patient.gender,
        "race": patient.race,
        "ethnicity": patient.ethnicity,
        "conditions": conditions,
        "medications": medications,
        "procedures": procedures,
        "recent_labs": recent_labs,
        "observations": observations,
        "recent_notes": recent_notes,
    }


def _fetch_session_data(session_id: int) -> dict[str, Any]:
    """Fetch session/visit data for note generation."""
    with get_session() as db_session:
        visit = db_session.execute(
            text("""
                SELECT v.id, v.patient_id, v.visit_date, v.visit_type, v.provider_name,
                       p.first_name, p.last_name
                FROM clinical.visits v
                JOIN clinical.patients p ON p.id = v.patient_id
                WHERE v.id = :sid
            """),
            {"sid": session_id},
        ).fetchone()

        if visit is None:
            raise ValueError(f"Session/visit {session_id} not found")

        notes = [
            {"type": r.note_type, "text": r.note_text, "date": str(r.note_date) if r.note_date else None}
            for r in db_session.execute(
                text("""
                    SELECT note_type, note_text, note_date
                    FROM clinical.notes
                    WHERE patient_id = :pid
                        AND note_date = :vdate
                    ORDER BY note_date DESC NULLS LAST
                """),
                {"pid": visit.patient_id, "vdate": visit.visit_date},
            ).fetchall()
        ]

        # Fetch patient summary for context
        patient_data = _fetch_patient_summary_data(visit.patient_id)

    return {
        "session_id": visit.id,
        "patient_id": visit.patient_id,
        "patient_name": f"{visit.first_name} {visit.last_name}",
        "visit_date": str(visit.visit_date) if visit.visit_date else None,
        "visit_type": visit.visit_type,
        "provider": visit.provider_name,
        "notes": notes,
        "patient_data": patient_data,
    }


def _fetch_case_data(case_id: int) -> dict[str, Any]:
    """Fetch case data for brief generation.

    Uses the patient profile as the case basis, enriched with
    all clinical notes and observations.
    """
    # In Aurora, a "case" maps to a patient's complete clinical record
    patient_data = _fetch_patient_summary_data(case_id)
    return patient_data


# ── Request/Response models ──────────────────────────────────────────────────


class SummarizeRequest(BaseModel):
    patient_id: int | None = Field(
        default=None, description="Patient to summarize"
    )
    case_id: int | None = Field(
        default=None, description="Case/patient ID to summarize"
    )
    context: str = Field(
        default="",
        max_length=2000,
        description="Additional context or focus area for the summary",
    )


class SummarizeResponse(BaseModel):
    patient_id: int
    summary: str
    key_findings: list[str] = []
    active_problems: list[str] = []
    current_medications: list[str] = []


class SessionNoteRequest(BaseModel):
    session_id: int = Field(..., description="Visit/session ID")
    note_style: str = Field(
        default="soap",
        description="Note format: soap, narrative, or brief",
    )


class SessionNoteResponse(BaseModel):
    session_id: int
    patient_id: int
    note: str
    note_style: str


class CaseBriefRequest(BaseModel):
    case_id: int = Field(..., description="Case/patient ID")
    presentation_type: str = Field(
        default="tumor_board",
        description="Type: tumor_board, mdr, grand_rounds, or handoff",
    )


class CaseBriefResponse(BaseModel):
    case_id: int
    brief: str
    presentation_type: str
    key_discussion_points: list[str] = []


# ── System prompts ───────────────────────────────────────────────────────────


SUMMARIZE_SYSTEM_PROMPT = """\
You are Abby, a clinical intelligence assistant for the Aurora platform.

Generate a concise clinical summary of the patient. Structure your response as:

1. **Patient Overview**: Demographics, relevant history
2. **Active Problems**: Current diagnoses and their status
3. **Current Treatment**: Active medications and recent procedures
4. **Key Findings**: Notable lab results, imaging, genomic findings
5. **Clinical Trajectory**: How the patient's condition is evolving

Be precise, evidence-based, and focus on clinically actionable information.
Do NOT fabricate any clinical data — use only what is provided.
"""

SOAP_NOTE_SYSTEM_PROMPT = """\
You are Abby, a clinical documentation assistant for the Aurora platform.

Generate a SOAP note based on the session data provided.

Structure:
**S (Subjective):** Patient-reported symptoms, concerns, history updates
**O (Objective):** Vital signs, exam findings, lab results, imaging
**A (Assessment):** Clinical assessment, differential diagnoses, staging updates
**P (Plan):** Treatment plan, orders, follow-up, referrals

Be precise and use standard medical terminology. Include relevant ICD/CPT codes where applicable.
Do NOT fabricate any clinical data — use only what is provided.
"""

NARRATIVE_NOTE_SYSTEM_PROMPT = """\
You are Abby, a clinical documentation assistant for the Aurora platform.

Generate a narrative clinical note based on the session data provided.
Write in standard clinical prose, covering the encounter comprehensively.
Include relevant history, findings, assessment, and plan in flowing paragraphs.

Do NOT fabricate any clinical data — use only what is provided.
"""

BRIEF_NOTE_SYSTEM_PROMPT = """\
You are Abby, a clinical documentation assistant for the Aurora platform.

Generate a brief clinical note (3-5 sentences) summarizing the key points
of this session. Focus on what changed, what was decided, and what the
next steps are.

Do NOT fabricate any clinical data — use only what is provided.
"""

CASE_BRIEF_PROMPTS: dict[str, str] = {
    "tumor_board": """\
You are Abby, preparing a tumor board case presentation.

Structure the brief as:
1. **Patient Presentation**: Demographics, chief complaint, relevant history
2. **Pathology**: Histology, molecular markers, staging
3. **Imaging Summary**: Key imaging findings and timeline
4. **Treatment History**: Prior and current therapies, responses
5. **Current Status**: Latest labs, imaging, functional status
6. **Discussion Points**: Key questions for the multidisciplinary team
7. **Proposed Next Steps**: Treatment recommendations to discuss

Use standard oncology terminology and TNM staging where applicable.
Do NOT fabricate any clinical data — use only what is provided.
""",
    "mdr": """\
You are Abby, preparing a multidisciplinary review case presentation.

Structure the brief as:
1. **Case Overview**: Patient demographics and primary problem
2. **Clinical Timeline**: Key events in chronological order
3. **Current Assessment**: Active diagnoses and status
4. **Multi-specialty Input Needed**: What each specialty should address
5. **Decision Points**: Key decisions requiring team consensus
6. **Recommended Actions**: Proposed plan for discussion

Do NOT fabricate any clinical data — use only what is provided.
""",
    "grand_rounds": """\
You are Abby, preparing a grand rounds case presentation.

Structure the brief as:
1. **Case Presentation**: Detailed clinical narrative
2. **Diagnostic Workup**: Chronological diagnostic journey
3. **Key Decision Points**: Critical clinical decisions and rationale
4. **Teaching Points**: Educational takeaways from this case
5. **Outcome**: Current status and lessons learned

Do NOT fabricate any clinical data — use only what is provided.
""",
    "handoff": """\
You are Abby, preparing a clinical handoff summary.

Structure the brief as:
1. **Patient**: Name, age, primary diagnosis
2. **Situation**: Why they are here, current status
3. **Background**: Relevant history, recent changes
4. **Assessment**: Current clinical assessment
5. **Recommendation**: Pending actions, what to watch for

Use SBAR format. Be concise and actionable.
Do NOT fabricate any clinical data — use only what is provided.
""",
}


# ── Endpoints ────────────────────────────────────────────────────────────────


@router.post("/copilot/summarize", response_model=SummarizeResponse)
async def summarize(request: SummarizeRequest) -> SummarizeResponse:
    """Summarize a patient's clinical profile.

    Takes a patient_id or case_id (which maps to patient_id in Aurora)
    and generates a structured clinical summary using the patient's
    complete clinical data.
    """
    pid = request.patient_id or request.case_id
    if pid is None:
        raise HTTPException(
            status_code=400,
            detail="Either patient_id or case_id is required",
        )

    try:
        patient_data = _fetch_patient_summary_data(pid)
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e)) from e

    # Build the user message with all clinical data
    user_message = f"Summarize this patient's clinical profile:\n\n{json.dumps(patient_data, indent=2, default=str)}"

    if request.context:
        user_message += f"\n\nFocus area: {request.context}"

    raw = await _generate(SUMMARIZE_SYSTEM_PROMPT, user_message)

    # Extract active problems and medications from the data
    active_problems = [
        c["name"]
        for c in patient_data.get("conditions", [])
        if c.get("status") != "resolved"
    ]
    current_medications = [
        f"{m['name']} {m.get('dosage', '')}".strip()
        for m in patient_data.get("medications", [])
        if m.get("status") != "stopped"
    ]

    # Extract key findings from observations
    key_findings = [
        f"{o['name']}: {o.get('value', '')}".strip()
        for o in patient_data.get("observations", [])
    ]

    return SummarizeResponse(
        patient_id=pid,
        summary=raw.strip(),
        key_findings=key_findings[:10],
        active_problems=active_problems,
        current_medications=current_medications,
    )


@router.post("/copilot/session-note", response_model=SessionNoteResponse)
async def session_note(request: SessionNoteRequest) -> SessionNoteResponse:
    """Generate a post-session clinical note.

    Takes a session/visit ID and generates a clinical note in the
    requested style (SOAP, narrative, or brief) from the session's
    discussions, decisions, and clinical data.
    """
    try:
        session_data = _fetch_session_data(request.session_id)
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e)) from e

    # Select system prompt based on note style
    style_prompts = {
        "soap": SOAP_NOTE_SYSTEM_PROMPT,
        "narrative": NARRATIVE_NOTE_SYSTEM_PROMPT,
        "brief": BRIEF_NOTE_SYSTEM_PROMPT,
    }
    system_prompt = style_prompts.get(request.note_style, SOAP_NOTE_SYSTEM_PROMPT)

    user_message = (
        f"Generate a {request.note_style} note for this clinical session:\n\n"
        f"{json.dumps(session_data, indent=2, default=str)}"
    )

    raw = await _generate(system_prompt, user_message)

    return SessionNoteResponse(
        session_id=request.session_id,
        patient_id=session_data["patient_id"],
        note=raw.strip(),
        note_style=request.note_style,
    )


@router.post("/copilot/case-brief", response_model=CaseBriefResponse)
async def case_brief(request: CaseBriefRequest) -> CaseBriefResponse:
    """Generate a presentation-ready case brief.

    Takes a case/patient ID and presentation type (tumor_board, mdr,
    grand_rounds, handoff) and generates a structured case brief
    suitable for that type of clinical presentation.
    """
    try:
        case_data = _fetch_case_data(request.case_id)
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e)) from e

    system_prompt = CASE_BRIEF_PROMPTS.get(
        request.presentation_type,
        CASE_BRIEF_PROMPTS["tumor_board"],
    )

    user_message = (
        f"Generate a {request.presentation_type} case brief for this patient:\n\n"
        f"{json.dumps(case_data, indent=2, default=str)}"
    )

    raw = await _generate(system_prompt, user_message, temperature=0.15)

    # Extract discussion points from conditions and observations
    discussion_points: list[str] = []
    for c in case_data.get("conditions", []):
        if c.get("status") == "active":
            discussion_points.append(f"Management of {c['name']}")
    for o in case_data.get("observations", []):
        if o.get("category") == "genomic":
            discussion_points.append(
                f"Implications of {o['name']}: {o.get('value', '')}"
            )

    return CaseBriefResponse(
        case_id=request.case_id,
        brief=raw.strip(),
        presentation_type=request.presentation_type,
        key_discussion_points=discussion_points[:5],
    )
