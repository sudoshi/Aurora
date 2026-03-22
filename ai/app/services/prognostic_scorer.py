"""Prognostic scorer service — calculates validated clinical prognostic scores.

Implements ECOG and Charlson Comorbidity Index algorithmically.
Falls back to Ollama for complex risk stratification.
"""

import logging

from app.models.decision_support import PrognosticScore
from app.services.llm_utils import call_ollama_json

logger = logging.getLogger(__name__)

SYSTEM_PROMPT = (
    "You are a clinical oncology prognostics expert. Given patient data, "
    "provide a risk stratification assessment with clear rationale. "
    "Reference validated scoring systems where applicable."
)

# Charlson Comorbidity Index weights keyed by condition category.
# Each key maps to a set of common condition descriptors and the CCI weight.
_CCI_WEIGHTS: dict[str, tuple[set[str], int]] = {
    "mi": ({"myocardial infarction", "mi", "heart attack"}, 1),
    "chf": ({"congestive heart failure", "chf", "heart failure"}, 1),
    "pvd": ({"peripheral vascular disease", "pvd"}, 1),
    "cva": ({"cerebrovascular disease", "cva", "stroke", "tia"}, 1),
    "dementia": ({"dementia", "alzheimer"}, 1),
    "copd": ({"chronic pulmonary disease", "copd", "emphysema", "chronic bronchitis"}, 1),
    "connective_tissue": ({"connective tissue disease", "lupus", "sle", "rheumatoid arthritis"}, 1),
    "ulcer": ({"peptic ulcer", "ulcer disease"}, 1),
    "mild_liver": ({"mild liver disease", "chronic hepatitis"}, 1),
    "diabetes_uncomplicated": ({"diabetes", "diabetes mellitus"}, 1),
    "diabetes_complicated": ({"diabetes with complications", "diabetic nephropathy", "diabetic retinopathy"}, 2),
    "hemiplegia": ({"hemiplegia", "paraplegia"}, 2),
    "renal": ({"moderate to severe renal disease", "chronic kidney disease", "ckd", "dialysis"}, 2),
    "malignancy": ({"malignancy", "cancer", "tumor", "lymphoma", "leukemia"}, 2),
    "moderate_severe_liver": ({"moderate to severe liver disease", "cirrhosis", "liver failure"}, 3),
    "metastatic": ({"metastatic solid tumor", "metastatic cancer", "metastatic"}, 6),
    "aids": ({"aids", "hiv/aids"}, 6),
}


def calculate_ecog(ecog_value: int) -> PrognosticScore:
    """Calculate ECOG Performance Status score.

    ECOG scale:
        0 — Fully active
        1 — Restricted in strenuous activity
        2 — Ambulatory, capable of self-care, up >50% waking hours
        3 — Capable of limited self-care, confined >50% waking hours
        4 — Completely disabled
        5 — Dead
    """
    ecog_value = max(0, min(5, ecog_value))

    interpretations = {
        0: "Fully active, able to carry on all pre-disease performance without restriction.",
        1: "Restricted in physically strenuous activity but ambulatory and able to carry out light work.",
        2: "Ambulatory and capable of all self-care but unable to carry out any work activities; up and about more than 50% of waking hours.",
        3: "Capable of only limited self-care; confined to bed or chair more than 50% of waking hours.",
        4: "Completely disabled; cannot carry on any self-care; totally confined to bed or chair.",
        5: "Dead.",
    }

    if ecog_value <= 1:
        category = "low_risk"
    elif ecog_value == 2:
        category = "intermediate"
    else:
        category = "high_risk"

    return PrognosticScore(
        score_name="ECOG Performance Status",
        value=float(ecog_value),
        interpretation=interpretations[ecog_value],
        category=category,
        components={"ecog_grade": ecog_value},
    )


