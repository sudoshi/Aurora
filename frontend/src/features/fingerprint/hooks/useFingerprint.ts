import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  assessOutcome,
  encodeBatch,
  encodePatient,
  getActiveWeights,
  getFingerprint,
  getFingerprintStats,
  getOutcome,
  listWeights,
  searchSimilar,
} from "../api/fingerprintApi";
import type { DimensionWeights, OutcomeAssessmentPayload, SearchContext } from "../types";

// -- Search -----------------------------------------------------------------

export function useSimilarPatients(params: {
  patient_id: number;
  weights?: Partial<DimensionWeights>;
  limit?: number;
  context?: SearchContext;
}) {
  return useQuery({
    queryKey: ["fingerprint", "search", params],
    queryFn: () => searchSimilar(params),
    enabled: params.patient_id > 0,
    refetchOnWindowFocus: false, // POST endpoint logs searches — avoid duplicate audit entries
    staleTime: 5 * 60 * 1000, // 5 minutes — similarity results don't change frequently
  });
}

// -- Fingerprint ------------------------------------------------------------

export function usePatientFingerprint(patientId: number) {
  return useQuery({
    queryKey: ["fingerprint", "patient", patientId],
    queryFn: () => getFingerprint(patientId),
    enabled: patientId > 0,
  });
}

export function useEncodePatient() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (patientId: number) => encodePatient(patientId),
    onSuccess: (_data, patientId) => {
      qc.invalidateQueries({ queryKey: ["fingerprint", "patient", patientId] });
      qc.invalidateQueries({ queryKey: ["fingerprint", "search"] });
      qc.invalidateQueries({ queryKey: ["fingerprint", "stats"] });
    },
  });
}

export function useEncodeBatch() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (patientIds: number[]) => encodeBatch(patientIds),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["fingerprint"] });
    },
  });
}

// -- Outcomes ---------------------------------------------------------------

export function usePatientOutcome(patientId: number) {
  return useQuery({
    queryKey: ["fingerprint", "outcome", patientId],
    queryFn: () => getOutcome(patientId),
    enabled: patientId > 0,
  });
}

export function useAssessOutcome() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ patientId, payload }: { patientId: number; payload: OutcomeAssessmentPayload }) =>
      assessOutcome(patientId, payload),
    onSuccess: (_data, { patientId }) => {
      qc.invalidateQueries({ queryKey: ["fingerprint", "outcome", patientId] });
      qc.invalidateQueries({ queryKey: ["fingerprint", "search"] });
    },
  });
}

// -- Weights ----------------------------------------------------------------

export function useWeightPresets() {
  return useQuery({
    queryKey: ["fingerprint", "weights"],
    queryFn: listWeights,
  });
}

export function useActiveWeights() {
  return useQuery({
    queryKey: ["fingerprint", "weights", "active"],
    queryFn: getActiveWeights,
  });
}

// -- Stats ------------------------------------------------------------------

export function useFingerprintStats() {
  return useQuery({
    queryKey: ["fingerprint", "stats"],
    queryFn: getFingerprintStats,
  });
}
