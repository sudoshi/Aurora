import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import apiClient from "@/lib/api-client";
import type { PinnedMessage } from "../types";
import { PINS_KEY } from "./keys";

// ---------------------------------------------------------------------------
// API functions
// ---------------------------------------------------------------------------

async function fetchPins(slug: string): Promise<PinnedMessage[]> {
  const { data } = await apiClient.get<{ data: PinnedMessage[] }>(
    `/api/commons/channels/${slug}/pins`,
  );
  return data.data;
}

async function pinMessage(slug: string, messageId: number): Promise<PinnedMessage> {
  const { data } = await apiClient.post<{ data: PinnedMessage }>(
    `/api/commons/channels/${slug}/pins`,
    { message_id: messageId },
  );
  return data.data;
}

async function unpinMessage(slug: string, pinId: number): Promise<void> {
  await apiClient.delete(`/api/commons/channels/${slug}/pins/${pinId}`);
}

// ---------------------------------------------------------------------------
// TanStack Query hooks
// ---------------------------------------------------------------------------

export function usePins(slug: string) {
  return useQuery({
    queryKey: [PINS_KEY, slug],
    queryFn: () => fetchPins(slug),
    enabled: !!slug,
  });
}

export function usePinMessage() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ slug, messageId }: { slug: string; messageId: number }) =>
      pinMessage(slug, messageId),
    onSuccess: (_data, variables) => {
      void qc.invalidateQueries({ queryKey: [PINS_KEY, variables.slug] });
    },
  });
}

export function useUnpinMessage() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ slug, pinId }: { slug: string; pinId: number }) =>
      unpinMessage(slug, pinId),
    onSuccess: (_data, variables) => {
      void qc.invalidateQueries({ queryKey: [PINS_KEY, variables.slug] });
    },
  });
}
