import apiClient from "@/lib/api-client";
import type {
  ClinicalCase,
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

/** Unwrap Aurora's ApiResponse envelope {success, data} */
function unwrap<T>(response: { data: { data?: T; success?: boolean } | T }): T {
  const d = response.data;
  if (d && typeof d === "object" && "success" in d && "data" in d) {
    return (d as { data: T }).data;
  }
  return d as T;
}

// ── Cases CRUD ───────────────────────────────────────────────────────────────

export interface PaginatedResult<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

export const getCases = (filters: CaseFilters = {}): Promise<PaginatedResult<ClinicalCase>> =>
  apiClient
    .get("/cases", { params: filters })
    .then((r) => {
      const d = r.data;
      // Laravel paginated: {success, data: [...], meta: {total, page, per_page, last_page}}
      // Or direct Laravel paginate: {data: [...], current_page, last_page, total, per_page}
      if (d?.meta) {
        return {
          data: d.data ?? [],
          current_page: d.meta.page ?? d.meta.current_page ?? 1,
          last_page: d.meta.last_page ?? 1,
          total: d.meta.total ?? 0,
          per_page: d.meta.per_page ?? 20,
        };
      }
      if (d?.current_page !== undefined) {
        return d;
      }
      // Fallback: treat as array
      const items = d?.data ?? d ?? [];
      return { data: items, current_page: 1, last_page: 1, total: items.length, per_page: items.length };
    });

export const getCase = (id: number) =>
  apiClient.get(`/cases/${id}`).then((r) => unwrap<ClinicalCase>(r));

export const createCase = (data: CreateCaseData) =>
  apiClient.post("/cases", data).then((r) => unwrap<ClinicalCase>(r));

export const updateCase = (id: number, data: UpdateCaseData) =>
  apiClient.put(`/cases/${id}`, data).then((r) => unwrap<ClinicalCase>(r));

export const deleteCase = (id: number) =>
  apiClient.delete(`/cases/${id}`);

// ── Team Members ─────────────────────────────────────────────────────────────

export const addTeamMember = (
  caseId: number,
  userId: number,
  role: TeamMemberRole,
) =>
  apiClient
    .post(`/cases/${caseId}/team`, { user_id: userId, role })
    .then((r) => unwrap<CaseTeamMember>(r));

export const removeTeamMember = (caseId: number, userId: number) =>
  apiClient.delete(`/cases/${caseId}/team/${userId}`);

// ── Discussions ──────────────────────────────────────────────────────────────

export const getDiscussions = (caseId: number) =>
  apiClient
    .get(`/cases/${caseId}/discussions`)
    .then((r) => unwrap<CaseDiscussion[]>(r));

export const createDiscussion = (
  caseId: number,
  content: string,
  parentId?: number,
) =>
  apiClient
    .post(`/cases/${caseId}/discussions`, {
      content,
      parent_id: parentId ?? null,
    })
    .then((r) => unwrap<CaseDiscussion>(r));

// ── Annotations ──────────────────────────────────────────────────────────────

export const getAnnotations = (caseId: number) =>
  apiClient
    .get(`/cases/${caseId}/annotations`)
    .then((r) => unwrap<CaseAnnotation[]>(r));

export const createAnnotation = (caseId: number, data: CreateAnnotationData) =>
  apiClient
    .post(`/cases/${caseId}/annotations`, data)
    .then((r) => unwrap<CaseAnnotation>(r));

// ── Documents ────────────────────────────────────────────────────────────────

export const getDocuments = (caseId: number) =>
  apiClient
    .get(`/cases/${caseId}/documents`)
    .then((r) => unwrap<CaseDocument[]>(r));

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
    .post(`/cases/${caseId}/documents`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    })
    .then((r) => unwrap<CaseDocument>(r));
};

export const deleteDocument = (documentId: number) =>
  apiClient.delete(`/documents/${documentId}`);
