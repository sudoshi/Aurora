import { useQuery } from "@tanstack/react-query";
import apiClient from "@/lib/api-client";
import type { ObjectSearchResult, SearchResult } from "../types";
import { SEARCH_KEY } from "./keys";

// ---------------------------------------------------------------------------
// API functions
// ---------------------------------------------------------------------------

async function searchMessages(
  query: string,
  channel?: string,
): Promise<SearchResult[]> {
  const params = new URLSearchParams({ q: query });
  if (channel) params.set("channel", channel);
  const { data } = await apiClient.get<{ data: SearchResult[] }>(
    `/api/commons/messages/search?${params.toString()}`,
  );
  return data.data;
}

async function searchObjects(
  query: string,
  type?: string,
): Promise<ObjectSearchResult[]> {
  const params = new URLSearchParams({ q: query });
  if (type) params.set("type", type);
  const { data } = await apiClient.get<{ data: ObjectSearchResult[] }>(
    `/api/commons/objects/search?${params.toString()}`,
  );
  return data.data;
}

// ---------------------------------------------------------------------------
// TanStack Query hooks
// ---------------------------------------------------------------------------

export function useSearchMessages(query: string, channel?: string) {
  return useQuery({
    queryKey: [SEARCH_KEY, query, channel],
    queryFn: () => searchMessages(query, channel),
    enabled: query.length >= 2,
    staleTime: 30_000,
  });
}

export function useSearchObjects(query: string, type?: string) {
  return useQuery({
    queryKey: ["commons-objects", query, type],
    queryFn: () => searchObjects(query, type),
    enabled: query.length >= 2,
    staleTime: 30_000,
  });
}
