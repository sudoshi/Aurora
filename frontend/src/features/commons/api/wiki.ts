import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import apiClient from "@/lib/api-client";
import type { WikiArticle, WikiRevision } from "../types";
import { WIKI_KEY } from "./keys";

// ---------------------------------------------------------------------------
// API functions
// ---------------------------------------------------------------------------

async function fetchWikiArticles(query?: string, tag?: string): Promise<WikiArticle[]> {
  const params = new URLSearchParams();
  if (query) params.set("q", query);
  if (tag) params.set("tag", tag);
  const { data } = await apiClient.get<{ data: WikiArticle[] }>(
    `/api/commons/wiki?${params.toString()}`,
  );
  return data.data;
}

async function fetchWikiArticle(slug: string): Promise<WikiArticle> {
  const { data } = await apiClient.get<{ data: WikiArticle }>(`/api/commons/wiki/${slug}`);
  return data.data;
}

async function createWikiArticle(payload: {
  title: string;
  body: string;
  tags?: string[];
}): Promise<WikiArticle> {
  const { data } = await apiClient.post<{ data: WikiArticle }>("/api/commons/wiki", payload);
  return data.data;
}

async function updateWikiArticle(
  slug: string,
  payload: { title?: string; body?: string; tags?: string[]; edit_summary?: string },
): Promise<WikiArticle> {
  const { data } = await apiClient.patch<{ data: WikiArticle }>(`/api/commons/wiki/${slug}`, payload);
  return data.data;
}

async function deleteWikiArticle(slug: string): Promise<void> {
  await apiClient.delete(`/api/commons/wiki/${slug}`);
}

async function fetchWikiRevisions(slug: string): Promise<WikiRevision[]> {
  const { data } = await apiClient.get<{ data: WikiRevision[] }>(`/api/commons/wiki/${slug}/revisions`);
  return data.data;
}

// ---------------------------------------------------------------------------
// TanStack Query hooks
// ---------------------------------------------------------------------------

export function useWikiArticles(query?: string, tag?: string) {
  return useQuery({
    queryKey: [WIKI_KEY, "list", query, tag],
    queryFn: () => fetchWikiArticles(query, tag),
  });
}

export function useWikiArticle(slug: string) {
  return useQuery({
    queryKey: [WIKI_KEY, slug],
    queryFn: () => fetchWikiArticle(slug),
    enabled: !!slug,
  });
}

export function useCreateWikiArticle() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: createWikiArticle,
    onSuccess: () => void qc.invalidateQueries({ queryKey: [WIKI_KEY] }),
  });
}

export function useUpdateWikiArticle() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ slug, ...payload }: { slug: string; title?: string; body?: string; tags?: string[]; edit_summary?: string }) =>
      updateWikiArticle(slug, payload),
    onSuccess: () => void qc.invalidateQueries({ queryKey: [WIKI_KEY] }),
  });
}

export function useDeleteWikiArticle() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: deleteWikiArticle,
    onSuccess: () => void qc.invalidateQueries({ queryKey: [WIKI_KEY] }),
  });
}

export function useWikiRevisions(slug: string) {
  return useQuery({
    queryKey: [WIKI_KEY, slug, "revisions"],
    queryFn: () => fetchWikiRevisions(slug),
    enabled: !!slug,
  });
}
