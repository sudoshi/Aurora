import { useQuery } from "@tanstack/react-query";
import { listAbbyConversations } from "../services/abbyService";

// ---------------------------------------------------------------------------
// TanStack Query hooks
// ---------------------------------------------------------------------------

export function useAbbyConversations() {
  return useQuery({
    queryKey: ["abby", "conversations"],
    queryFn: listAbbyConversations,
    staleTime: 60_000,
  });
}
