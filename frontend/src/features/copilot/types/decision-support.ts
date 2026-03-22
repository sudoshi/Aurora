// ---------------------------------------------------------------------------
// Decision Support Types — Aurora Copilot
// ---------------------------------------------------------------------------

export interface TrialSuggestion {
  trial_type: string;
  rationale: string;
  key_criteria_met: string[];
  potential_exclusions: string[];
  confidence: "high" | "medium" | "low";
}

export interface TrialMatchResponse {
  patient_id: number;
  trials: TrialSuggestion[];
}

export interface ConcordanceResult {
  concordant: boolean;
  guideline_referenced: string;
  supporting_evidence: string[];
  concerns: string[];
  alternative_recommendations: string[];
  confidence: "high" | "medium" | "low";
}

export interface DrugInteraction {
  drug_a: string;
  drug_b: string;
  severity: "major" | "moderate" | "minor";
  mechanism: string;
  clinical_significance: string;
  recommendation: string;
}

export interface DrugInteractionResponse {
  medications: string[];
  proposed_medication: string | null;
  interactions: DrugInteraction[];
}

export interface VariantInterpretation {
  gene: string;
  variant: string;
  classification: string;
  clinical_significance: string;
  actionable: boolean;
  targeted_therapies: string[];
  clinical_trials: string[];
}

export interface PrognosticScore {
  score_name: string;
  value: number;
  interpretation: string;
  category: "low_risk" | "intermediate" | "high_risk";
}

export interface PrognosticResponse {
  patient_id: number;
  scores: PrognosticScore[];
}

export interface PatientContext {
  patient_id: number;
  conditions: string[];
  medications: string[];
  age?: number;
  sex?: string;
}
