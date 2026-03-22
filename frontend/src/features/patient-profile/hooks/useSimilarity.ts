import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { searchSimilarPatients, embedPatient } from "../api/similarityApi";

export function useSimilarPatients(patientId: number | null, topK = 10) {
  return useQuery({
    queryKey: ["similar-patients", patientId, topK],
    queryFn: () => searchSimilarPatients(patientId!, topK),
    enabled: patientId != null,
    staleTime: 5 * 60_000,
  });
}

export function useEmbedPatient() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (patientId: number) => embedPatient(patientId),
    onSuccess: (_, patientId) => {
      qc.invalidateQueries({ queryKey: ["similar-patients", patientId] });
    },
  });
}
