export type OdysseyStatus =
  | "referral" | "phenotyping" | "testing" | "prioritization"
  | "mdt_review" | "matchmaking" | "diagnosed" | "reanalysis" | "closed";

export type ProgressStatus = "in_progress" | "solved" | "unsolved";

export const ODYSSEY_STATES: readonly OdysseyStatus[] = [
  "referral", "phenotyping", "testing", "prioritization",
  "mdt_review", "matchmaking", "diagnosed", "reanalysis", "closed",
];

export interface OdysseyTransition {
  id: number;
  from_status: string | null;
  to_status: OdysseyStatus;
  actor_id: number;
  note: string | null;
  created_at: string;
  actor?: { id: number; name: string };
}

export interface PhenotypeFeature {
  id: number;
  odyssey_id: number;
  hpo_id: string;
  hpo_label: string;
  excluded: boolean;
  onset_hpo_id: string | null;
  severity_hpo_id: string | null;
  frequency_hpo_id: string | null;
  evidence: string | null;
  created_at: string;
}

export interface OdysseyPatient {
  id: number;
  first_name: string | null;
  last_name: string | null;
  mrn: string | null;
}

export interface DiagnosticOdyssey {
  id: number;
  patient_id: number;
  case_id: number | null;
  title: string;
  status: OdysseyStatus;
  progress_status: ProgressStatus;
  referral_reason: string | null;
  created_by: number;
  solved_at: string | null;
  created_at: string;
  updated_at: string;
  phenotype_features_count?: number;
  patient?: OdysseyPatient;
  transitions?: OdysseyTransition[];
  phenotype_features?: PhenotypeFeature[];
}

export interface OdysseyDetail {
  odyssey: DiagnosticOdyssey;
  allowed_transitions: OdysseyStatus[];
}

export interface HpoTerm {
  id: string;
  label: string;
  definition: string | null;
  synonyms: string[];
}

export interface PhenopacketImportResult {
  imported: number;
  skipped: number;
}

export interface CreateOdysseyInput {
  patient_id: number;
  title: string;
  referral_reason?: string;
}

export interface CreatePhenotypeInput {
  hpo_id: string;
  hpo_label: string;
  excluded?: boolean;
  severity_hpo_id?: string;
  onset_hpo_id?: string;
  frequency_hpo_id?: string;
}

/** Display helper: "First Last" (falls back to MRN, then id). */
export function odysseyPatientName(p?: OdysseyPatient): string {
  if (!p) return "—";
  const full = [p.first_name, p.last_name].filter(Boolean).join(" ").trim();
  return full || p.mrn || `#${p.id}`;
}
