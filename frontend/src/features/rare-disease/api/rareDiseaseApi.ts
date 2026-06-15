import apiClient from "@/lib/api-client";
import type {
  CreateOdysseyInput,
  CreatePhenotypeInput,
  DiagnosticOdyssey,
  HpoTerm,
  OdysseyDetail,
  OdysseyStatus,
  PhenopacketImportResult,
  PhenotypeFeature,
} from "../types";

interface Paginated<T> {
  data: T[];
  meta: { total: number; page: number; per_page: number; last_page: number };
}

export async function getOdysseyWorklist(params?: {
  status?: OdysseyStatus;
  progress_status?: string;
  per_page?: number;
  page?: number;
}): Promise<Paginated<DiagnosticOdyssey>> {
  const { data } = await apiClient.get("/odysseys", { params });
  return data;
}

export async function getOdyssey(id: number): Promise<OdysseyDetail> {
  const { data } = await apiClient.get(`/odysseys/${id}`);
  return data.data ?? data;
}

export async function createOdyssey(input: CreateOdysseyInput): Promise<DiagnosticOdyssey> {
  const { patient_id, ...body } = input;
  const { data } = await apiClient.post(`/patients/${patient_id}/odysseys`, body);
  return data.data ?? data;
}

export async function transitionOdyssey(
  id: number,
  to_status: OdysseyStatus,
  note?: string,
): Promise<DiagnosticOdyssey> {
  const { data } = await apiClient.post(`/odysseys/${id}/transition`, { to_status, note });
  return data.data ?? data;
}

export async function listPhenotypes(odysseyId: number): Promise<PhenotypeFeature[]> {
  const { data } = await apiClient.get(`/odysseys/${odysseyId}/phenotypes`);
  return data.data ?? data;
}

export async function addPhenotype(
  odysseyId: number,
  input: CreatePhenotypeInput,
): Promise<PhenotypeFeature> {
  const { data } = await apiClient.post(`/odysseys/${odysseyId}/phenotypes`, input);
  return data.data ?? data;
}

export async function deletePhenotype(phenotypeId: number): Promise<void> {
  await apiClient.delete(`/phenotypes/${phenotypeId}`);
}

export async function exportPhenopacket(odysseyId: number): Promise<Record<string, unknown>> {
  const { data } = await apiClient.get(`/odysseys/${odysseyId}/phenopacket`);
  return data.data ?? data;
}

export async function importPhenopacket(
  odysseyId: number,
  packet: Record<string, unknown>,
): Promise<PhenopacketImportResult> {
  const { data } = await apiClient.post(`/odysseys/${odysseyId}/import-phenopacket`, packet);
  return data.data ?? data;
}

export async function searchHpo(q: string, limit = 10): Promise<HpoTerm[]> {
  const { data } = await apiClient.get("/hpo/search", { params: { q, limit } });
  return data.data ?? data;
}
