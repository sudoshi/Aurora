import apiClient from "@/lib/api-client";
import type { DecisionDraft, DraftSource } from "../types/decisionDraft";

export async function draftDecision(caseId: number): Promise<DecisionDraft> {
  const { data } = await apiClient.post(`/cases/${caseId}/decisions/draft`);
  return data.data ?? data;
}

export async function recordDecision(
  caseId: number,
  payload: {
    decision_type: string;
    recommendation: string;
    rationale: string;
    ai_generated: true;
    ai_model: string;
    ai_confidence: number;
    ai_sources: DraftSource[];
  },
): Promise<unknown> {
  const { data } = await apiClient.post(`/cases/${caseId}/decisions`, payload);
  return data.data ?? data;
}
