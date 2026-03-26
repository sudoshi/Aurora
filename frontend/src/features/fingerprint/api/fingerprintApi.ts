import apiClient from "@/lib/api-client";
import type {
  DimensionWeights,
  FingerprintStats,
  FusionWeightConfig,
  OutcomeAssessmentPayload,
  OutcomeTrajectory,
  PatientFingerprint,
  SearchContext,
  SimilaritySearchResponse,
} from "../types";

const BASE = "/fingerprint";

// -- Search -----------------------------------------------------------------

export async function searchSimilar(params: {
  patient_id: number;
  weights?: Partial<DimensionWeights>;
  limit?: number;
  context?: SearchContext;
}): Promise<SimilaritySearchResponse> {
  const { data } = await apiClient.post(`${BASE}/search`, params);
  return data.data;
}

// -- Fingerprint ------------------------------------------------------------

export async function getFingerprint(patientId: number): Promise<PatientFingerprint> {
  const { data } = await apiClient.get(`${BASE}/patients/${patientId}`);
  return data.data;
}

export async function encodePatient(patientId: number): Promise<PatientFingerprint> {
  const { data } = await apiClient.post(`${BASE}/patients/${patientId}/encode`);
  return data.data;
}

export async function encodeBatch(patientIds: number[]): Promise<{ patient_id: number; dimension_count: number }[]> {
  const { data } = await apiClient.post(`${BASE}/encode-batch`, { patient_ids: patientIds });
  return data.data;
}

// -- Outcomes ---------------------------------------------------------------

export async function getOutcome(patientId: number): Promise<OutcomeTrajectory> {
  const { data } = await apiClient.get(`${BASE}/patients/${patientId}/outcome`);
  return data.data;
}

export async function assessOutcome(
  patientId: number,
  payload: OutcomeAssessmentPayload,
): Promise<{ patient_id: number; clinician_rating: string; assessed_at: string }> {
  const { data } = await apiClient.put(`${BASE}/patients/${patientId}/outcome/assess`, payload);
  return data.data;
}

// -- Weights ----------------------------------------------------------------

export async function listWeights(): Promise<FusionWeightConfig[]> {
  const { data } = await apiClient.get(`${BASE}/weights`);
  return data.data ?? data;
}

export async function getActiveWeights(): Promise<FusionWeightConfig> {
  const { data } = await apiClient.get(`${BASE}/weights/active`);
  return data.data;
}

// -- Stats ------------------------------------------------------------------

export async function getFingerprintStats(): Promise<FingerprintStats> {
  const { data } = await apiClient.get(`${BASE}/stats`);
  return data.data ?? data;
}
