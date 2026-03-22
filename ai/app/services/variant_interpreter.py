"""Variant interpreter service — interprets genomic variants in clinical context."""

import logging

from app.models.decision_support import VariantInterpretation
from app.services.llm_utils import call_ollama_json

logger = logging.getLogger(__name__)

SYSTEM_PROMPT = (
    "You are a molecular oncology and genomics expert. Interpret the clinical "
    "significance of genomic variants, classify them according to AMP/ASCO/CAP "
    "guidelines, and identify actionable therapeutic implications. "
    "Reference relevant targeted therapies and clinical trials."
)


async def interpret_variant(
    gene: str,
    variant: str,
    cancer_type: str | None = None,
) -> VariantInterpretation:
    """Interpret a genomic variant in clinical context.

    Args:
        gene: Gene symbol (e.g., EGFR, BRAF).
        variant: Variant notation (e.g., L858R, V600E).
        cancer_type: Optional cancer type for context.

    Returns:
        VariantInterpretation with classification and actionability.

    Raises:
        Exception: Propagated from Ollama if service is unavailable.
    """
    cancer_context = (
        f"\nCancer type: {cancer_type}" if cancer_type else ""
    )

    prompt = f"""Interpret this genomic variant in a clinical oncology context.

Gene: {gene}
Variant: {variant}{cancer_context}

Respond in JSON with this exact structure:
{{
  "classification": "pathogenic or likely_pathogenic or vus or likely_benign or benign",
  "clinical_significance": "what this variant means for the patient",
  "actionable": true or false,
  "targeted_therapies": ["drug 1", "drug 2"],
  "clinical_trials": ["relevant trial type 1"],
  "references": ["guideline or source 1"]
}}"""

    data = await call_ollama_json(prompt, system=SYSTEM_PROMPT)

    classification = str(data.get("classification", "vus")).lower()
    valid_classifications = (
        "pathogenic",
        "likely_pathogenic",
        "vus",
        "likely_benign",
        "benign",
    )
    if classification not in valid_classifications:
        classification = "vus"

    return VariantInterpretation(
        gene=gene,
        variant=variant,
        classification=classification,
        clinical_significance=str(
            data.get("clinical_significance", "Unable to determine")
        ),
        actionable=bool(data.get("actionable", False)),
        targeted_therapies=[
            str(t) for t in data.get("targeted_therapies", [])
        ],
        clinical_trials=[
            str(t) for t in data.get("clinical_trials", [])
        ],
        references=[str(r) for r in data.get("references", [])],
    )
