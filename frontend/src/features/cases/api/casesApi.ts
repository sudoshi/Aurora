import apiClient from "@/lib/api-client";
import type {
  ClinicalCase,
  PaginatedCases,
  CaseFilters,
  CreateCaseData,
  UpdateCaseData,
  CaseTeamMember,
  CaseDiscussion,
  CaseAnnotation,
  CaseDocument,
  CreateAnnotationData,
  TeamMemberRole,
} from "../types/case";

// ── Cases CRUD ───────────────────────────────────────────────────────────────

export const getCases = (filters: CaseFilters = {}) =>
  apiClient
    .get<PaginatedCases>("/cases", { params: filters })
    .then((r) => r.data);

export const getCase = (id: number) =>
  apiClient.get<ClinicalCase>(`/cases/${id}`).then((r) => r.data);

export const createCase = (data: CreateCaseData) =>
  apiClient.post<ClinicalCase>("/cases", data).then((r) => r.data);

export const updateCase = (id: number, data: UpdateCaseData) =>
  apiClient.put<ClinicalCase>(`/cases/${id}`, data).then((r) => r.data);

export const deleteCase = (id: number) =>
  apiClient.delete(`/cases/${id}`);

// ── Team Members ─────────────────────────────────────────────────────────────

export const addTeamMember = (
  caseId: number,
  userId: number,
  role: TeamMemberRole,
) =>
  apiClient
    .post<CaseTeamMember>(`/cases/${caseId}/team`, { user_id: userId, role })
    .then((r) => r.data);

export const removeTeamMember = (caseId: number, userId: number) =>
  apiClient.delete(`/cases/${caseId}/team/${userId}`);

// ── Discussions ──────────────────────────────────────────────────────────────

export const getDiscussions = (caseId: number) =>
  apiClient
    .get<CaseDiscussion[]>(`/cases/${caseId}/discussions`)
    .then((r) => r.data);

export const createDiscussion = (
  caseId: number,
  content: string,
  parentId?: number,
) =>
  apiClient
    .post<CaseDiscussion>(`/cases/${caseId}/discussions`, {
      content,
      parent_id: parentId ?? null,
    })
    .then((r) => r.data);

// ── Annotations ──────────────────────────────────────────────────────────────

export const getAnnotations = (caseId: number) =>
  apiClient
    .get<CaseAnnotation[]>(`/cases/${caseId}/annotations`)
    .then((r) => r.data);

export const createAnnotation = (caseId: number, data: CreateAnnotationData) =>
  apiClient
    .post<CaseAnnotation>(`/cases/${caseId}/annotations`, data)
    .then((r) => r.data);

// ── Documents ────────────────────────────────────────────────────────────────

export const getDocuments = (caseId: number) =>
  apiClient
    .get<CaseDocument[]>(`/cases/${caseId}/documents`)
    .then((r) => r.data);

export const uploadDocument = (
  caseId: number,
  file: File,
  documentType: string,
  description?: string,
) => {
  const formData = new FormData();
  formData.append("file", file);
  formData.append("document_type", documentType);
  if (description) {
    formData.append("description", description);
  }
  return apiClient
    .post<CaseDocument>(`/cases/${caseId}/documents`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    })
    .then((r) => r.data);
};

export const deleteDocument = (documentId: number) =>
  apiClient.delete(`/documents/${documentId}`);
