"""
Patient embedding service -- computes clinical embedding vectors from patient data.

A patient's embedding captures their clinical profile:
- Diagnosis codes and severity
- Medication history
- Procedure history
- Lab value patterns
- Genomic variants (when available)
- Imaging findings

The embedding is stored in clinical.patient_embeddings via pgvector.
"""

import logging
from datetime import datetime
from typing import Any

import httpx
from sqlalchemy import text

from app.config import settings
from app.db import get_session

logger = logging.getLogger(__name__)

# Embedding dimension — SapBERT produces 768-dim vectors
EMBEDDING_DIM = 768


def _fetch_patient_data(patient_id: int) -> dict[str, Any]:
    """Fetch all clinical data for a patient from the clinical schema.

    Returns a dict with keys: demographics, conditions, medications,
    procedures, measurements, observations.
    """
    with get_session() as session:
        # Demographics
        demo_row = session.execute(
            text("""
                SELECT p.id, p.first_name, p.last_name, p.date_of_birth,
                       p.gender, p.race, p.ethnicity
                FROM clinical.patients p
                WHERE p.id = :pid
            """),
            {"pid": patient_id},
        ).fetchone()

        if demo_row is None:
            raise ValueError(f"Patient {patient_id} not found")

        age = None
        if demo_row.date_of_birth:
            today = datetime.now().date()
            dob = demo_row.date_of_birth
            age = today.year - dob.year - ((today.month, today.day) < (dob.month, dob.day))

        demographics = {
            "patient_id": demo_row.id,
            "age": age,
            "gender": demo_row.gender,
            "race": demo_row.race,
            "ethnicity": demo_row.ethnicity,
        }

        # Conditions
        conditions = [
            {"name": r.condition_name, "onset_date": r.onset_date, "status": r.status}
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

        # Medications
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

        # Procedures
        procedures = [
            {"name": r.procedure_name, "date": r.procedure_date}
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

        # Measurements (labs)
        measurements = [
            {
                "name": r.measurement_name,
                "value": r.value_numeric,
                "unit": r.unit,
                "date": r.measurement_date,
            }
            for r in session.execute(
                text("""
                    SELECT measurement_name, value_numeric, unit, measurement_date
                    FROM clinical.measurements
                    WHERE patient_id = :pid
                    ORDER BY measurement_date DESC NULLS LAST
                """),
                {"pid": patient_id},
            ).fetchall()
        ]

        # Observations (genomics, imaging findings, etc.)
        observations = [
            {
                "name": r.observation_name,
                "value": r.value_as_string,
                "category": r.category,
            }
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

    return {
        "demographics": demographics,
        "conditions": conditions,
        "medications": medications,
        "procedures": procedures,
        "measurements": measurements,
        "observations": observations,
    }


def build_patient_text(patient_data: dict[str, Any]) -> str:
    """Create a structured text representation of a patient's clinical profile.

    This text is used as input to the embedding model. The structured format
    ensures consistent encoding across patients.

    Example output:
        Demographics: 67yo Male
        Conditions: Type 2 Diabetes Mellitus (2019), Hypertension (2015)
        Medications: Metformin 1000mg, Lisinopril 20mg
        Procedures: Right upper lobectomy (2025)
        Key Labs: HbA1c 7.2, Creatinine 1.1
        Genomic: EGFR L858R mutation, PD-L1 TPS 80%
    """
    sections: list[str] = []

    # Demographics
    demo = patient_data.get("demographics", {})
    age_str = f"{demo['age']}yo" if demo.get("age") else "Unknown age"
    gender_str = demo.get("gender") or "Unknown"
    sections.append(f"Demographics: {age_str} {gender_str}")

    # Conditions
    conditions = patient_data.get("conditions", [])
    if conditions:
        parts = []
        for c in conditions:
            name = c["name"]
            year = ""
            if c.get("onset_date"):
                onset = c["onset_date"]
                year = f" ({onset.year if hasattr(onset, 'year') else str(onset)[:4]})"
            parts.append(f"{name}{year}")
        sections.append(f"Conditions: {', '.join(parts)}")

    # Medications
    medications = patient_data.get("medications", [])
    if medications:
        active_meds = [m for m in medications if m.get("status") != "stopped"]
        if not active_meds:
            active_meds = medications
        parts = []
        for m in active_meds:
            name = m["name"]
            dosage = f" {m['dosage']}" if m.get("dosage") else ""
            parts.append(f"{name}{dosage}")
        sections.append(f"Medications: {', '.join(parts)}")

    # Procedures
    procedures = patient_data.get("procedures", [])
    if procedures:
        parts = []
        for p in procedures:
            name = p["name"]
            year = ""
            if p.get("date"):
                date_val = p["date"]
                year = f" ({date_val.year if hasattr(date_val, 'year') else str(date_val)[:4]})"
            parts.append(f"{name}{year}")
        sections.append(f"Procedures: {', '.join(parts)}")

    # Key Labs (most recent values)
    measurements = patient_data.get("measurements", [])
    if measurements:
        # Deduplicate by name, keeping most recent
        seen: set[str] = set()
        parts = []
        for m in measurements:
            name = m["name"]
            if name in seen:
                continue
            seen.add(name)
            val = m.get("value")
            if val is not None:
                unit = f" {m['unit']}" if m.get("unit") else ""
                parts.append(f"{name} {val}{unit}")
            if len(parts) >= 10:
                break
        if parts:
            sections.append(f"Key Labs: {', '.join(parts)}")

    # Genomic / observations
    observations = patient_data.get("observations", [])
    if observations:
        genomic = [o for o in observations if o.get("category") == "genomic"]
        imaging = [o for o in observations if o.get("category") == "imaging"]
        other = [o for o in observations
                 if o.get("category") not in ("genomic", "imaging")]

        if genomic:
            parts = []
            for o in genomic:
                val = o.get("value", "")
                parts.append(f"{o['name']} {val}".strip())
            sections.append(f"Genomic: {', '.join(parts)}")

        if imaging:
            parts = []
            for o in imaging:
                val = o.get("value", "")
                parts.append(f"{o['name']} {val}".strip())
            sections.append(f"Imaging: {', '.join(parts)}")

        if other:
            parts = []
            for o in other:
                val = o.get("value", "")
                parts.append(f"{o['name']} {val}".strip())
            sections.append(f"Observations: {', '.join(parts)}")

    return "\n".join(sections)


async def compute_embedding(text: str) -> list[float]:
    """Generate an embedding vector from clinical text.

    Attempts SapBERT first (768-dim, local, medical-domain).
    Falls back to Ollama embedding endpoint if SapBERT is unavailable.
    """
    # Try SapBERT first
    try:
        from app.services.sapbert import get_sapbert_service

        service = get_sapbert_service()
        embedding = service.encode_single(text)
        logger.info("Generated embedding via SapBERT (%d dims)", len(embedding))
        return embedding
    except Exception as sapbert_err:
        logger.info("SapBERT unavailable (%s), falling back to Ollama", sapbert_err)

    # Fallback: Ollama embeddings endpoint
    try:
        async with httpx.AsyncClient(timeout=settings.ollama_timeout) as client:
            resp = await client.post(
                f"{settings.ollama_base_url}/api/embed",
                json={
                    "model": settings.ollama_model,
                    "input": text,
                },
            )
            resp.raise_for_status()
            data = resp.json()

            # Ollama returns {"embeddings": [[...]]} for /api/embed
            embeddings = data.get("embeddings", [])
            if embeddings and len(embeddings) > 0:
                embedding = embeddings[0]
                logger.info(
                    "Generated embedding via Ollama (%d dims)", len(embedding)
                )
                return embedding

            raise ValueError("Ollama returned empty embeddings")
    except Exception as ollama_err:
        logger.error("Ollama embedding failed: %s", ollama_err)
        raise RuntimeError(
            f"No embedding backend available. SapBERT: {sapbert_err}, Ollama: {ollama_err}"
        ) from ollama_err


def _store_embedding(
    patient_id: int,
    embedding: list[float],
    model_name: str,
) -> None:
    """Store or update a patient embedding in clinical.patient_embeddings."""
    embedding_str = "[" + ",".join(str(x) for x in embedding) + "]"

    with get_session() as session:
        # Upsert: delete existing then insert
        session.execute(
            text("""
                DELETE FROM clinical.patient_embeddings
                WHERE patient_id = :pid
            """),
            {"pid": patient_id},
        )
        session.execute(
            text("""
                INSERT INTO clinical.patient_embeddings
                    (patient_id, embedding, model_name, created_at)
                VALUES
                    (:pid, :embedding::vector, :model, NOW())
            """),
            {
                "pid": patient_id,
                "embedding": embedding_str,
                "model": model_name,
            },
        )


def _detect_model_name() -> str:
    """Detect which embedding model is available."""
    try:
        from app.services.sapbert import get_sapbert_service

        get_sapbert_service()
        return settings.sapbert_model
    except Exception:
        return f"ollama/{settings.ollama_model}"


async def embed_patient(patient_id: int) -> list[float]:
    """Full embedding pipeline for a single patient.

    1. Fetch clinical data from the database
    2. Build text representation
    3. Compute embedding vector
    4. Store in patient_embeddings table
    5. Return the embedding
    """
    logger.info("Embedding patient %d", patient_id)

    patient_data = _fetch_patient_data(patient_id)
    patient_text = build_patient_text(patient_data)

    if not patient_text.strip():
        raise ValueError(f"Patient {patient_id} has no clinical data to embed")

    embedding = await compute_embedding(patient_text)
    model_name = _detect_model_name()
    _store_embedding(patient_id, embedding, model_name)

    logger.info(
        "Stored embedding for patient %d (%d dims, model=%s)",
        patient_id,
        len(embedding),
        model_name,
    )
    return embedding


async def embed_all_patients() -> dict[str, int]:
    """Batch-embed all patients that do not yet have embeddings.

    Returns a dict with total, embedded, failed, and skipped counts.
    """
    with get_session() as session:
        rows = session.execute(
            text("""
                SELECT p.id
                FROM clinical.patients p
                LEFT JOIN clinical.patient_embeddings pe ON pe.patient_id = p.id
                WHERE pe.id IS NULL
                ORDER BY p.id
            """)
        ).fetchall()

    patient_ids = [r.id for r in rows]
    total = len(patient_ids)
    embedded = 0
    failed = 0

    logger.info("Batch embedding %d patients without embeddings", total)

    for pid in patient_ids:
        try:
            await embed_patient(pid)
            embedded += 1
        except Exception as e:
            logger.warning("Failed to embed patient %d: %s", pid, e)
            failed += 1

    logger.info(
        "Batch embedding complete: %d embedded, %d failed out of %d",
        embedded,
        failed,
        total,
    )
    return {
        "total": total,
        "embedded": embedded,
        "failed": failed,
        "skipped": 0,
    }


async def embed_patients_by_ids(patient_ids: list[int]) -> dict[str, int]:
    """Embed a specific list of patients.

    Returns a dict with total, embedded, failed, and skipped counts.
    """
    total = len(patient_ids)
    embedded = 0
    failed = 0
    skipped = 0

    for pid in patient_ids:
        try:
            await embed_patient(pid)
            embedded += 1
        except ValueError as e:
            # Patient not found or no data
            logger.warning("Skipped patient %d: %s", pid, e)
            skipped += 1
        except Exception as e:
            logger.warning("Failed to embed patient %d: %s", pid, e)
            failed += 1

    return {
        "total": total,
        "embedded": embedded,
        "failed": failed,
        "skipped": skipped,
    }
