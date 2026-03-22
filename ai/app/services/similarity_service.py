"""
Similarity search -- finds patients with similar clinical profiles.

Uses pgvector cosine distance for ANN search, then re-ranks with
domain-specific weights for clinical relevance.
"""

import logging
from dataclasses import dataclass, field
from typing import Any

from sqlalchemy import text

from app.db import get_session

logger = logging.getLogger(__name__)

# Domain weights for clinical re-ranking.
# These reflect how much each clinical domain contributes to
# determining whether two patients are truly "similar" in a
# clinically meaningful way.
DOMAIN_WEIGHTS: dict[str, float] = {
    "diagnosis": 0.30,
    "genomics": 0.25,
    "treatment": 0.20,
    "labs": 0.15,
    "demographics": 0.10,
}


@dataclass
class SimilarPatient:
    """A patient returned from a similarity search with relevance details."""

    patient_id: int
    similarity_score: float
    shared_conditions: list[str] = field(default_factory=list)
    shared_medications: list[str] = field(default_factory=list)
    key_differences: list[str] = field(default_factory=list)
    outcome_summary: str | None = None
    domain_scores: dict[str, float] = field(default_factory=dict)

    def to_dict(self) -> dict[str, Any]:
        return {
            "patient_id": self.patient_id,
            "similarity_score": round(self.similarity_score, 4),
            "shared_conditions": self.shared_conditions,
            "shared_medications": self.shared_medications,
            "key_differences": self.key_differences,
            "outcome_summary": self.outcome_summary,
            "domain_scores": {k: round(v, 4) for k, v in self.domain_scores.items()},
        }


def _fetch_patient_clinical_sets(patient_id: int) -> dict[str, Any]:
    """Fetch clinical data sets for domain-level comparison.

    Returns conditions, medications, genomic observations, lab names,
    and demographics for a single patient.
    """
    with get_session() as session:
        # Demographics
        demo = session.execute(
            text("""
                SELECT date_of_birth, gender
                FROM clinical.patients
                WHERE id = :pid
            """),
            {"pid": patient_id},
        ).fetchone()

        # Conditions
        conditions = {
            r.condition_name
            for r in session.execute(
                text("""
                    SELECT DISTINCT condition_name
                    FROM clinical.conditions
                    WHERE patient_id = :pid
                """),
                {"pid": patient_id},
            ).fetchall()
        }

        # Medications
        medications = {
            r.medication_name
            for r in session.execute(
                text("""
                    SELECT DISTINCT medication_name
                    FROM clinical.medications
                    WHERE patient_id = :pid
                """),
                {"pid": patient_id},
            ).fetchall()
        }

        # Genomic observations
        genomics = {
            r.observation_name
            for r in session.execute(
                text("""
                    SELECT DISTINCT observation_name
                    FROM clinical.observations
                    WHERE patient_id = :pid AND category = 'genomic'
                """),
                {"pid": patient_id},
            ).fetchall()
        }

        # Lab measurement names
        labs = {
            r.measurement_name
            for r in session.execute(
                text("""
                    SELECT DISTINCT measurement_name
                    FROM clinical.measurements
                    WHERE patient_id = :pid
                """),
                {"pid": patient_id},
            ).fetchall()
        }

    age = None
    gender = None
    if demo:
        gender = demo.gender
        if demo.date_of_birth:
            from datetime import datetime

            today = datetime.now().date()
            dob = demo.date_of_birth
            age = today.year - dob.year - (
                (today.month, today.day) < (dob.month, dob.day)
            )

    return {
        "conditions": conditions,
        "medications": medications,
        "genomics": genomics,
        "labs": labs,
        "age": age,
        "gender": gender,
    }


def _jaccard_similarity(set_a: set[str], set_b: set[str]) -> float:
    """Compute Jaccard similarity between two sets."""
    if not set_a and not set_b:
        return 1.0
    if not set_a or not set_b:
        return 0.0
    intersection = set_a & set_b
    union = set_a | set_b
    return len(intersection) / len(union)


