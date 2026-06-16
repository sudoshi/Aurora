import { useMutation, useQueryClient } from "@tanstack/react-query";
import { draftDecision, recordDecision } from "../api/decisionDraftApi";
import type { DraftSource } from "../types/decisionDraft";

export function useDraftDecision(caseId: number) {
  return useMutation({
    mutationFn: () => draftDecision(caseId),
  });
}

export function useRecordDecision(caseId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: {
      decision_type: string;
      recommendation: string;
      rationale: string;
      ai_generated: true;
      ai_model: string;
      ai_confidence: number;
      ai_sources: DraftSource[];
    }) => recordDecision(caseId, payload),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ["decisions", caseId] });
    },
  });
}
