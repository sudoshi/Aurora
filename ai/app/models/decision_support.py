"""Pydantic models for decision support services."""

from pydantic import BaseModel, Field


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


# --- Prognostic Scorer ---


class PrognosticScoreRequest(BaseModel):
    patient_data: dict = Field(default_factory=dict)


class PrognosticScore(BaseModel):
    score_name: str
    value: float
    interpretation: str
    category: str = Field(
        ..., pattern=r"^(low_risk|intermediate|high_risk)$"
    )
    components: dict[str, float | int | str]


class PrognosticScoreResponse(BaseModel):
    scores: list[PrognosticScore]
    error: str | None = None


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