def compute_domain_similarity(
    patient_a: dict[str, Any], patient_b: dict[str, Any]
) -> dict[str, float]:
    """Compute per-domain similarity scores between two patients.

    Returns a dict with scores for each domain (0.0-1.0).
    """
    scores: dict[str, float] = {}

    # Diagnosis similarity (Jaccard on condition sets)
    scores["diagnosis"] = _jaccard_similarity(
        patient_a.get("conditions", set()),
        patient_b.get("conditions", set()),
    )

    # Genomics similarity (Jaccard on genomic observation sets)
    scores["genomics"] = _jaccard_similarity(
        patient_a.get("genomics", set()),
        patient_b.get("genomics", set()),
    )

    # Treatment similarity (Jaccard on medication sets)
    scores["treatment"] = _jaccard_similarity(
        patient_a.get("medications", set()),
        patient_b.get("medications", set()),
    )

    # Labs similarity (Jaccard on lab name sets)
    scores["labs"] = _jaccard_similarity(
        patient_a.get("labs", set()),
        patient_b.get("labs", set()),
    )

    # Demographics similarity (age proximity + gender match)
    demo_score = 0.0
    age_a = patient_a.get("age")
    age_b = patient_b.get("age")
    gender_a = patient_a.get("gender")
    gender_b = patient_b.get("gender")

    gender_match = 0.5 if (gender_a and gender_b and gender_a == gender_b) else 0.0
    age_proximity = 0.0
    if age_a is not None and age_b is not None:
        age_diff = abs(age_a - age_b)
        # Full score if within 5 years, linear decay to 0 at 30 years
        age_proximity = max(0.0, 1.0 - age_diff / 30.0) * 0.5

    demo_score = gender_match + age_proximity
    scores["demographics"] = min(demo_score, 1.0)

    return scores


def _compute_weighted_score(
    embedding_score: float, domain_scores: dict[str, float]
) -> float:
    """Combine embedding cosine similarity with domain-specific scores.

    The embedding score contributes 50% of the final score, and the
    domain-weighted re-ranking contributes the other 50%.
    """
    domain_weighted = sum(
        DOMAIN_WEIGHTS.get(domain, 0.0) * score
        for domain, score in domain_scores.items()
    )

    # 50% embedding + 50% domain re-ranking
    return 0.5 * embedding_score + 0.5 * domain_weighted


def _identify_differences(
    data_a: dict[str, Any], data_b: dict[str, Any]
) -> list[str]:
    """Identify key clinical differences between two patients."""
    differences: list[str] = []

    # Conditions unique to patient B
    unique_conditions = data_b.get("conditions", set()) - data_a.get("conditions", set())
    if unique_conditions:
        conditions_list = sorted(unique_conditions)[:3]
        differences.append(f"Additional conditions: {', '.join(conditions_list)}")

    # Medications unique to patient B
    unique_meds = data_b.get("medications", set()) - data_a.get("medications", set())
    if unique_meds:
        meds_list = sorted(unique_meds)[:3]
        differences.append(f"Different medications: {', '.join(meds_list)}")

    # Genomic differences
    unique_genomics = data_b.get("genomics", set()) - data_a.get("genomics", set())
    if unique_genomics:
        genomics_list = sorted(unique_genomics)[:3]
        differences.append(f"Genomic differences: {', '.join(genomics_list)}")

    # Age difference
    age_a = data_a.get("age")
    age_b = data_b.get("age")
    if age_a is not None and age_b is not None:
        diff = abs(age_a - age_b)
        if diff > 10:
            differences.append(f"Age difference: {diff} years")

    return differences


def _fetch_outcome_summary(patient_id: int) -> str | None:
    """Fetch a brief outcome summary for a patient from their notes."""
    with get_session() as session:
        row = session.execute(
            text("""
                SELECT note_text
                FROM clinical.notes
                WHERE patient_id = :pid
                    AND note_type = 'outcome'
                ORDER BY note_date DESC NULLS LAST
                LIMIT 1
            """),
            {"pid": patient_id},
        ).fetchone()

    if row and row.note_text:
        # Return first 200 chars as summary
        summary = row.note_text[:200]
        if len(row.note_text) > 200:
            summary += "..."
        return summary

    return None


