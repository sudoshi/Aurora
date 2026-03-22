import { useMutation } from "@tanstack/react-query";
import {
  analyzeCase,
  suggestFindings,
  explainExpression,
  refineAnalysis,
} from "../api/abbyApi";
import type {
  AbbyAnalyzeRequest,
  AbbySuggestRequest,
  AbbyRefineRequest,
} from "../types/abby";

export function useAnalyzeCase() {
  return useMutation({
    mutationFn: (data: AbbyAnalyzeRequest) => analyzeCase(data),
  });
}

export function useSuggestFindings() {
  return useMutation({
    mutationFn: (data: AbbySuggestRequest) => suggestFindings(data),
  });
}

export function useExplainExpression() {
  return useMutation({
    mutationFn: (expression: Record<string, unknown>) =>
      explainExpression(expression),
  });
}

export function useRefineAnalysis() {
  return useMutation({
    mutationFn: (data: AbbyRefineRequest) => refineAnalysis(data),
  });
}
