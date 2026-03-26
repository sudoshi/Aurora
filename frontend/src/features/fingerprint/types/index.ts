// ── Enums & Literals ────────────────────────────────────────────────

export type ClinicianRating = 'excellent' | 'good' | 'mixed' | 'poor' | 'failure';
export type SearchContext = 'point_of_care' | 'tumor_board' | 'research';
export type WeightConfigType = 'preset' | 'learned' | 'custom';

// ── Fingerprint ─────────────────────────────────────────────────────

export interface DimensionState {
  genomic: boolean;
  volumetric: boolean;
  clinical: boolean;
}

export interface DimensionConfidence {
  genomic: number | null;
  volumetric: number | null;
  clinical: number | null;
}

export interface DimensionTimestamps {
  genomic: string | null;
  volumetric: string | null;
  clinical: string | null;
}

export interface PatientFingerprint {
  patient_id: number;
  has_fingerprint: boolean;
  dimensions: DimensionState;
  confidence: DimensionConfidence;
  encoded_at: DimensionTimestamps;
  encoder_version: string;
  dimension_count: number;
}

// ── Similarity Search ───────────────────────────────────────────────

export interface DimensionWeights {
  genomic: number;
  volumetric: number;
  clinical: number;
}

export interface SimilarPatientResult {
  patient_id: number;
  composite_score: number;
  genomic_similarity: number | null;
  volumetric_similarity: number | null;
  clinical_similarity: number | null;
  dimensions_matched: string[];
  explanation: string | null;
  patient: {
    id: number;
    mrn: string;
    first_name: string;
    last_name: string;
    sex: string;
    date_of_birth: string;
    primary_conditions: string[];
  } | null;
  outcome: {
    composite_score: number | null;
    clinician_rating: ClinicianRating | null;
    decision_tags: string[];
    hindsight_note: string | null;
    sub_scores: Record<string, number | null>;
  } | null;
}

export interface SearchMeta {
  query_patient_id: number;
  weights_used: DimensionWeights;
  weights_customized: boolean;
  dimensions_available: boolean[];
  result_count: number;
}

export interface SimilaritySearchResponse {
  results: SimilarPatientResult[];
  meta: SearchMeta;
}

// ── Outcome ─────────────────────────────────────────────────────────

export interface OutcomeSubScores {
  tumor_response: number | null;
  treatment_tolerance: number | null;
  lab_trajectory: number | null;
  disease_stability: number | null;
  care_intensity: number | null;
}

export interface OutcomeTrajectory {
  patient_id: number;
  has_outcome: boolean;
  computed: {
    composite_score: number | null;
    sub_scores: OutcomeSubScores;
    computed_at: string | null;
  } | null;
  assessment: {
    rating: ClinicianRating;
    factors: string | null;
    decision_tags: string[];
    hindsight_note: string | null;
    assessed_by: string | null;
    assessed_at: string | null;
  } | null;
}

export interface OutcomeAssessmentPayload {
  clinician_rating: ClinicianRating;
  clinician_factors?: string;
  decision_tags?: string[];
  hindsight_note?: string;
}

// ── Weight Config ───────────────────────────────────────────────────

export interface FusionWeightConfig {
  id: number;
  name: string;
  config_type: WeightConfigType;
  genomic_weight: number;
  volumetric_weight: number;
  clinical_weight: number;
  outcome_weights: Record<string, number> | null;
  is_active: boolean;
  trained_on_count: number | null;
}

// ── Stats ───────────────────────────────────────────────────────────

export interface FingerprintStats {
  total_fingerprinted: number;
  genomic_coverage: number;
  volumetric_coverage: number;
  clinical_coverage: number;
  full_coverage: number;
  outcomes_annotated: number;
}
