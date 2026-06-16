import apiClient from "@/lib/api-client";
import type { KbAlertPage, KbAlertSeverity, KbAlertStatus, KbChangeAlert } from "../types";

export async function getPatientKbAlerts(patientId: number, status?: KbAlertStatus): Promise<KbChangeAlert[]> {
  const { data } = await apiClient.get(`/patients/${patientId}/kb-alerts`, { params: status ? { status } : {} });
  return data.data ?? data;
}

export async function getKbAlertWorklist(
  params?: { status?: KbAlertStatus; severity?: KbAlertSeverity },
): Promise<KbAlertPage> {
  const { data } = await apiClient.get("/kb-alerts", { params });
  return data;
}

export async function acknowledgeKbAlert(
  alertId: number,
  payload: { status: "acknowledged" | "dismissed"; resolution_note?: string },
): Promise<KbChangeAlert> {
  const { data } = await apiClient.post(`/kb-alerts/${alertId}/acknowledge`, payload);
  return data.data ?? data;
}
