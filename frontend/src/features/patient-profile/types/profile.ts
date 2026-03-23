// ---------------------------------------------------------------------------
// Patient Profile Types — Aurora V2
// ---------------------------------------------------------------------------

export interface ClinicalPatient {
  id: number;
  mrn: string;
  first_name: string;
  last_name: string;
  date_of_birth: string | null;
  sex: string;
  race: string | null;
  ethnicity: string | null;
  deceased_at: string | null;
  category?: AuroraDomainCategory | null;
}

export type ClinicalDomain =
  | "condition"
  | "medication"
  | "procedure"
  | "measurement"
  | "observation"
  | "visit";

export type AuroraDomainCategory =
  | "oncology"
  | "surgical"
  | "rare_disease"
  | "complex_medical";

/** Normalized clinical event — all domains share these fields. */
export interface ClinicalEvent {
  id: number;
  domain: ClinicalDomain;
  concept_name: string;
  concept_code: string | null;
  start_date: string;
  end_date: string | null;
  type_name?: string | null;
  aurora_domain?: AuroraDomainCategory | null;

  // Measurement / Observation value fields
  value_numeric?: number | null;
  value_as_string?: string | null;
  unit?: string | null;

  // Measurement reference range
  reference_range_low?: number | null;
  reference_range_high?: number | null;
  abnormal_flag?: string | null;

  // Medication-specific
  drug_name?: string | null;
  route?: string | null;
  dose_value?: number | null;
  dose_unit?: string | null;
  frequency?: string | null;

  // Visit-specific (for event binning)
  visit_id?: number | null;
}

export interface ObservationPeriod {
  id?: number;
  start_date: string;
  end_date: string;
  period_type?: string | null;
}

export interface ConditionEra {
  id: number;
  condition_name: string;
  condition_code: string | null;
  start_date: string;
  end_date: string;
  occurrence_count: number;
}

export interface DrugEra {
  id: number;
  drug_name: string;
  drug_code: string | null;
  start_date: string;
  end_date: string;
  exposure_count: number;
  gap_days: number;
}

export interface ClinicalNote {
  id: number;
  note_type: string;
  title: string | null;
  content: string;
  author: string | null;
  authored_at: string;
  visit_id: number | null;
}

export interface ImagingStudy {
  id: number;
  study_uid: string;
  modality: string;
  study_date: string;
  description: string | null;
  body_part: string | null;
  num_series: number;
  num_instances: number;
}

export interface GenomicVariant {
  id: number;
  gene: string;
  variant: string;
  variant_type: string;
  clinical_significance: string | null;
  actionability: string | null;
}

export interface PatientProfile {
  patient: ClinicalPatient;
  conditions: ClinicalEvent[];
  medications: ClinicalEvent[];
  procedures: ClinicalEvent[];
  measurements: ClinicalEvent[];
  observations: ClinicalEvent[];
  visits: ClinicalEvent[];
  notes: ClinicalNote[];
  imaging: ImagingStudy[];
  genomics: GenomicVariant[];
  observation_periods?: ObservationPeriod[];
  condition_eras?: ConditionEra[];
  drug_eras?: DrugEra[];
}

export interface PatientStats {
  conditions: number;
  medications: number;
  procedures: number;
  measurements: number;
  observations: number;
  visits: number;
  notes: number;
  imaging: number;
  genomics: number;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface CreatePatientPayload {
  mrn: string;
  first_name: string;
  last_name: string;
  date_of_birth?: string;
  sex?: string;
  race?: string;
  ethnicity?: string;
}

export type ViewMode = "timeline" | "list" | "labs" | "visits" | "notes" | "eras";

export type DomainTab =
  | "all"
  | "condition"
  | "medication"
  | "procedure"
  | "measurement"
  | "observation"
  | "visit";
