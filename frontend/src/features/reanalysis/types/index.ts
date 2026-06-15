export type KbAlertSeverity = "high" | "medium";
export type KbAlertStatus = "new" | "acknowledged" | "dismissed";

export interface KbChangeAlert {
  id: number;
  genomic_variant_id: number;
  patient_id: number;
  source: string;
  clinvar_variation_id: string | null;
  from_bucket: string;
  to_bucket: string;
  from_stars: number;
  to_stars: number;
  severity: KbAlertSeverity;
  evidence: {
    clinvar_significance?: string;
    review_status?: string;
    gene?: string;
    variation_url?: string;
  } | null;
  status: KbAlertStatus;
  task_id: number | null;
  acknowledged_by: number | null;
  acknowledged_at: string | null;
  resolution_note: string | null;
  created_at: string;
  variant?: { id: number; gene: string; patient_id: number };
}

export const BUCKET_LABEL: Record<string, string> = {
  benign: "Benign",
  likely_benign: "Likely Benign",
  vus: "VUS",
  conflicting: "Conflicting",
  likely_pathogenic: "Likely Pathogenic",
  pathogenic: "Pathogenic",
  unknown: "Unknown",
};
