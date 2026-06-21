import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import apiClient from "@/lib/api-client";
import type { Announcement } from "../types";
import { ANNOUNCEMENTS_KEY } from "./keys";

// ---------------------------------------------------------------------------
// API functions
// ---------------------------------------------------------------------------

async function fetchAnnouncements(
  channelSlug?: string,
  category?: string,
): Promise<Announcement[]> {
  const params = new URLSearchParams();
  if (channelSlug) params.set("channel", channelSlug);
  if (category) params.set("category", category);
  const { data } = await apiClient.get<{ data: Announcement[] }>(
    `/api/commons/announcements?${params.toString()}`,
  );
  return data.data;
}

async function createAnnouncement(payload: {
  title: string;
  body: string;
  category?: string;
  channel_slug?: string;
  is_pinned?: boolean;
  expires_at?: string;
}): Promise<Announcement> {
  const { data } = await apiClient.post<{ data: Announcement }>(
    "/api/commons/announcements",
    payload,
  );
  return data.data;
}

async function updateAnnouncement(
  id: number,
  payload: { title?: string; body?: string; category?: string; is_pinned?: boolean; expires_at?: string | null },
): Promise<Announcement> {
  const { data } = await apiClient.patch<{ data: Announcement }>(
    `/api/commons/announcements/${id}`,
    payload,
  );
  return data.data;
}

async function deleteAnnouncement(id: number): Promise<void> {
  await apiClient.delete(`/api/commons/announcements/${id}`);
}

async function toggleBookmark(id: number): Promise<{ bookmarked: boolean }> {
  const { data } = await apiClient.post<{ data: { bookmarked: boolean } }>(
    `/api/commons/announcements/${id}/bookmark`,
  );
  return data.data;
}

// ---------------------------------------------------------------------------
// TanStack Query hooks
// ---------------------------------------------------------------------------

export function useAnnouncements(channelSlug?: string, category?: string) {
  return useQuery({
    queryKey: [ANNOUNCEMENTS_KEY, channelSlug, category],
    queryFn: () => fetchAnnouncements(channelSlug, category),
  });
}

export function useCreateAnnouncement() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: createAnnouncement,
    onSuccess: () => void qc.invalidateQueries({ queryKey: [ANNOUNCEMENTS_KEY] }),
  });
}

export function useUpdateAnnouncement() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, ...payload }: { id: number; title?: string; body?: string; category?: string; is_pinned?: boolean; expires_at?: string | null }) =>
      updateAnnouncement(id, payload),
    onSuccess: () => void qc.invalidateQueries({ queryKey: [ANNOUNCEMENTS_KEY] }),
  });
}

export function useDeleteAnnouncement() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: deleteAnnouncement,
    onSuccess: () => void qc.invalidateQueries({ queryKey: [ANNOUNCEMENTS_KEY] }),
  });
}

export function useToggleBookmark() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: toggleBookmark,
    onSuccess: () => void qc.invalidateQueries({ queryKey: [ANNOUNCEMENTS_KEY] }),
  });
}
