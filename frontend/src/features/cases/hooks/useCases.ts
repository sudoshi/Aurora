import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  getCases,
  getCase,
  createCase,
  updateCase,
  deleteCase,
  addTeamMember,
  removeTeamMember,
  getDiscussions,
  createDiscussion,
  getAnnotations,
  createAnnotation,
  getDocuments,
  uploadDocument,
  deleteDocument,
} from "../api/casesApi";
import type {
  CaseFilters,
  CreateCaseData,
  UpdateCaseData,
  CreateAnnotationData,
  TeamMemberRole,
} from "../types/case";

// ── Case queries ─────────────────────────────────────────────────────────────

export const useCases = (filters: CaseFilters = {}) =>
  useQuery({
    queryKey: ["cases", filters],
    queryFn: () => getCases(filters),
  });

export const useCase = (id: number) =>
  useQuery({
    queryKey: ["cases", id],
    queryFn: () => getCase(id),
    enabled: id > 0,
  });

// ── Case mutations ───────────────────────────────────────────────────────────

export const useCreateCase = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateCaseData) => createCase(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["cases"] }),
  });
};

export const useUpdateCase = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateCaseData }) =>
      updateCase(id, data),
    onSuccess: (_data, variables) => {
      qc.invalidateQueries({ queryKey: ["cases"] });
      qc.invalidateQueries({ queryKey: ["cases", variables.id] });
    },
  });
};

export const useDeleteCase = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => deleteCase(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["cases"] }),
  });
};

// ── Team member mutations ────────────────────────────────────────────────────

export const useAddTeamMember = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      caseId,
      userId,
      role,
    }: {
      caseId: number;
      userId: number;
      role: TeamMemberRole;
    }) => addTeamMember(caseId, userId, role),
    onSuccess: (_data, variables) =>
      qc.invalidateQueries({ queryKey: ["cases", variables.caseId] }),
  });
};

export const useRemoveTeamMember = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ caseId, userId }: { caseId: number; userId: number }) =>
      removeTeamMember(caseId, userId),
    onSuccess: (_data, variables) =>
      qc.invalidateQueries({ queryKey: ["cases", variables.caseId] }),
  });
};

// ── Discussion queries & mutations ───────────────────────────────────────────

export const useCaseDiscussions = (caseId: number) =>
  useQuery({
    queryKey: ["cases", caseId, "discussions"],
    queryFn: () => getDiscussions(caseId),
    enabled: caseId > 0,
  });

export const useCreateDiscussion = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      caseId,
      content,
      parentId,
    }: {
      caseId: number;
      content: string;
      parentId?: number;
    }) => createDiscussion(caseId, content, parentId),
    onSuccess: (_data, variables) =>
      qc.invalidateQueries({ queryKey: ["cases", variables.caseId, "discussions"] }),
  });
};

// ── Annotation queries & mutations ───────────────────────────────────────────

export const useCaseAnnotations = (caseId: number) =>
  useQuery({
    queryKey: ["cases", caseId, "annotations"],
    queryFn: () => getAnnotations(caseId),
    enabled: caseId > 0,
  });

export const useCreateAnnotation = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      caseId,
      data,
    }: {
      caseId: number;
      data: CreateAnnotationData;
    }) => createAnnotation(caseId, data),
    onSuccess: (_data, variables) =>
      qc.invalidateQueries({ queryKey: ["cases", variables.caseId, "annotations"] }),
  });
};

// ── Document queries & mutations ─────────────────────────────────────────────

export const useCaseDocuments = (caseId: number) =>
  useQuery({
    queryKey: ["cases", caseId, "documents"],
    queryFn: () => getDocuments(caseId),
    enabled: caseId > 0,
  });

export const useUploadDocument = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      caseId,
      file,
      documentType,
      description,
    }: {
      caseId: number;
      file: File;
      documentType: string;
      description?: string;
    }) => uploadDocument(caseId, file, documentType, description),
    onSuccess: (_data, variables) =>
      qc.invalidateQueries({ queryKey: ["cases", variables.caseId, "documents"] }),
  });
};

export const useDeleteDocument = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (documentId: number) => deleteDocument(documentId),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["cases"] }),
  });
};
