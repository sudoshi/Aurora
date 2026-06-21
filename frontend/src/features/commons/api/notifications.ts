import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import apiClient from "@/lib/api-client";
import type { CommonsNotification } from "../types";
import { NOTIFICATIONS_KEY } from "./keys";

// ---------------------------------------------------------------------------
// API functions
// ---------------------------------------------------------------------------

async function fetchNotifications(): Promise<CommonsNotification[]> {
  const { data } = await apiClient.get<{ data: CommonsNotification[] }>(
    "/api/commons/notifications",
  );
  return data.data;
}

async function fetchUnreadNotificationCount(): Promise<number> {
  const { data } = await apiClient.get<{ data: { count: number } }>(
    "/api/commons/notifications/unread-count",
  );
  return data.data.count;
}

async function markNotificationsRead(ids?: number[]): Promise<void> {
  await apiClient.post("/api/commons/notifications/mark-read", { ids: ids ?? null });
}

// ---------------------------------------------------------------------------
// TanStack Query hooks
// ---------------------------------------------------------------------------

export function useNotifications() {
  return useQuery({
    queryKey: [NOTIFICATIONS_KEY],
    queryFn: fetchNotifications,
  });
}

export function useUnreadNotificationCount() {
  return useQuery({
    queryKey: [NOTIFICATIONS_KEY, "unread-count"],
    queryFn: fetchUnreadNotificationCount,
    refetchInterval: 30_000,
    staleTime: 15_000,
  });
}

export function useMarkNotificationsRead() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (ids?: number[]) => markNotificationsRead(ids),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: [NOTIFICATIONS_KEY] });
    },
  });
}
