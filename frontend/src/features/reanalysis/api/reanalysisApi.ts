import apiClient from "@/lib/api-client";
import type { KbAlertStatus, KbChangeAlert } from "../types";

export async function getPatientKbAlerts(patientId: number, status?: KbAlertStatus): Promise<KbChangeAlert[]> {
  const { data } = await apiClient.get(`/patients/${patientId}/kb-alerts`, { params: status ? { status } : {} });
  return data.data ?? data;
}

export async function acknowledgeKbAlert(
  alertId: number,
  payload: { status: "acknowledged" | "dismissed"; resolution_note?: string },
): Promise<KbChangeAlert> {
  const { data } = await apiClient.post(`/kb-alerts/${alertId}/acknowledge`, payload);
  return data.data ?? data;
}
