import apiClient from "@/lib/api-client";
import type {
  Session,
  PaginatedSessions,
  SessionFilters,
  CreateSessionData,
  UpdateSessionData,
  SessionCase,
  SessionParticipant,
  AddSessionCaseData,
  AddParticipantData,
} from "../types/session";

// ── Sessions CRUD ────────────────────────────────────────────────────────────

export const getSessions = (filters: SessionFilters = {}) =>
  apiClient
    .get<PaginatedSessions>("/sessions", { params: filters })
    .then((r) => r.data);

export const getSession = (id: number) =>
  apiClient.get<Session>(`/sessions/${id}`).then((r) => r.data);

export const createSession = (data: CreateSessionData) =>
  apiClient.post<Session>("/sessions", data).then((r) => r.data);

export const updateSession = (id: number, data: UpdateSessionData) =>
  apiClient.put<Session>(`/sessions/${id}`, data).then((r) => r.data);

export const deleteSession = (id: number) =>
  apiClient.delete(`/sessions/${id}`);

// ── Session Lifecycle ────────────────────────────────────────────────────────

export const startSession = (id: number) =>
  apiClient.post<Session>(`/sessions/${id}/start`).then((r) => r.data);

export const endSession = (id: number) =>
  apiClient.post<Session>(`/sessions/${id}/end`).then((r) => r.data);

export const cancelSession = (id: number) =>
  apiClient.post<Session>(`/sessions/${id}/cancel`).then((r) => r.data);

// ── Session Cases ────────────────────────────────────────────────────────────

export const addSessionCase = (sessionId: number, data: AddSessionCaseData) =>
  apiClient
    .post<SessionCase>(`/sessions/${sessionId}/cases`, data)
    .then((r) => r.data);

export const removeSessionCase = (sessionId: number, caseId: number) =>
  apiClient.delete(`/sessions/${sessionId}/cases/${caseId}`);

export const reorderSessionCases = (
  sessionId: number,
  orderedCaseIds: number[],
) =>
  apiClient
    .put(`/sessions/${sessionId}/cases/reorder`, { case_ids: orderedCaseIds })
    .then((r) => r.data);

// ── Participants ─────────────────────────────────────────────────────────────

export const addParticipant = (sessionId: number, data: AddParticipantData) =>
  apiClient
    .post<SessionParticipant>(`/sessions/${sessionId}/participants`, data)
    .then((r) => r.data);

export const removeParticipant = (sessionId: number, userId: number) =>
  apiClient.delete(`/sessions/${sessionId}/participants/${userId}`);
