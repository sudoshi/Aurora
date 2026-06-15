import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  addPhenotype,
  createOdyssey,
  deletePhenotype,
  exportPhenopacket,
  getOdyssey,
  getOdysseyWorklist,
  importPhenopacket,
  listPhenotypes,
  searchHpo,
  transitionOdyssey,
} from "../api/rareDiseaseApi";
import { useDebounce } from "./useDebounce";
import type { CreateOdysseyInput, CreatePhenotypeInput, OdysseyStatus } from "../types";

const KEY = "rare-disease";

export function useOdysseyWorklist(params?: { status?: OdysseyStatus; per_page?: number; page?: number }) {
  return useQuery({
    queryKey: [KEY, "worklist", params],
    queryFn: () => getOdysseyWorklist(params),
  });
}

export function useOdyssey(id: number) {
  return useQuery({
    queryKey: [KEY, "odyssey", id],
    queryFn: () => getOdyssey(id),
    enabled: Number.isFinite(id) && id > 0,
  });
}

export function useCreateOdyssey() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CreateOdysseyInput) => createOdyssey(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, "worklist"] }),
  });
}

export function useTransitionOdyssey(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { to_status: OdysseyStatus; note?: string }) =>
      transitionOdyssey(id, payload.to_status, payload.note),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [KEY, "odyssey", id] });
      qc.invalidateQueries({ queryKey: [KEY, "worklist"] });
    },
  });
}

export function usePhenotypes(odysseyId: number) {
  return useQuery({
    queryKey: [KEY, "phenotypes", odysseyId],
    queryFn: () => listPhenotypes(odysseyId),
    enabled: Number.isFinite(odysseyId) && odysseyId > 0,
  });
}

export function useAddPhenotype(odysseyId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CreatePhenotypeInput) => addPhenotype(odysseyId, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [KEY, "phenotypes", odysseyId] });
      qc.invalidateQueries({ queryKey: [KEY, "odyssey", odysseyId] });
    },
  });
}

export function useDeletePhenotype(odysseyId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (phenotypeId: number) => deletePhenotype(phenotypeId),
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, "phenotypes", odysseyId] }),
  });
}

export function useImportPhenopacket(odysseyId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (packet: Record<string, unknown>) => importPhenopacket(odysseyId, packet),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [KEY, "phenotypes", odysseyId] });
      qc.invalidateQueries({ queryKey: [KEY, "odyssey", odysseyId] });
    },
  });
}

export function useExportPhenopacket() {
  return useMutation({ mutationFn: (odysseyId: number) => exportPhenopacket(odysseyId) });
}

/** Debounced HPO autocomplete; only queries at >= 2 chars. */
export function useHpoSearch(query: string) {
  const debounced = useDebounce(query.trim(), 300);
  return useQuery({
    queryKey: [KEY, "hpo", debounced],
    queryFn: () => searchHpo(debounced),
    enabled: debounced.length >= 2,
    staleTime: 5 * 60 * 1000,
  });
}
