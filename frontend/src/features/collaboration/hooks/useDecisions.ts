import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  getDecisions,
  getDecision,
  createDecision,
  updateDecisionStatus,
  finalizeDecision,
  deleteDecision,
  castVote,
  getVotes,
  getFollowUps,
  createFollowUp,
  updateFollowUpStatus,
  getDecisionDashboard,
} from "../api/decisionsApi";
import type {
  DecisionFilters,
  CreateDecisionData,
  DecisionStatus,
  CastVoteData,
  CreateFollowUpData,
} from "../types/decision";

// ── Decision queries ─────────────────────────────────────────────────────────

export const useDecisions = (filters: DecisionFilters = {}) =>
  useQuery({
    queryKey: ["decisions", filters],
    queryFn: () => getDecisions(filters),
  });

export const useDecision = (id: number) =>
  useQuery({
    queryKey: ["decisions", id],
    queryFn: () => getDecision(id),
    enabled: id > 0,
  });

export const useDecisionDashboard = () =>
  useQuery({
    queryKey: ["decisions", "dashboard"],
    queryFn: getDecisionDashboard,
  });

// ── Decision mutations ───────────────────────────────────────────────────────

export const useCreateDecision = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateDecisionData) => createDecision(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["decisions"] });
      qc.invalidateQueries({ queryKey: ["cases"] });
    },
  });
};

export const useUpdateDecisionStatus = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, status }: { id: number; status: DecisionStatus }) =>
      updateDecisionStatus(id, status),
    onSuccess: (_data, variables) => {
      qc.invalidateQueries({ queryKey: ["decisions"] });
      qc.invalidateQueries({ queryKey: ["decisions", variables.id] });
    },
  });
};

export const useFinalizeDecision = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => finalizeDecision(id),
    onSuccess: (_data, id) => {
      qc.invalidateQueries({ queryKey: ["decisions"] });
      qc.invalidateQueries({ queryKey: ["decisions", id] });
    },
  });
};

export const useDeleteDecision = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => deleteDecision(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["decisions"] }),
  });
};

// ── Votes ────────────────────────────────────────────────────────────────────

export const useDecisionVotes = (decisionId: number) =>
  useQuery({
    queryKey: ["decisions", decisionId, "votes"],
    queryFn: () => getVotes(decisionId),
    enabled: decisionId > 0,
  });

export const useCastVote = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      decisionId,
      data,
    }: {
      decisionId: number;
      data: CastVoteData;
    }) => castVote(decisionId, data),
    onSuccess: (_data, variables) => {
      qc.invalidateQueries({ queryKey: ["decisions", variables.decisionId] });
      qc.invalidateQueries({
        queryKey: ["decisions", variables.decisionId, "votes"],
      });
    },
  });
};

// ── Follow-ups ───────────────────────────────────────────────────────────────

export const useFollowUps = (decisionId: number) =>
  useQuery({
    queryKey: ["decisions", decisionId, "follow-ups"],
    queryFn: () => getFollowUps(decisionId),
    enabled: decisionId > 0,
  });

export const useCreateFollowUp = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      decisionId,
      data,
    }: {
      decisionId: number;
      data: CreateFollowUpData;
    }) => createFollowUp(decisionId, data),
    onSuccess: (_data, variables) =>
      qc.invalidateQueries({
        queryKey: ["decisions", variables.decisionId, "follow-ups"],
      }),
  });
};

export const useUpdateFollowUpStatus = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      followUpId,
      status,
    }: {
      followUpId: number;
      status: string;
    }) => updateFollowUpStatus(followUpId, status),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["decisions"] }),
  });
};
