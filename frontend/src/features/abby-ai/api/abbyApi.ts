import apiClient from "@/lib/api-client";
import type {
  AbbyAnalyzeRequest,
  AbbyAnalyzeResponse,
  AbbySuggestRequest,
  AbbySuggestResponse,
  AbbyExplainResponse,
  AbbyRefineRequest,
} from "../types/abby";
import type { AbbyProfileResponse, AbbyProfileUpdateRequest } from "../types/memory";

const BASE = "/api/abby";

export async function analyzeCase(
  data: AbbyAnalyzeRequest,
): Promise<AbbyAnalyzeResponse> {
  const { data: result } = await apiClient.post<AbbyAnalyzeResponse>(
    `${BASE}/analyze-case`,
    data,
  );
  return result;
}

export async function suggestFindings(
  data: AbbySuggestRequest,
): Promise<AbbySuggestResponse> {
  const { data: result } = await apiClient.post<AbbySuggestResponse>(
    `${BASE}/suggest-findings`,
    data,
  );
  return result;
}

export async function explainExpression(
  expression: Record<string, unknown>,
): Promise<AbbyExplainResponse> {
  const { data: result } = await apiClient.post<AbbyExplainResponse>(
    `${BASE}/explain`,
    { expression },
  );
  return result;
}

export async function refineAnalysis(
  data: AbbyRefineRequest,
): Promise<AbbyAnalyzeResponse> {
  const { data: result } = await apiClient.post<AbbyAnalyzeResponse>(
    `${BASE}/refine`,
    data,
  );
  return result;
}

export async function fetchAbbyProfile(): Promise<AbbyProfileResponse> {
  const { data } = await apiClient.get<AbbyProfileResponse>(`${BASE}/profile`);
  return data;
}

export async function updateAbbyProfile(
  payload: AbbyProfileUpdateRequest,
): Promise<AbbyProfileResponse> {
  const { data } = await apiClient.put<AbbyProfileResponse>(`${BASE}/profile`, payload);
  return data;
}

export async function resetAbbyProfile(): Promise<void> {
  await apiClient.post(`${BASE}/profile/reset`);
}
