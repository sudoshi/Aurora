import api from '@/lib/api-client';
import type {
  CollaborationData,
  PatientFlag,
  PatientTask,
  PatientDecision,
  ClinicalDomain,
} from '../types/collaboration';

interface ApiResponse<T> {
  success: boolean;
  data: T;
}

function unwrap<T>(response: { data: ApiResponse<T> }): T {
  return response.data.data;
}

// ── Flags ────────────────────────────────────────────────────────────

export async function fetchPatientFlags(
  patientId: number,
  domain?: ClinicalDomain,
  resolved?: boolean,
): Promise<PatientFlag[]> {
  const params: Record<string, string> = {};
  if (domain) params.domain = domain;
  if (resolved !== undefined) params.resolved = String(resolved);
  return unwrap(await api.get(`/patients/${patientId}/flags`, { params }));
}

export async function createPatientFlag(
  patientId: number,
  data: { domain: ClinicalDomain; record_ref: string; severity?: string; title: string; description?: string },
): Promise<PatientFlag> {
  return unwrap(await api.post(`/patients/${patientId}/flags`, data));
}

export async function updatePatientFlag(
  flagId: number,
  data: { severity?: string; title?: string; description?: string; resolve?: boolean },
): Promise<PatientFlag> {
  return unwrap(await api.patch(`/flags/${flagId}`, data));
}

export async function deletePatientFlag(flagId: number): Promise<void> {
  await api.delete(`/flags/${flagId}`);
}

// ── Tasks ────────────────────────────────────────────────────────────

export async function fetchPatientTasks(
  patientId: number,
  domain?: ClinicalDomain,
  status?: string,
): Promise<PatientTask[]> {
  const params: Record<string, string> = {};
  if (domain) params.domain = domain;
  if (status) params.status = status;
  return unwrap(await api.get(`/patients/${patientId}/tasks`, { params }));
}

export async function createPatientTask(
  patientId: number,
  data: {
    title: string;
    description?: string;
    assigned_to?: number;
    domain?: ClinicalDomain;
    record_ref?: string;
    due_date?: string;
    priority?: string;
  },
): Promise<PatientTask> {
  return unwrap(await api.post(`/patients/${patientId}/tasks`, data));
}

export async function updatePatientTask(
  taskId: number,
  data: { status?: string; assigned_to?: number; title?: string; description?: string; due_date?: string; priority?: string },
): Promise<PatientTask> {
  return unwrap(await api.patch(`/tasks/${taskId}`, data));
}

export async function deletePatientTask(taskId: number): Promise<void> {
  await api.delete(`/tasks/${taskId}`);
}

// ── Collaboration Aggregate ──────────────────────────────────────────

export async function fetchPatientCollaboration(
  patientId: number,
  domain?: ClinicalDomain,
): Promise<CollaborationData> {
  const params: Record<string, string> = {};
  if (domain) params.domain = domain;
  return unwrap(await api.get(`/patients/${patientId}/collaboration`, { params }));
}

// ── Decisions (read-only convenience) ────────────────────────────────

export async function fetchPatientDecisions(patientId: number): Promise<PatientDecision[]> {
  return unwrap(await api.get(`/patients/${patientId}/decisions`));
}