def _build_filter_clauses(filters: dict[str, Any]) -> tuple[str, dict[str, Any]]:
    """Build SQL WHERE clauses from filter parameters.

    Returns (sql_fragment, params_dict).
    """
    clauses: list[str] = []
    params: dict[str, Any] = {}

    # Age range filter
    age_range = filters.get("age_range")
    if age_range:
        if isinstance(age_range, dict):
            min_age = age_range.get("min")
            max_age = age_range.get("max")
        elif isinstance(age_range, (list, tuple)) and len(age_range) == 2:
            min_age, max_age = age_range
        else:
            min_age, max_age = None, None

        if min_age is not None:
            clauses.append(
                "EXTRACT(YEAR FROM AGE(p.date_of_birth)) >= :min_age"
            )
            params["min_age"] = min_age
        if max_age is not None:
            clauses.append(
                "EXTRACT(YEAR FROM AGE(p.date_of_birth)) <= :max_age"
            )
            params["max_age"] = max_age

    # Condition filter — patient must have at least one of these conditions
    condition_filter = filters.get("conditions")
    if condition_filter and isinstance(condition_filter, list):
        placeholders = ", ".join(
            f":cond_{i}" for i in range(len(condition_filter))
        )
        clauses.append(f"""
            EXISTS (
                SELECT 1 FROM clinical.conditions c
                WHERE c.patient_id = p.id
                AND c.condition_name IN ({placeholders})
            )
        """)
        for i, cond in enumerate(condition_filter):
            params[f"cond_{i}"] = cond

    # Genomic filter — patient must have at least one of these variants
    genomic_filter = filters.get("genomics")
    if genomic_filter and isinstance(genomic_filter, list):
        placeholders = ", ".join(
            f":gen_{i}" for i in range(len(genomic_filter))
        )
        clauses.append(f"""
            EXISTS (
                SELECT 1 FROM clinical.observations o
                WHERE o.patient_id = p.id
                AND o.category = 'genomic'
                AND o.observation_name IN ({placeholders})
            )
        """)
        for i, gen in enumerate(genomic_filter):
            params[f"gen_{i}"] = gen

    sql_fragment = ""
    if clauses:
        sql_fragment = "AND " + " AND ".join(clauses)

    return sql_fragment, params


def search_by_embedding(
    embedding: list[float],
    top_k: int = 20,
    filters: dict[str, Any] | None = None,
    exclude_patient_id: int | None = None,
) -> list[dict[str, Any]]:
    """Search for similar patients by embedding vector using pgvector cosine distance.

    Returns raw results (patient_id, cosine_similarity) before domain re-ranking.
    """
    embedding_str = "[" + ",".join(str(x) for x in embedding) + "]"

    filter_sql = ""
    filter_params: dict[str, Any] = {}
    if filters:
        filter_sql, filter_params = _build_filter_clauses(filters)

    exclude_sql = ""
    if exclude_patient_id is not None:
        exclude_sql = "AND pe.patient_id != :exclude_pid"
        filter_params["exclude_pid"] = exclude_patient_id

    query = f"""
        SELECT
            pe.patient_id,
            1 - (pe.embedding <=> :embedding::vector) AS cosine_similarity
        FROM clinical.patient_embeddings pe
        JOIN clinical.patients p ON p.id = pe.patient_id
        WHERE 1=1
            {exclude_sql}
            {filter_sql}
        ORDER BY pe.embedding <=> :embedding::vector
        LIMIT :top_k
    """

    params = {"embedding": embedding_str, "top_k": top_k, **filter_params}

    with get_session() as session:
        rows = session.execute(text(query), params).fetchall()

    return [
        {
            "patient_id": row.patient_id,
            "cosine_similarity": float(row.cosine_similarity),
        }
        for row in rows
    ]


