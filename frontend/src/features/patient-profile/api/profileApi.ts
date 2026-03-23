import apiClient from "@/lib/api-client";
import type {
  PatientProfile,
  PatientStats,
  ClinicalPatient,
  ClinicalNote,
  PaginationMeta,
  CreatePatientPayload,
} from "../types/profile";

// ---------------------------------------------------------------------------
// Patient list (paginated)
// ---------------------------------------------------------------------------

export interface PatientListResponse {
  data: ClinicalPatient[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export async function fetchPatients(
  page = 1,
  perPage = 50,
): Promise<PatientListResponse> {
  const { data } = await apiClient.get<{ success: boolean; data: PatientListResponse }>(
    `/patients`,
    { params: { page, per_page: perPage } },
  );
  return data.data;
}

// ---------------------------------------------------------------------------
// Patient profile
// ---------------------------------------------------------------------------

export async function fetchPatientProfile(
  patientId: number,
): Promise<PatientProfile> {
  const { data } = await apiClient.get<{ success: boolean; data: PatientProfile }>(
    `/patients/${patientId}/profile`,
  );
  return data.data;
}

// ---------------------------------------------------------------------------
// Patient stats
// ---------------------------------------------------------------------------

export async function fetchPatientStats(
  patientId: number,
): Promise<PatientStats> {
  const { data } = await apiClient.get<{ success: boolean; data: PatientStats }>(
    `/patients/${patientId}/stats`,
  );
  return data.data;
}

// ---------------------------------------------------------------------------
// Patient search
// ---------------------------------------------------------------------------

export async function searchPatients(
  query: string,
  limit = 20,
): Promise<ClinicalPatient[]> {
  const { data } = await apiClient.get<{ success: boolean; data: ClinicalPatient[] }>(
    `/patients/search`,
    { params: { q: query, limit } },
  );
  return data.data;
}

// ---------------------------------------------------------------------------
// Patient notes (paginated)
// ---------------------------------------------------------------------------

export interface NotesPaginatedResponse {
  data: ClinicalNote[];
  meta: PaginationMeta;
}

export async function fetchPatientNotes(
  patientId: number,
  page = 1,
  perPage = 50,
): Promise<NotesPaginatedResponse> {
  const { data } = await apiClient.get<NotesPaginatedResponse>(
    `/patients/${patientId}/notes`,
    { params: { page, per_page: perPage } },
  );
  return data;
}

// ---------------------------------------------------------------------------
// Create patient
// ---------------------------------------------------------------------------

export async function createPatient(
  payload: CreatePatientPayload,
): Promise<ClinicalPatient> {
  const { data } = await apiClient.post<{ success: boolean; data: ClinicalPatient }>(
    `/patients`,
    payload,
  );
  return data.data;
}
