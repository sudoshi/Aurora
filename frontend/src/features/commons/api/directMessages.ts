import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import apiClient from "@/lib/api-client";
import type { Channel, DirectMessage } from "../types";
import { DM_KEY } from "./keys";

// ---------------------------------------------------------------------------
// API functions
// ---------------------------------------------------------------------------

async function fetchDirectMessages(): Promise<DirectMessage[]> {
  const { data } = await apiClient.get<{ data: DirectMessage[] }>("/api/commons/dm");
  return data.data;
}

async function createDirectMessage(userId: number): Promise<Channel> {
  const { data } = await apiClient.post<{ data: Channel }>("/api/commons/dm", {
    user_id: userId,
  });
  return data.data;
}

// ---------------------------------------------------------------------------
// TanStack Query hooks
// ---------------------------------------------------------------------------

export function useDirectMessages() {
  return useQuery({
    queryKey: [DM_KEY],
    queryFn: fetchDirectMessages,
  });
}

export function useCreateDirectMessage() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: createDirectMessage,
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: [DM_KEY] });
    },
  });
}
