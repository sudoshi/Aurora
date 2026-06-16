import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { acknowledgeKbAlert, getKbAlertWorklist, getPatientKbAlerts } from "../api/reanalysisApi";
import type { KbAlertSeverity, KbAlertStatus, KbChangeAlert } from "../types";

const KEY = "reanalysis";

export function usePatientKbAlerts(patientId: number, status?: KbAlertStatus) {
  return useQuery({
    queryKey: [KEY, "patient", patientId, status],
    queryFn: () => getPatientKbAlerts(patientId, status),
    enabled: Number.isFinite(patientId) && patientId > 0,
  });
}

export function useKbAlertWorklist(filters?: { status?: KbAlertStatus; severity?: KbAlertSeverity }) {
  return useQuery({
    queryKey: [KEY, "worklist", filters?.status, filters?.severity],
    queryFn: () => getKbAlertWorklist(filters),
  });
}

export function useAcknowledgeKbAlert(patientId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { alertId: number; status: "acknowledged" | "dismissed"; resolution_note?: string }) =>
      acknowledgeKbAlert(payload.alertId, { status: payload.status, resolution_note: payload.resolution_note }),
    onSuccess: (updated, payload) => {
      // Update all matching cache entries that hold this alert; avoid a stale
      // refetch overwriting the new status from the server response.
      qc.setQueriesData<KbChangeAlert[]>(
        { queryKey: [KEY, "patient", patientId] },
        (old) =>
          (old ?? []).map((a) =>
            a.id === payload.alertId ? { ...a, status: updated.status ?? payload.status } : a,
          ),
      );
    },
    onError: () => {
      void qc.invalidateQueries({ queryKey: [KEY, "patient", patientId] });
    },
  });
}
