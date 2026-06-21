import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import apiClient from "@/lib/api-client";
import type { ChannelMember } from "../types";
import { CHANNELS_KEY, MEMBERS_KEY } from "./keys";

// ---------------------------------------------------------------------------
// API functions
// ---------------------------------------------------------------------------

async function fetchMembers(slug: string): Promise<ChannelMember[]> {
  const { data } = await apiClient.get<{ data: ChannelMember[] }>(
    `/api/commons/channels/${slug}/members`,
  );
  return data.data;
}

async function joinChannel(slug: string): Promise<ChannelMember> {
  const { data } = await apiClient.post<{ data: ChannelMember }>(
    `/api/commons/channels/${slug}/members`,
  );
  return data.data;
}

async function updateNotificationPreference(
  slug: string,
  memberId: number,
  preference: "all" | "mentions" | "none",
): Promise<ChannelMember> {
  const { data } = await apiClient.patch<{ data: ChannelMember }>(
    `/api/commons/channels/${slug}/members/${memberId}`,
    { notification_preference: preference },
  );
  return data.data;
}

// ---------------------------------------------------------------------------
// TanStack Query hooks
// ---------------------------------------------------------------------------

export function useMembers(slug: string) {
  return useQuery({
    queryKey: [MEMBERS_KEY, slug],
    queryFn: () => fetchMembers(slug),
    enabled: !!slug,
  });
}

export function useJoinChannel() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: joinChannel,
    onSuccess: () => void qc.invalidateQueries({ queryKey: [CHANNELS_KEY] }),
  });
}

export function useUpdateNotificationPreference() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      slug,
      memberId,
      preference,
    }: {
      slug: string;
      memberId: number;
      preference: "all" | "mentions" | "none";
    }) => updateNotificationPreference(slug, memberId, preference),
    onSuccess: (_data, variables) => {
      void qc.invalidateQueries({ queryKey: [MEMBERS_KEY, variables.slug] });
    },
  });
}
