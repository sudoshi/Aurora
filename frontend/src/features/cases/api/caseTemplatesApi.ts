import apiClient from "@/lib/api-client";
import type { CaseTemplate } from "../types/case";

/** Unwrap Aurora's ApiResponse envelope {success, data} */
function unwrap<T>(response: { data: { data?: T; success?: boolean } | T }): T {
  const d = response.data;
  if (d && typeof d === "object" && "success" in d && "data" in d) {
    return (d as { data: T }).data;
  }
  return d as T;
}

export const getCaseTemplates = (activeOnly = true): Promise<CaseTemplate[]> =>
  apiClient
    .get("/case-templates", { params: activeOnly ? { active: 1 } : {} })
    .then((r) => unwrap<CaseTemplate[]>(r));
