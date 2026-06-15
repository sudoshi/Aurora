export type AcmgClassification = "pathogenic" | "likely_pathogenic" | "vus" | "likely_benign" | "benign";
export type AcmgStrength = "very_strong" | "strong" | "moderate" | "supporting";

export interface AcmgCriterionDef {
  category: "pathogenic" | "benign";
  default_strength: AcmgStrength;
  automatable: boolean;
  standalone: boolean;
  description: string;
}
export type AcmgCatalog = Record<string, AcmgCriterionDef>;

export interface ClassificationCriterion {
  id: number;
  classification_id: number;
  code: string;
  applied_strength: AcmgStrength;
  points: number;
  data_source: string;
  evidence_value: string | null;
  rationale: string | null;
  set_by: "auto" | "curator";
  set_by_user_id: number | null;
}

export interface VariantClassification {
  id: number;
  genomic_variant_id: number;
  gene_symbol: string | null;
  computed_classification: AcmgClassification;
  computed_points: number;
  final_classification: AcmgClassification | null;
  status: "computed" | "confirmed";
  ruleset_version: string;
  gene_specification_id: string | null;
  override_reason: string | null;
  confirmed_by: number | null;
  confirmed_at: string | null;
  criteria?: ClassificationCriterion[];
}

export interface CreateClassificationInput {
  population_af?: number;
  revel?: number;
  protein_hgvs?: string;
}

export const CLASSIFICATION_LABEL: Record<AcmgClassification, string> = {
  pathogenic: "Pathogenic",
  likely_pathogenic: "Likely Pathogenic",
  vus: "VUS",
  likely_benign: "Likely Benign",
  benign: "Benign",
};
