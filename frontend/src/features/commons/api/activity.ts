import { useQuery } from "@tanstack/react-query";
import apiClient from "@/lib/api-client";
import type { ActivityItem } from "../types";
import { ACTIVITIES_KEY } from "./keys";

// ---------------------------------------------------------------------------
// API functions
// ---------------------------------------------------------------------------

async function fetchActivities(slug: string): Promise<ActivityItem[]> {
  const { data } = await apiClient.get<{ data: ActivityItem[] }>(
    `/api/commons/channels/${slug}/activities`,
  );
  return data.data;
}

// ---------------------------------------------------------------------------
// TanStack Query hooks
// ---------------------------------------------------------------------------

export function useActivities(slug: string) {
  return useQuery({
    queryKey: [ACTIVITIES_KEY, slug],
    queryFn: () => fetchActivities(slug),
    enabled: !!slug,
    staleTime: 30_000,
  });
}
