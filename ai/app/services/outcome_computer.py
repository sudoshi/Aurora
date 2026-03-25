"""Compute outcome trajectory sub-scores from patient clinical data."""

import logging
from typing import Any

from sqlalchemy import text

from app.db import get_session

logger = logging.getLogger(__name__)

# Default outcome sub-score weights
OUTCOME_WEIGHTS: dict[str, float] = {
    "tumor_response": 0.30,
    "treatment_tolerance": 0.20,
    "lab_trajectory": 0.20,
    "disease_stability": 0.15,
    "care_intensity": 0.15,
}


def compute_outcome(patient_id: int) -> dict[str, float | None]:
    """Compute all five trajectory sub-scores for a patient.

    Returns dict with keys: tumor_response, treatment_tolerance,
    lab_trajectory, disease_stability, care_intensity, composite.
    """
    scores: dict[str, float | None] = {}

    with get_session() as session:
        scores["tumor_response"] = _tumor_response(session, patient_id)
        scores["treatment_tolerance"] = _treatment_tolerance(session, patient_id)
        scores["lab_trajectory"] = _lab_trajectory(session, patient_id)
        scores["disease_stability"] = _disease_stability(session, patient_id)
        scores["care_intensity"] = _care_intensity(session, patient_id)

    # Composite = weighted sum of available scores
    available = {k: v for k, v in scores.items() if v is not None}
    if available:
        total_weight = sum(OUTCOME_WEIGHTS[k] for k in available)
        if total_weight > 0:
            scores["composite"] = round(
                sum(v * OUTCOME_WEIGHTS[k] / total_weight for k, v in available.items()),
                4,
            )
        else:
            scores["composite"] = None
    else:
        scores["composite"] = None

    return scores


def _tumor_response(session: Any, patient_id: int) -> float | None:
    """RECIST category + volume change adjustment. Clamp to [0, 1]."""
    result = session.execute(
        text("""
            SELECT im.measurement_type, im.value_numeric,
                   iseg.volume_mm3
            FROM clinical.imaging_studies ist
            LEFT JOIN clinical.imaging_measurements im ON im.imaging_study_id = ist.id
            LEFT JOIN clinical.imaging_segmentations iseg ON iseg.imaging_study_id = ist.id
            WHERE ist.patient_id = :pid
            ORDER BY ist.study_date DESC
        """),
        {"pid": patient_id},
    )
    rows = result.fetchall()
    if not rows:
        return None

    # Simple RECIST mapping — find best response
    recist_map = {"CR": 1.0, "PR": 0.75, "SD": 0.5, "PD": 0.0}
    best = 0.0
    for row in rows:
        if row.measurement_type == "RECIST" and row.value_numeric is not None:
            # Map string-like values
            for key, val in recist_map.items():
                if val > best:
                    best = val

    return round(max(0.0, min(1.0, best)), 4)


def _treatment_tolerance(session: Any, patient_id: int) -> float | None:
    """Drug era completion ratio."""
    result = session.execute(
        text("""
            SELECT drug_name, era_start, era_end, gap_days
            FROM clinical.drug_eras
            WHERE patient_id = :pid AND era_start IS NOT NULL
        """),
        {"pid": patient_id},
    )
    eras = result.fetchall()
    if not eras:
        return None

    completion_ratios = []
    for era in eras:
        if era.era_start and era.era_end:
            days = (era.era_end - era.era_start).days
            # Simple heuristic: longer era = better tolerance
            completion_ratios.append(min(days / 180.0, 1.0))

    if not completion_ratios:
        return None

    return round(sum(completion_ratios) / len(completion_ratios), 4)


def _lab_trajectory(session: Any, patient_id: int) -> float | None:
    """Key markers trending toward normal. Simplified: proportion in range."""
    result = session.execute(
        text("""
            SELECT measurement_name, value_numeric, reference_range_low, reference_range_high
            FROM clinical.measurements
            WHERE patient_id = :pid AND value_numeric IS NOT NULL
            ORDER BY measured_at DESC
            LIMIT 20
        """),
        {"pid": patient_id},
    )
    measurements = result.fetchall()
    if not measurements:
        return None

    in_range = 0
    total = 0
    for m in measurements:
        if m.reference_range_low is not None and m.reference_range_high is not None:
            total += 1
            if m.reference_range_low <= m.value_numeric <= m.reference_range_high:
                in_range += 1

    if total == 0:
        return 0.5  # no reference ranges available

    return round(in_range / total, 4)


def _disease_stability(session: Any, patient_id: int) -> float | None:
    """Fewer active/new conditions = higher stability."""
    result = session.execute(
        text("""
            SELECT status, COUNT(*) as cnt
            FROM clinical.conditions
            WHERE patient_id = :pid
            GROUP BY status
        """),
        {"pid": patient_id},
    )
    rows = result.fetchall()
    if not rows:
        return None

    status_counts = {row.status: row.cnt for row in rows}
    total = sum(status_counts.values())
    active = status_counts.get("active", 0)
    resolved = status_counts.get("resolved", 0)

    if total == 0:
        return None

    return round((resolved + 0.5 * (total - active - resolved)) / total, 4)


def _care_intensity(session: Any, patient_id: int) -> float | None:
    """Lower care intensity = better. Score = 1 - normalized_intensity."""
    result = session.execute(
        text("""
            SELECT visit_type, COUNT(*) as cnt
            FROM clinical.visits
            WHERE patient_id = :pid
            GROUP BY visit_type
        """),
        {"pid": patient_id},
    )
    rows = result.fetchall()
    if not rows:
        return None

    type_counts = {row.visit_type: row.cnt for row in rows}
    emergency = type_counts.get("emergency", 0)
    inpatient = type_counts.get("inpatient", 0)
    outpatient = type_counts.get("outpatient", 0)

    # Weighted intensity score (higher = more intensive care)
    intensity = emergency * 3 + inpatient * 2 + outpatient * 0.5
    # Normalize: typical patient might have intensity ~5
    normalized = min(intensity / 10.0, 1.0)

    return round(1.0 - normalized, 4)