def search_similar(
    patient_id: int,
    top_k: int = 20,
    filters: dict[str, Any] | None = None,
) -> list[SimilarPatient]:
    """Find patients clinically similar to the given patient.

    Pipeline:
    1. Fetch the patient's stored embedding
    2. Query pgvector for top-N candidates by cosine distance
    3. Fetch clinical data for the query patient and each candidate
    4. Compute domain-specific similarity scores
    5. Re-rank by weighted combination of embedding + domain scores
    6. Return enriched results with shared conditions, differences, etc.
    """
    # Fetch the query patient's embedding
    with get_session() as session:
        row = session.execute(
            text("""
                SELECT embedding::text
                FROM clinical.patient_embeddings
                WHERE patient_id = :pid
            """),
            {"pid": patient_id},
        ).fetchone()

    if row is None:
        raise ValueError(
            f"Patient {patient_id} has no embedding. "
            "Run /similarity/embed first."
        )

    # Parse the embedding from the text representation
    embedding_text = row[0]  # "[0.1,0.2,...]"
    embedding = [
        float(x) for x in embedding_text.strip("[]").split(",")
    ]

    # Fetch more candidates than needed for re-ranking headroom
    candidate_k = min(top_k * 3, 100)
    raw_results = search_by_embedding(
        embedding,
        top_k=candidate_k,
        filters=filters,
        exclude_patient_id=patient_id,
    )

    if not raw_results:
        return []

    # Fetch clinical data for the query patient
    query_data = _fetch_patient_clinical_sets(patient_id)

    # Re-rank with domain-specific scores
    enriched: list[SimilarPatient] = []
    for result in raw_results:
        cand_pid = result["patient_id"]
        cosine_sim = result["cosine_similarity"]

        try:
            cand_data = _fetch_patient_clinical_sets(cand_pid)
        except Exception as e:
            logger.warning("Failed to fetch data for patient %d: %s", cand_pid, e)
            continue

        domain_scores = compute_domain_similarity(query_data, cand_data)
        final_score = _compute_weighted_score(cosine_sim, domain_scores)

        shared_conditions = sorted(
            query_data.get("conditions", set()) & cand_data.get("conditions", set())
        )
        shared_medications = sorted(
            query_data.get("medications", set()) & cand_data.get("medications", set())
        )
        key_differences = _identify_differences(query_data, cand_data)
        outcome_summary = _fetch_outcome_summary(cand_pid)

        enriched.append(
            SimilarPatient(
                patient_id=cand_pid,
                similarity_score=final_score,
                shared_conditions=shared_conditions,
                shared_medications=shared_medications,
                key_differences=key_differences,
                outcome_summary=outcome_summary,
                domain_scores=domain_scores,
            )
        )

    # Sort by final score descending and trim to top_k
    enriched.sort(key=lambda sp: sp.similarity_score, reverse=True)
    return enriched[:top_k]


def get_embedding_stats() -> dict[str, Any]:
    """Return statistics about embedding coverage."""
    with get_session() as session:
        total_patients = session.execute(
            text("SELECT COUNT(*) FROM clinical.patients")
        ).scalar() or 0

        embedded_patients = session.execute(
            text("SELECT COUNT(DISTINCT patient_id) FROM clinical.patient_embeddings")
        ).scalar() or 0

        model_counts = session.execute(
            text("""
                SELECT model_name, COUNT(*) as cnt
                FROM clinical.patient_embeddings
                GROUP BY model_name
                ORDER BY cnt DESC
            """)
        ).fetchall()

        oldest = session.execute(
            text("""
                SELECT MIN(created_at) FROM clinical.patient_embeddings
            """)
        ).scalar()

        newest = session.execute(
            text("""
                SELECT MAX(created_at) FROM clinical.patient_embeddings
            """)
        ).scalar()

    coverage = (embedded_patients / total_patients * 100) if total_patients > 0 else 0.0

    return {
        "total_patients": total_patients,
        "embedded_patients": embedded_patients,
        "coverage_pct": round(coverage, 1),
        "models": {r.model_name: r.cnt for r in model_counts},
        "oldest_embedding": str(oldest) if oldest else None,
        "newest_embedding": str(newest) if newest else None,
    }