def calculate_charlson(conditions: list[str], age: int | None = None) -> PrognosticScore:
    """Calculate Charlson Comorbidity Index from condition list.

    Args:
        conditions: List of condition descriptions/names.
        age: Patient age (adds age-based points if >= 50).

    Returns:
        PrognosticScore with CCI value and interpretation.
    """
    total = 0
    components: dict[str, float | int | str] = {}
    matched_categories: set[str] = set()

    lowered = [c.lower().strip() for c in conditions]

    for category, (terms, weight) in _CCI_WEIGHTS.items():
        for condition in lowered:
            if any(term in condition for term in terms):
                if category not in matched_categories:
                    matched_categories.add(category)
                    total += weight
                    components[category] = weight
                break

    # Age adjustment: 1 point per decade over 40 (some variants use 50).
    if age is not None and age >= 50:
        age_points = (age - 40) // 10
        total += age_points
        components["age_adjustment"] = age_points

    if total == 0:
        interpretation = "No significant comorbidity burden."
        category_str = "low_risk"
    elif total <= 2:
        interpretation = "Mild comorbidity burden. Generally favorable prognosis."
        category_str = "low_risk"
    elif total <= 4:
        interpretation = "Moderate comorbidity burden. May affect treatment tolerance."
        category_str = "intermediate"
    else:
        interpretation = "Severe comorbidity burden. Significant impact on prognosis and treatment decisions."
        category_str = "high_risk"

    components["total_score"] = total

    return PrognosticScore(
        score_name="Charlson Comorbidity Index",
        value=float(total),
        interpretation=interpretation,
        category=category_str,
        components=components,
    )


async def _llm_risk_stratification(patient_data: dict) -> PrognosticScore | None:
    """Use Ollama for generic risk stratification when standard scores don't apply."""
    data_summary = "\n".join(f"{k}: {v}" for k, v in patient_data.items())

    prompt = f"""Given this patient data, provide a risk stratification assessment.

Patient Data:
{data_summary}

Respond in JSON with this exact structure:
{{
  "score_name": "Risk Stratification Assessment",
  "value": a numeric risk score 0-10,
  "interpretation": "explanation of risk level",
  "category": "low_risk or intermediate or high_risk",
  "components": {{"factor1": "value1", "factor2": "value2"}}
}}"""

    data = await call_ollama_json(prompt, system=SYSTEM_PROMPT)

    if not data or "interpretation" not in data:
        return None

    category = str(data.get("category", "intermediate")).lower()
    if category not in ("low_risk", "intermediate", "high_risk"):
        category = "intermediate"

    try:
        value = float(data.get("value", 5))
    except (ValueError, TypeError):
        value = 5.0

    raw_components = data.get("components", {})
    components: dict[str, float | int | str] = {}
    for k, v in raw_components.items():
        if isinstance(v, (int, float)):
            components[str(k)] = v
        else:
            components[str(k)] = str(v)

    return PrognosticScore(
        score_name=str(data.get("score_name", "Risk Stratification Assessment")),
        value=value,
        interpretation=str(data.get("interpretation", "")),
        category=category,
        components=components,
    )


async def calculate_scores(patient_data: dict) -> list[PrognosticScore]:
    """Calculate all applicable prognostic scores for a patient.

    Runs algorithmic scores (ECOG, CCI) first, then falls back to LLM for
    generic risk stratification.

    Args:
        patient_data: Dict with keys like 'ecog', 'conditions', 'age', etc.

    Returns:
        List of prognostic scores.
    """
    scores: list[PrognosticScore] = []

    # ECOG if provided
    ecog_raw = patient_data.get("ecog")
    if ecog_raw is not None:
        try:
            scores.append(calculate_ecog(int(ecog_raw)))
        except (ValueError, TypeError):
            logger.warning("Invalid ECOG value: %s", ecog_raw)

    # Charlson Comorbidity Index if conditions provided
    conditions = patient_data.get("conditions", [])
    if isinstance(conditions, list) and conditions:
        age = None
        if "age" in patient_data:
            try:
                age = int(patient_data["age"])
            except (ValueError, TypeError):
                pass
        scores.append(calculate_charlson(conditions, age))

    # LLM-based risk stratification for complex cases
    try:
        llm_score = await _llm_risk_stratification(patient_data)
        if llm_score is not None:
            scores.append(llm_score)
    except Exception as exc:
        logger.error("LLM risk stratification failed: %s", exc)

    return scores
