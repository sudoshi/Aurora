import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { getMmeMatches, runMmeSearch } from "../api/matchmakerApi";

const KEY = "matchmaker";

export function useMmeMatches(odysseyId: number) {
  return useQuery({
    queryKey: [KEY, "matches", odysseyId],
    queryFn: () => getMmeMatches(odysseyId),
    enabled: Number.isFinite(odysseyId) && odysseyId > 0,
  });
}

export function useRunMmeSearch(odysseyId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => runMmeSearch(odysseyId),
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, "matches", odysseyId] }),
  });
}
