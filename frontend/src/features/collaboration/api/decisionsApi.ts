import apiClient from "@/lib/api-client";
import type {
  Decision,
  PaginatedDecisions,
  DecisionFilters,
  CreateDecisionData,
  DecisionVote,
  CastVoteData,
  FollowUp,
  CreateFollowUpData,
  DecisionStatus,
} from "../types/decision";

// ── Decisions CRUD ───────────────────────────────────────────────────────────

export const getDecisions = (filters: DecisionFilters = {}) =>
  apiClient
    .get<PaginatedDecisions>("/decisions", { params: filters })
    .then((r) => r.data);

export const getDecision = (id: number) =>
  apiClient.get<Decision>(`/decisions/${id}`).then((r) => r.data);

export const createDecision = (data: CreateDecisionData) =>
  apiClient.post<Decision>("/decisions", data).then((r) => r.data);

export const updateDecisionStatus = (id: number, status: DecisionStatus) =>
  apiClient
    .put<Decision>(`/decisions/${id}/status`, { status })
    .then((r) => r.data);

export const finalizeDecision = (id: number) =>
  apiClient.post<Decision>(`/decisions/${id}/finalize`).then((r) => r.data);

export const deleteDecision = (id: number) =>
  apiClient.delete(`/decisions/${id}`);

// ── Votes ────────────────────────────────────────────────────────────────────

export const castVote = (decisionId: number, data: CastVoteData) =>
  apiClient
    .post<DecisionVote>(`/decisions/${decisionId}/votes`, data)
    .then((r) => r.data);

export const getVotes = (decisionId: number) =>
  apiClient
    .get<DecisionVote[]>(`/decisions/${decisionId}/votes`)
    .then((r) => r.data);

// ── Follow-ups ───────────────────────────────────────────────────────────────

export const getFollowUps = (decisionId: number) =>
  apiClient
    .get<FollowUp[]>(`/decisions/${decisionId}/follow-ups`)
    .then((r) => r.data);

export const createFollowUp = (decisionId: number, data: CreateFollowUpData) =>
  apiClient
    .post<FollowUp>(`/decisions/${decisionId}/follow-ups`, data)
    .then((r) => r.data);

export const updateFollowUpStatus = (
  followUpId: number,
  status: string,
) =>
  apiClient
    .put<FollowUp>(`/follow-ups/${followUpId}/status`, { status })
    .then((r) => r.data);

// ── Dashboard ────────────────────────────────────────────────────────────────

export interface DecisionDashboardData {
  recent_decisions: Decision[];
  pending_follow_ups: FollowUp[];
  stats: {
    approved: number;
    pending: number;
    deferred: number;
    total: number;
  };
}

export const getDecisionDashboard = () =>
  apiClient
    .get<DecisionDashboardData>("/decisions/dashboard")
    .then((r) => r.data);
