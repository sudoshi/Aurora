import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { acknowledgeKbAlert, getPatientKbAlerts } from "../api/reanalysisApi";
import type { KbAlertStatus } from "../types";

const KEY = "reanalysis";

export function usePatientKbAlerts(patientId: number, status?: KbAlertStatus) {
  return useQuery({
    queryKey: [KEY, "patient", patientId, status],
    queryFn: () => getPatientKbAlerts(patientId, status),
    enabled: Number.isFinite(patientId) && patientId > 0,
  });
}

export function useAcknowledgeKbAlert(patientId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { alertId: number; status: "acknowledged" | "dismissed"; resolution_note?: string }) =>
      acknowledgeKbAlert(payload.alertId, { status: payload.status, resolution_note: payload.resolution_note }),
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, "patient", patientId] }),
  });
}
