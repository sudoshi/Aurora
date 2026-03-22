export interface PersonDemographics {
  person_id: number;
  gender: string | null;
  year_of_birth: number | null;
  race: string | null;
  ethnicity: string | null;
}

export interface DrugExposure {
  drug_name: string;
  drug_class: string | null;
  start_date: string;
  end_date: string | null;
  total_days: number;
}

export interface VariantDrugCorrelation {
  variant_id: number;
  gene_symbol: string;
  hgvs_p: string | null;
  clinvar_significance: string;
  drug_name: string;
  relationship: "sensitive" | "resistant" | "partial_response";
  mechanism: string | null;
  evidence_level: string;
  confidence: "high" | "medium" | "low";
  evidence_summary: string | null;
  patient_received_drug: boolean;
  drug_start: string | null;
  drug_end: string | null;
  drug_days: number | null;
  response_category: string | null;
  response_rationale: string | null;
}

export interface PrecisionRecommendation {
  gene: string;
  variant: string;
  recommendation_type: "avoid_and_consider" | "consider";
  drugs_avoid: string[];
  drugs_consider: string[];
  rationale: string;
}

export interface VariantSummary {
  all: Array<{
    id: number;
    gene_symbol: string;
    hgvs_p: string | null;
    variant_class: string;
    clinvar_significance: string;
    clinvar_disease: string | null;
    raw_info: Record<string, unknown> | null;
  }>;
  actionable: Record<number, string>;
  vus: Record<number, string>;
  other: Record<number, string>;
  total: number;
  pathogenic_count: number;
  vus_count: number;
}

export interface RadiogenomicsPanel {
  person_id: number;
  demographics: PersonDemographics;
  variants: VariantSummary;
  drug_exposures: DrugExposure[];
  correlations: VariantDrugCorrelation[];
  recommendations: PrecisionRecommendation[];
}

export interface VariantDrugInteraction {
  id: number;
  gene_symbol: string;
  hgvs_p: string | null;
  variant_class: string | null;
  drug_name: string;
  relationship: "sensitive" | "resistant" | "partial_response";
  mechanism: string | null;
  evidence_level: string;
  confidence: "high" | "medium" | "low";
  evidence_summary: string | null;
}
