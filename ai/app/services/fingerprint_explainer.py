"""Generate natural language similarity explanations using Ollama."""

import logging
from typing import Any

from sqlalchemy import text

from app.db import get_session
from app.services.ollama_client import generate_concept_mapping

logger = logging.getLogger(__name__)


async def explain_similarity(
    query_patient_id: int,
    similar_patient_ids: list[int],
) -> list[str | None]:
    """Generate a brief explanation for each similar patient pair.

    Returns a list of explanation strings (one per similar patient).
    """
    explanations: list[str | None] = []

    with get_session() as session:
        query_context = _get_patient_context(session, query_patient_id)

        for pid in similar_patient_ids:
            try:
                similar_context = _get_patient_context(session, pid)
                explanation = await _generate_explanation(query_context, similar_context)
                explanations.append(explanation)
            except Exception as exc:
                logger.warning("Explanation failed for patient %d: %s", pid, exc)
                explanations.append(None)

    return explanations


def _get_patient_context(session: Any, patient_id: int) -> dict[str, Any]:
    """Fetch key clinical facts for explanation generation."""
    # Conditions
    result = session.execute(
        text("SELECT concept_name, domain, status FROM clinical.conditions WHERE patient_id = :pid LIMIT 5"),
        {"pid": patient_id},
    )
    conditions = [{"name": r.concept_name, "domain": r.domain, "status": r.status} for r in result.fetchall()]

    # Key variants
    result = session.execute(
        text("SELECT gene, variant, clinical_significance FROM clinical.genomic_variants WHERE patient_id = :pid ORDER BY clinical_significance LIMIT 5"),
        {"pid": patient_id},
    )
    variants = [{"gene": r.gene, "variant": r.variant, "significance": r.clinical_significance} for r in result.fetchall()]

    # Top medications
    result = session.execute(
        text("SELECT drug_name, status FROM clinical.medications WHERE patient_id = :pid LIMIT 5"),
        {"pid": patient_id},
    )
    medications = [{"drug": r.drug_name, "status": r.status} for r in result.fetchall()]

    return {
        "patient_id": patient_id,
        "conditions": conditions,
        "variants": variants,
        "medications": medications,
    }


async def _generate_explanation(
    query: dict[str, Any],
    similar: dict[str, Any],
) -> str:
    """Use Ollama to generate a brief similarity explanation."""
    prompt = f"""Compare these two patients and explain why they are similar in 1-2 clinical sentences.
Focus on shared mutations, conditions, and treatments. Be concise and clinically relevant.

Patient A (query):
- Conditions: {', '.join(c['name'] for c in query['conditions'])}
- Variants: {', '.join(f"{v['gene']} {v['variant'] or ''} ({v['significance']})" for v in query['variants'])}
- Medications: {', '.join(m['drug'] for m in query['medications'])}

Patient B (similar):
- Conditions: {', '.join(c['name'] for c in similar['conditions'])}
- Variants: {', '.join(f"{v['gene']} {v['variant'] or ''} ({v['significance']})" for v in similar['variants'])}
- Medications: {', '.join(m['drug'] for m in similar['medications'])}

Explanation:"""

    try:
        result = await generate_concept_mapping(prompt, context="patient similarity explanation")
        # generate_concept_mapping returns dict with 'reasoning' as the narrative text
        explanation = result.get("reasoning", result.get("mapping", result.get("result", str(result))))
        # Clean up: take just the first 2 sentences if too long
        sentences = explanation.strip().split(". ")
        clean = ". ".join(sentences[:2])
        if not clean.endswith("."):
            clean += "."
        return clean
    except Exception:
        # Fallback: deterministic text-based explanation
        shared_genes = {v["gene"] for v in query["variants"]} & {v["gene"] for v in similar["variants"]}
        shared_drugs = {m["drug"] for m in query["medications"]} & {m["drug"] for m in similar["medications"]}

        parts = []
        if shared_genes:
            parts.append(f"Shared mutations in {', '.join(shared_genes)}")
        if shared_drugs:
            parts.append(f"Both treated with {', '.join(shared_drugs)}")
        if not parts:
            parts.append("Similar clinical trajectory")

        return ". ".join(parts) + "."
