import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  getSessions,
  getSession,
  createSession,
  updateSession,
  deleteSession,
  startSession,
  endSession,
  cancelSession,
  addSessionCase,
  removeSessionCase,
  reorderSessionCases,
  addParticipant,
  removeParticipant,
} from "../api/sessionsApi";
import type {
  SessionFilters,
  CreateSessionData,
  UpdateSessionData,
  AddSessionCaseData,
  AddParticipantData,
} from "../types/session";

// ── Session queries ──────────────────────────────────────────────────────────

export const useSessions = (filters: SessionFilters = {}) =>
  useQuery({
    queryKey: ["sessions", filters],
    queryFn: () => getSessions(filters),
  });

export const useSession = (id: number) =>
  useQuery({
    queryKey: ["sessions", id],
    queryFn: () => getSession(id),
    enabled: id > 0,
  });

// ── Session mutations ────────────────────────────────────────────────────────

export const useCreateSession = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateSessionData) => createSession(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["sessions"] }),
  });
};

export const useUpdateSession = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateSessionData }) =>
      updateSession(id, data),
    onSuccess: (_data, variables) => {
      qc.invalidateQueries({ queryKey: ["sessions"] });
      qc.invalidateQueries({ queryKey: ["sessions", variables.id] });
    },
  });
};

export const useDeleteSession = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => deleteSession(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["sessions"] }),
  });
};

// ── Session lifecycle ────────────────────────────────────────────────────────

export const useStartSession = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => startSession(id),
    onSuccess: (_data, id) => {
      qc.invalidateQueries({ queryKey: ["sessions"] });
      qc.invalidateQueries({ queryKey: ["sessions", id] });
    },
  });
};

export const useEndSession = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => endSession(id),
    onSuccess: (_data, id) => {
      qc.invalidateQueries({ queryKey: ["sessions"] });
      qc.invalidateQueries({ queryKey: ["sessions", id] });
    },
  });
};

export const useCancelSession = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => cancelSession(id),
    onSuccess: (_data, id) => {
      qc.invalidateQueries({ queryKey: ["sessions"] });
      qc.invalidateQueries({ queryKey: ["sessions", id] });
    },
  });
};

// ── Session cases ────────────────────────────────────────────────────────────

export const useAddSessionCase = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      sessionId,
      data,
    }: {
      sessionId: number;
      data: AddSessionCaseData;
    }) => addSessionCase(sessionId, data),
    onSuccess: (_data, variables) =>
      qc.invalidateQueries({ queryKey: ["sessions", variables.sessionId] }),
  });
};

export const useRemoveSessionCase = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      sessionId,
      caseId,
    }: {
      sessionId: number;
      caseId: number;
    }) => removeSessionCase(sessionId, caseId),
    onSuccess: (_data, variables) =>
      qc.invalidateQueries({ queryKey: ["sessions", variables.sessionId] }),
  });
};

export const useReorderSessionCases = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      sessionId,
      orderedCaseIds,
    }: {
      sessionId: number;
      orderedCaseIds: number[];
    }) => reorderSessionCases(sessionId, orderedCaseIds),
    onSuccess: (_data, variables) =>
      qc.invalidateQueries({ queryKey: ["sessions", variables.sessionId] }),
  });
};

// ── Participants ─────────────────────────────────────────────────────────────

export const useAddParticipant = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      sessionId,
      data,
    }: {
      sessionId: number;
      data: AddParticipantData;
    }) => addParticipant(sessionId, data),
    onSuccess: (_data, variables) =>
      qc.invalidateQueries({ queryKey: ["sessions", variables.sessionId] }),
  });
};

export const useRemoveParticipant = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      sessionId,
      userId,
    }: {
      sessionId: number;
      userId: number;
    }) => removeParticipant(sessionId, userId),
    onSuccess: (_data, variables) =>
      qc.invalidateQueries({ queryKey: ["sessions", variables.sessionId] }),
  });
};
