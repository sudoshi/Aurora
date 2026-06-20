export type FileFormat = 'vcf' | 'maf' | 'cbio_maf' | 'fhir_genomics' | 'csv' | 'tsv';
export type GenomeBuild = 'GRCh38' | 'GRCh37' | 'hg38' | 'hg19';
export type UploadStatus = 'pending' | 'parsing' | 'mapped' | 'review' | 'imported' | 'failed';
export type MappingStatus = 'mapped' | 'unmapped' | 'review';
export type CriteriaType = 'gene_mutation' | 'tmb' | 'msi' | 'fusion' | 'pathogenicity' | 'treatment_episode';

export interface GenomicUpload {
  id: number;
  source_id: number;
  created_by: number;
  filename: string;
  original_filename?: string;
  file_format: FileFormat;
  file_size_bytes: number;
  file_size?: number;
  status: UploadStatus;
  raw_status?: string;
  genome_build: GenomeBuild | null;
  sample_id: string | null;
  total_variants: number;
  mapped_variants: number;
  unmapped_variants?: number;
  review_required: number;
  error_message: string | null;
  last_result?: Record<string, unknown> | null;
  matched_at?: string | null;
  clinvar_annotated_at?: string | null;
  parsed_at: string | null;
  imported_at: string | null;
  created_at: string;
  updated_at: string;
  creator?: { id: number; name: string };
}

export interface GenomicOperation {
  name: string;
  status: 'queued' | 'succeeded' | 'completed_with_errors' | 'failed';
  performed: boolean;
}

export interface GenomicMatchResult {
  candidates: number;
  matched: number;
  unmatched: number;
  review_required: number;
}

export interface GenomicImportResult {
  created: number;
  updated: number;
  written: number;
  skipped: number;
  errors: string[];
}

export interface GenomicClinVarAnnotationResult {
  eligible: number;
  annotated: number;
  already_annotated: number;
  missing_reference: number;
  skipped: number;
}

export interface GenomicVariant {
  id: number;
  upload_id: number;
  source_id: number;
  person_id: number | null;
  sample_id: string | null;
  chromosome: string;
  position: number;
  reference_allele: string;
  alternate_allele: string;
  genome_build: GenomeBuild | null;
  gene_symbol: string | null;
  hgvs_c: string | null;
  hgvs_p: string | null;
  variant_type: string | null;
  variant_class: string | null;
  consequence: string | null;
  quality: number | null;
  filter_status: string | null;
  zygosity: string | null;
  allele_frequency: number | null;
  read_depth: number | null;
  clinvar_id: string | null;
  clinvar_significance: string | null;
  cosmic_id: string | null;
  measurement_concept_id: number;
  mapping_status: MappingStatus;
  created_at: string;
}

export interface GenomicCohortCriterion {
  id: number;
  created_by: number;
  name: string;
  criteria_type: CriteriaType;
  criteria_definition: Record<string, unknown>;
  description: string | null;
  is_shared: boolean;
  created_at: string;
  updated_at: string;
}

export interface GenomicsStats {
  total_uploads: number;
  total_variants: number;
  mapped_variants: number;
  review_required: number;
  uploads_by_status: Record<string, number>;
  top_genes: Record<string, number>;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

// -- ClinVar ----------------------------------------------------------------

export interface ClinVarVariant {
  id: number;
  variation_id: string | null;
  rs_id: string | null;
  chromosome: string;
  position: number;
  reference_allele: string;
  alternate_allele: string;
  genome_build: string;
  gene_symbol: string | null;
  hgvs: string | null;
  clinical_significance: string | null;
  disease_name: string | null;
  review_status: string | null;
  is_pathogenic: boolean;
  last_synced_at: string | null;
}

export interface ClinVarSyncLogEntry {
  id: number;
  genome_build: string;
  papu_only: boolean;
  status: 'running' | 'completed' | 'failed';
  variants_inserted: number;
  variants_updated: number;
  error_message: string | null;
  started_at: string | null;
  finished_at: string | null;
}

export interface ClinVarStatus {
  total_variants: number;
  pathogenic_count: number;
  last_sync: string | null;
  last_sync_build: string | null;
  last_sync_papu: boolean | null;
  syncs: ClinVarSyncLogEntry[];
}

// --- Gene-Drug Interactions ---

export interface GeneDrugInteraction {
  id: number;
  gene: string;
  variant_pattern: string;
  drug: string;
  drug_class: string | null;
  relationship: "sensitive" | "resistant" | "dose_adjustment";
  evidence_level: string;
  indication: string | null;
  mechanism: string | null;
  source: "oncokb" | "nccn" | "fda" | "pharmgkb" | "manual";
  source_url: string | null;
  oncokb_last_synced_at: string | null;
  last_verified_at: string | null;
}

// --- Genomic Briefing (AI) ---

export interface GenomicBriefingVariant {
  gene: string;
  variant: string;
  classification: string;
  evidence_level: string | null;
  therapies: string[];
}

export interface GenomicBriefingDrugExposure {
  drug_name: string;
  start_date: string | null;
  end_date: string | null;
}

export interface GenomicBriefingInteraction {
  gene: string;
  drug: string;
  relationship: string;
  evidence_level: string;
  mechanism: string | null;
}

export interface GenomicBriefingRequest {
  patient_id: number;
  variants: GenomicBriefingVariant[];
  drug_exposures: GenomicBriefingDrugExposure[];
  interactions: GenomicBriefingInteraction[];
  total_variant_count: number;
}

export interface GenomicBriefingResponse {
  briefing: string;
  generated_at: string;
  variant_count: number;
  actionable_count: number;
  error?: string;
}

// --- Radiogenomics (absorbed from features/radiogenomics) ---

export interface DrugExposure {
  drug_name: string;
  drug_class: string | null;
  start_date: string | null;
  end_date: string | null;
  total_days: number | null;
}

export interface VariantDrugCorrelation {
  variant_id: number;
  gene_symbol: string;
  variant: string;
  clinical_significance: string;
  drug_name: string;
  relationship: string;
  evidence_level: string;
  mechanism: string | null;
  source: string | null;
  last_verified_at: string | null;
  patient_exposed: boolean;
  exposure_start: string | null;
  exposure_end: string | null;
}

export interface PrecisionRecommendation {
  gene: string;
  variant: string;
  drugs_avoid: string[];
  drugs_consider: string[];
  rationale: string;
}

export interface RadiogenomicsPanel {
  patient: {
    person_id: number;
    gender: string | null;
    year_of_birth: number | null;
    race: string | null;
    ethnicity: string | null;
  };
  variants: {
    all: number;
    actionable: number;
    vus: number;
    other: number;
    details: GenomicVariant[];
  };
  drug_exposures: DrugExposure[];
  correlations: VariantDrugCorrelation[];
  recommendations: PrecisionRecommendation[];
}
