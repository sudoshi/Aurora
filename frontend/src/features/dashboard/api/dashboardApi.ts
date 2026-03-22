import apiClient from "@/lib/api-client";

export interface DashboardStats {
  total_patients: number;
  total_cases: number;
  active_users: number;
  pending_decisions: number;
  recent_cases: Array<{
    id: number;
    title: string;
    patient_name: string;
    status: string;
    priority: string;
    updated_at: string;
  }>;
  system_health: {
    status: "healthy" | "degraded" | "down";
    services: Array<{
      name: string;
      status: "healthy" | "degraded" | "down";
    }>;
  };
}

/**
 * Unified dashboard stats -- single API call for all dashboard data.
 */
export async function fetchDashboardStats(): Promise<DashboardStats> {
  const { data } = await apiClient.get("/dashboard/stats");
  const d = data.data ?? data;

  return {
    total_patients: d.total_patients ?? 0,
    total_cases: d.total_cases ?? 0,
    active_users: d.active_users ?? 0,
    pending_decisions: d.pending_decisions ?? 0,
    recent_cases: (d.recent_cases ?? []).map((c: Record<string, unknown>) => ({
      id: c.id,
      title: c.title ?? "Untitled Case",
      patient_name: c.patient_name ?? "Unknown",
      status: c.status ?? "open",
      priority: c.priority ?? "normal",
      updated_at: c.updated_at ?? "",
    })),
    system_health: d.system_health ?? { status: "healthy", services: [] },
  };
}
