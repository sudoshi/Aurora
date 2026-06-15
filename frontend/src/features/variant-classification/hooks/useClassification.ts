import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  addCriterion,
  confirmClassification,
  createClassification,
  deleteCriterion,
  getAcmgCatalog,
  getClassification,
} from "../api/classificationApi";
import type { AcmgClassification, AcmgStrength, CreateClassificationInput } from "../types";

const KEY = "variant-classification";

export function useAcmgCatalog() {
  return useQuery({ queryKey: [KEY, "catalog"], queryFn: getAcmgCatalog, staleTime: 60 * 60 * 1000 });
}

export function useClassification(id: number | null) {
  return useQuery({
    queryKey: [KEY, "detail", id],
    queryFn: () => getClassification(id as number),
    enabled: typeof id === "number" && id > 0,
  });
}

export function useCreateClassification(variantId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CreateClassificationInput) => createClassification(variantId, input),
    onSuccess: (c) => qc.setQueryData([KEY, "detail", c.id], c),
  });
}

export function useAddCriterion(classificationId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { code: string; applied_strength: AcmgStrength; rationale?: string }) =>
      addCriterion(classificationId, payload),
    onSuccess: (c) => qc.setQueryData([KEY, "detail", classificationId], c),
  });
}

export function useDeleteCriterion(classificationId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (criterionId: number) => deleteCriterion(criterionId),
    onSuccess: (c) => qc.setQueryData([KEY, "detail", classificationId], c),
  });
}

export function useConfirmClassification(classificationId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { final_classification: AcmgClassification; override_reason?: string }) =>
      confirmClassification(classificationId, payload),
    onSuccess: (c) => qc.setQueryData([KEY, "detail", classificationId], c),
  });
}
