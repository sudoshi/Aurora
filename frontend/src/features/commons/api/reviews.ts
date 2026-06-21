import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import apiClient from "@/lib/api-client";
import type { ReviewRequest } from "../types";
import { REVIEWS_KEY } from "./keys";

// ---------------------------------------------------------------------------
// API functions
// ---------------------------------------------------------------------------

async function fetchReviews(slug: string): Promise<ReviewRequest[]> {
  const { data } = await apiClient.get<{ data: ReviewRequest[] }>(
    `/api/commons/channels/${slug}/reviews`,
  );
  return data.data;
}

async function createReviewRequest(
  slug: string,
  messageId: number,
  reviewerId?: number,
): Promise<ReviewRequest> {
  const { data } = await apiClient.post<{ data: ReviewRequest }>(
    `/api/commons/channels/${slug}/reviews`,
    { message_id: messageId, reviewer_id: reviewerId ?? null },
  );
  return data.data;
}

async function resolveReview(
  id: number,
  status: "approved" | "changes_requested",
  comment?: string,
): Promise<ReviewRequest> {
  const { data } = await apiClient.patch<{ data: ReviewRequest }>(
    `/api/commons/reviews/${id}/resolve`,
    { status, comment: comment ?? null },
  );
  return data.data;
}

// ---------------------------------------------------------------------------
// TanStack Query hooks
// ---------------------------------------------------------------------------

export function useReviews(slug: string) {
  return useQuery({
    queryKey: [REVIEWS_KEY, slug],
    queryFn: () => fetchReviews(slug),
    enabled: !!slug,
  });
}

export function useCreateReviewRequest() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      slug,
      messageId,
      reviewerId,
    }: {
      slug: string;
      messageId: number;
      reviewerId?: number;
    }) => createReviewRequest(slug, messageId, reviewerId),
    onSuccess: (_data, variables) => {
      void qc.invalidateQueries({ queryKey: [REVIEWS_KEY, variables.slug] });
    },
  });
}

export function useResolveReview() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      id,
      status,
      comment,
    }: {
      id: number;
      slug: string;
      status: "approved" | "changes_requested";
      comment?: string;
    }) => resolveReview(id, status, comment),
    onSuccess: (_data, variables) => {
      void qc.invalidateQueries({ queryKey: [REVIEWS_KEY, variables.slug] });
    },
  });
}
