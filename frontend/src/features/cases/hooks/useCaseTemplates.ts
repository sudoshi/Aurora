import { useQuery } from "@tanstack/react-query";
import { getCaseTemplates } from "../api/caseTemplatesApi";

export function useCaseTemplates(activeOnly = true) {
  return useQuery({
    queryKey: ["case-templates", { activeOnly }],
    queryFn: () => getCaseTemplates(activeOnly),
    staleTime: 5 * 60 * 1000,
  });
}
