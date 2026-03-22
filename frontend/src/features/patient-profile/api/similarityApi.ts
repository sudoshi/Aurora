import apiClient from "@/lib/api-client";

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export interface SimilarPatient {
  patient_id: number;
  score: number;
  shared_conditions: string[];
  shared_medications: string[];
  key_differences: string[];
  outcome_summary: string | null;
}

export interface SimilaritySearchResponse {
  query_patient_id: number;
  results: SimilarPatient[];
}

// ---------------------------------------------------------------------------
// API calls
// ---------------------------------------------------------------------------

export async function searchSimilarPatients(
  patientId: number,
  topK: number = 10,
): Promise<SimilaritySearchResponse> {
  const { data } = await apiClient.post("/ai/similarity/search", {
    patient_id: patientId,
    top_k: topK,
  });
  return data.data ?? data;
}

export async function embedPatient(patientId: number): Promise<void> {
  await apiClient.post("/ai/similarity/embed", { patient_id: patientId });
}
