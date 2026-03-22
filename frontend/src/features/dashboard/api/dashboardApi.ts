import apiClient from "@/lib/api-client";

export interface RecentCase {
  id: number;
  title: string;
  specialty: string;
  urgency: string;
  status: string;
  case_type: string;
  created_at: string;
  creator_name: string;
}

export interface DashboardStats {
  total_patients: number;
  total_cases: number;
  active_cases: number;
  active_users: number;
  total_users: number;
  pending_decisions: number;
  recent_cases: RecentCase[];
  system_health: Record<string, string>;
}

export async function fetchDashboardStats(): Promise<DashboardStats> {
  const { data } = await apiClient.get("/dashboard/stats");
  const d = data.data ?? data;

  return {
    total_patients: d.total_patients ?? 0,
    total_cases: d.total_cases ?? 0,
    active_cases: d.active_cases ?? 0,
    active_users: d.active_users ?? 0,
    total_users: d.total_users ?? 0,
    pending_decisions: d.pending_decisions ?? 0,
    recent_cases: d.recent_cases ?? [],
    system_health: d.system_health ?? {},
  };
}
