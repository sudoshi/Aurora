import apiClient from "@/lib/api-client";
import type { MmeMatch } from "../types";

export async function getMmeMatches(odysseyId: number): Promise<MmeMatch[]> {
  const { data } = await apiClient.get(`/odysseys/${odysseyId}/mme-matches`);
  return data.data ?? data;
}

export async function runMmeSearch(odysseyId: number): Promise<{ stored: number }> {
  const { data } = await apiClient.post(`/odysseys/${odysseyId}/mme-search`);
  return data.data ?? data;
}
