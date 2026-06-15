import apiClient from "@/lib/api-client";
import type {
  AcmgCatalog,
  AcmgClassification,
  AcmgStrength,
  CreateClassificationInput,
  VariantClassification,
} from "../types";

export async function getAcmgCatalog(): Promise<AcmgCatalog> {
  const { data } = await apiClient.get("/acmg/criteria");
  return data.data ?? data;
}

export async function getClassification(id: number): Promise<VariantClassification> {
  const { data } = await apiClient.get(`/classifications/${id}`);
  return data.data ?? data;
}

export async function createClassification(
  variantId: number,
  input: CreateClassificationInput,
): Promise<VariantClassification> {
  const { data } = await apiClient.post(`/genomic-variants/${variantId}/classifications`, input);
  return data.data ?? data;
}

export async function addCriterion(
  classificationId: number,
  payload: { code: string; applied_strength: AcmgStrength; rationale?: string },
): Promise<VariantClassification> {
  const { data } = await apiClient.post(`/classifications/${classificationId}/criteria`, payload);
  return data.data ?? data;
}

export async function deleteCriterion(criterionId: number): Promise<VariantClassification> {
  const { data } = await apiClient.delete(`/classification-criteria/${criterionId}`);
  return data.data ?? data;
}

export async function confirmClassification(
  classificationId: number,
  payload: { final_classification: AcmgClassification; override_reason?: string },
): Promise<VariantClassification> {
  const { data } = await apiClient.post(`/classifications/${classificationId}/confirm`, payload);
  return data.data ?? data;
}
