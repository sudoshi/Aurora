"""Pydantic models for decision support services."""

from pydantic import BaseModel, Field

# --- Advisory grading constants ---
#
# The decision-support endpoints (trial-match, guidelines, drug-interactions,
# variant-interpret, prognosis, rare-disease, genomic-briefing) are powered by
# Ollama LLM reasoning with NO backing knowledge base. Their outputs are
# second-opinion / advisory only and must never be mistaken for
# database-verified clinical decision support. Every response model below carries
# an explicit evidence grade + disclaimer so consumers can label them correctly.

LLM_ADVISORY_GRADE = "llm_advisory"
LLM_ADVISORY_DISCLAIMER = (
    "AI-generated advisory output from a language model with no backing "
    "knowledge base. Not database-verified clinical decision support — verify "
    "independently before any clinical use."
)

# --- AI availability status ---
#
# A machine-readable signal of whether the LLM actually produced this response.
# "ok"       -> the Ollama/LLM call succeeded and the content is model output.
# "degraded" -> the LLM was unavailable; structured fallback content is returned
#               so the endpoint still serves usable data, but the narrative /
#               reasoning portion is NOT a real model result. Callers MUST treat
#               degraded output as a placeholder, not a clinical inference.
AI_STATUS_OK = "ok"
AI_STATUS_DEGRADED = "degraded"


# --- Trial Matching ---


class TrialMatchRequest(BaseModel):
    patient_id: int
    condition_focus: str | None = None
    diagnosis: str | None = None
    stage: str | None = None
    prior_treatments: list[str] = Field(default_factory=list)
    biomarkers: dict[str, str] = Field(default_factory=dict)
    age: int | None = None
    sex: str | None = None


class TrialSuggestion(BaseModel):
    trial_type: str
    rationale: str
    key_criteria_met: list[str]
    potential_exclusions: list[str]
    confidence: str = Field(..., pattern=r"^(high|medium|low)$")


class TrialMatchResponse(BaseModel):
    patient_id: int
    suggestions: list[TrialSuggestion]
    error: str | None = None
    evidence_grade: str = LLM_ADVISORY_GRADE
    disclaimer: str = LLM_ADVISORY_DISCLAIMER
    ai_status: str = AI_STATUS_OK


# --- Guideline Checker ---


class GuidelineCheckRequest(BaseModel):
    recommendation: str = Field(..., min_length=1, max_length=5000)
    patient_context: dict = Field(default_factory=dict)
    guideline: str | None = None


class ConcordanceResult(BaseModel):
    concordant: bool
    guideline_referenced: str
    supporting_evidence: list[str]
    concerns: list[str]
    alternative_recommendations: list[str]
    confidence: str = Field(..., pattern=r"^(high|medium|low)$")


class GuidelineCheckResponse(BaseModel):
    result: ConcordanceResult | None = None
    error: str | None = None
    evidence_grade: str = LLM_ADVISORY_GRADE
    disclaimer: str = LLM_ADVISORY_DISCLAIMER
    ai_status: str = AI_STATUS_OK


# --- Drug Interaction Checker ---


class DrugInteractionRequest(BaseModel):
    medications: list[str] = Field(..., min_length=1)
    proposed_medication: str | None = None


class DrugInteraction(BaseModel):
    drug_a: str
    drug_b: str
    severity: str = Field(..., pattern=r"^(major|moderate|minor)$")
    mechanism: str
    clinical_significance: str
    recommendation: str


class DrugInteractionResponse(BaseModel):
    interactions: list[DrugInteraction]
    error: str | None = None
    evidence_grade: str = LLM_ADVISORY_GRADE
    disclaimer: str = LLM_ADVISORY_DISCLAIMER
    ai_status: str = AI_STATUS_OK


# --- Variant Interpreter ---


class VariantInterpretRequest(BaseModel):
    gene: str = Field(..., min_length=1, max_length=50)
    variant: str = Field(..., min_length=1, max_length=100)
    cancer_type: str | None = None


class VariantInterpretation(BaseModel):
    gene: str
    variant: str
    classification: str = Field(
        ...,
        pattern=r"^(pathogenic|likely_pathogenic|vus|likely_benign|benign)$",
    )
    clinical_significance: str
    actionable: bool
    targeted_therapies: list[str]
    clinical_trials: list[str]
    references: list[str]


class VariantInterpretResponse(BaseModel):
    interpretation: VariantInterpretation | None = None
    error: str | None = None
    evidence_grade: str = LLM_ADVISORY_GRADE
    disclaimer: str = LLM_ADVISORY_DISCLAIMER
    ai_status: str = AI_STATUS_OK


# --- Prognostic Scorer ---


class PrognosticScoreRequest(BaseModel):
    patient_data: dict = Field(default_factory=dict)


class PrognosticScore(BaseModel):
    score_name: str
    value: float
    interpretation: str
    category: str = Field(..., pattern=r"^(low_risk|intermediate|high_risk)$")
    components: dict[str, float | int | str]


class PrognosticScoreResponse(BaseModel):
    # NOTE: This response can mix deterministic rule-based scores (ECOG,
    # Charlson Comorbidity Index — computed algorithmically) with an Ollama LLM
    # risk-stratification fallback (see prognostic_scorer.calculate_scores).
    # Because the response as a whole may contain LLM-derived output, it is
    # graded as advisory — a consumer cannot treat the full payload as
    # database-verified.
    scores: list[PrognosticScore]
    error: str | None = None
    evidence_grade: str = LLM_ADVISORY_GRADE
    disclaimer: str = LLM_ADVISORY_DISCLAIMER
    ai_status: str = AI_STATUS_OK


# --- Rare Disease Matcher ---


class RareDiseaseMatchRequest(BaseModel):
    symptoms: list[str] = Field(..., min_length=1)
    patient_context: dict | None = None


class RareDiseaseMatch(BaseModel):
    disease_name: str
    omim_id: str | None = None
    confidence: str = Field(..., pattern=r"^(high|medium|low)$")
    matching_features: list[str]
    distinguishing_features: list[str]
    recommended_workup: list[str]
    genetic_testing: list[str]


class RareDiseaseMatchResponse(BaseModel):
    matches: list[RareDiseaseMatch]
    error: str | None = None
    evidence_grade: str = LLM_ADVISORY_GRADE
    disclaimer: str = LLM_ADVISORY_DISCLAIMER
    ai_status: str = AI_STATUS_OK


# --- Genomic Briefing ---


class VariantSummary(BaseModel):
    gene: str
    variant: str
    classification: str
    evidence_level: str | None = None
    therapies: list[str] = Field(default_factory=list)


class DrugExposureSummary(BaseModel):
    drug_name: str
    start_date: str | None = None
    end_date: str | None = None


class InteractionSummary(BaseModel):
    gene: str
    drug: str
    relationship: str
    evidence_level: str
    mechanism: str | None = None


class GenomicBriefingRequest(BaseModel):
    patient_id: int
    variants: list[VariantSummary] = Field(default_factory=list)
    drug_exposures: list[DrugExposureSummary] = Field(default_factory=list)
    interactions: list[InteractionSummary] = Field(default_factory=list)
    total_variant_count: int = 0


class GenomicBriefingResponse(BaseModel):
    briefing: str = ""
    generated_at: str = ""
    variant_count: int = 0
    actionable_count: int = 0
    error: str | None = None
    evidence_grade: str = LLM_ADVISORY_GRADE
    disclaimer: str = LLM_ADVISORY_DISCLAIMER
    ai_status: str = AI_STATUS_OK
