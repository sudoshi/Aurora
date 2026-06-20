import apiClient from "@/lib/api-client";
import type {
  GenomicUpload,
  GenomicVariant,
  GenomicCohortCriterion,
  GenomicsStats,
  PaginatedResponse,
  FileFormat,
  GenomeBuild,
  CriteriaType,
  ClinVarVariant,
  ClinVarStatus,
  GeneDrugInteraction,
  GenomicBriefingRequest,
  GenomicBriefingResponse,
  RadiogenomicsPanel,
  GenomicOperation,
  GenomicMatchResult,
  GenomicImportResult,
  GenomicClinVarAnnotationResult,
} from "../types";

const BASE = "/genomics";

// -- Stats ------------------------------------------------------------------

export async function getGenomicsStats(): Promise<GenomicsStats> {
  const { data } = await apiClient.get(`${BASE}/stats`);
  return data.data ?? data;
}

// -- Uploads ----------------------------------------------------------------

export async function listUploads(params?: {
  status?: string;
  per_page?: number;
  page?: number;
}): Promise<PaginatedResponse<GenomicUpload>> {
  const { data } = await apiClient.get(`${BASE}/uploads`, { params });
  return data;
}

export async function uploadVariantFile(payload: {
  file: File;
  file_format: FileFormat;
  genome_build?: GenomeBuild;
  sample_id?: string;
}): Promise<GenomicUpload> {
  const form = new FormData();
  form.append("file", payload.file);
  form.append("file_format", payload.file_format);
  if (payload.genome_build) form.append("genome_build", payload.genome_build);
  if (payload.sample_id) form.append("sample_id", payload.sample_id);

  const { data } = await apiClient.post(`${BASE}/uploads`, form, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  return data.data;
}

export async function getUpload(id: number): Promise<GenomicUpload> {
  const { data } = await apiClient.get(`${BASE}/uploads/${id}`);
  return data.data;
}

export async function deleteUpload(id: number): Promise<void> {
  await apiClient.delete(`${BASE}/uploads/${id}`);
}

export async function matchPersons(
  id: number
): Promise<{ operation: GenomicOperation; upload: GenomicUpload; result: GenomicMatchResult }> {
  const { data } = await apiClient.post(`${BASE}/uploads/${id}/match-persons`);
  return data.data;
}

export async function importToOmop(
  id: number
): Promise<{ operation: GenomicOperation; upload: GenomicUpload; result: GenomicImportResult }> {
  const { data } = await apiClient.post(`${BASE}/uploads/${id}/import`);
  return data.data;
}

// -- Variants ---------------------------------------------------------------

export async function listVariants(params?: {
  upload_id?: number;
  person_id?: number;
  gene?: string;
  clinvar_significance?: string;
  mapping_status?: string;
  per_page?: number;
  page?: number;
}): Promise<PaginatedResponse<GenomicVariant>> {
  const { data } = await apiClient.get(`${BASE}/variants`, { params });
  return data;
}

export async function getVariant(id: number): Promise<GenomicVariant> {
  const { data } = await apiClient.get(`${BASE}/variants/${id}`);
  return data.data;
}

// -- Cohort criteria --------------------------------------------------------

export async function listCriteria(params?: {
  type?: CriteriaType;
}): Promise<GenomicCohortCriterion[]> {
  const { data } = await apiClient.get(`${BASE}/criteria`, { params });
  return data.data;
}

export async function createCriterion(payload: {
  name: string;
  criteria_type: CriteriaType;
  criteria_definition: Record<string, unknown>;
  description?: string;
  is_shared?: boolean;
}): Promise<GenomicCohortCriterion> {
  const { data } = await apiClient.post(`${BASE}/criteria`, payload);
  return data.data;
}

export async function updateCriterion(
  id: number,
  payload: Partial<{
    name: string;
    criteria_type: CriteriaType;
    criteria_definition: Record<string, unknown>;
    description: string;
    is_shared: boolean;
  }>
): Promise<GenomicCohortCriterion> {
  const { data } = await apiClient.put(`${BASE}/criteria/${id}`, payload);
  return data.data;
}

export async function deleteCriterion(id: number): Promise<void> {
  await apiClient.delete(`${BASE}/criteria/${id}`);
}

// -- ClinVar ----------------------------------------------------------------

export async function getClinVarStatus(): Promise<ClinVarStatus> {
  const { data } = await apiClient.get(`${BASE}/clinvar/status`);
  return data.data;
}

export async function searchClinVar(params?: {
  q?: string;
  gene?: string;
  significance?: string;
  pathogenic_only?: boolean;
  per_page?: number;
  page?: number;
}): Promise<PaginatedResponse<ClinVarVariant>> {
  const { data } = await apiClient.get(`${BASE}/clinvar/search`, { params });
  return data;
}

export async function syncClinVar(papuOnly = false): Promise<{
  inserted: number;
  updated: number;
  errors: number;
  log_id: number;
}> {
  const { data } = await apiClient.post(`${BASE}/clinvar/sync`, { papu_only: papuOnly });
  return data.data;
}

export async function annotateClinVar(uploadId: number): Promise<{
  operation: GenomicOperation;
  upload: GenomicUpload;
  result: GenomicClinVarAnnotationResult;
}> {
  const { data } = await apiClient.post(`${BASE}/uploads/${uploadId}/annotate-clinvar`);
  return data.data;
}

// --- Gene-Drug Interactions ---

export async function getInteractions(
  params: { gene?: string; evidence_level?: string; relationship?: string } = {},
): Promise<GeneDrugInteraction[]> {
  const { data } = await apiClient.get("/genomics/interactions", { params });
  return data.data ?? data;
}

// --- Radiogenomics Panel (absorbed from features/radiogenomics) ---

export async function getRadiogenomicsPanel(
  patientId: number,
): Promise<RadiogenomicsPanel> {
  const { data } = await apiClient.get(`/radiogenomics/patients/${patientId}`);
  return data.data ?? data;
}

// --- AI Genomic Briefing ---

const AI_BASE = (import.meta as unknown as { env: Record<string, string | undefined> }).env.VITE_AI_URL ?? "http://localhost:8100/api";

export async function generateGenomicBriefing(
  payload: GenomicBriefingRequest,
): Promise<GenomicBriefingResponse> {
  const resp = await fetch(`${AI_BASE}/decision-support/genomic-briefing`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  if (!resp.ok) {
    return { briefing: "", generated_at: "", variant_count: 0, actionable_count: 0, error: `HTTP ${resp.status}` };
  }
  return resp.json();
}

// --- AI Variant Interpretation ---

export async function interpretVariant(
  gene: string,
  variant: string,
  cancerType?: string,
): Promise<{ interpretation?: { gene: string; variant: string; classification: string; clinical_significance: string; actionable: boolean; targeted_therapies: string[]; clinical_trials: string[]; references: string[] }; error?: string }> {
  const resp = await fetch(`${AI_BASE}/decision-support/variant-interpret`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ gene, variant, cancer_type: cancerType }),
  });
  if (!resp.ok) {
    return { error: `HTTP ${resp.status}` };
  }
  return resp.json();
}
