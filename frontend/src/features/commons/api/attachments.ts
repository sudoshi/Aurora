import { useMutation, useQueryClient } from "@tanstack/react-query";
import apiClient from "@/lib/api-client";
import type { Attachment } from "../types";
import { MESSAGES_KEY } from "./keys";

// ---------------------------------------------------------------------------
// API functions
// ---------------------------------------------------------------------------

async function uploadAttachment(
  slug: string,
  messageId: number,
  file: File,
): Promise<Attachment> {
  const formData = new FormData();
  formData.append("file", file);
  formData.append("message_id", String(messageId));
  const { data } = await apiClient.post<{ data: Attachment }>(
    `/api/commons/channels/${slug}/attachments`,
    formData,
    { headers: { "Content-Type": "multipart/form-data" } },
  );
  return data.data;
}

// ---------------------------------------------------------------------------
// TanStack Query hooks
// ---------------------------------------------------------------------------

export function useUploadAttachment() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      slug,
      messageId,
      file,
    }: {
      slug: string;
      messageId: number;
      file: File;
    }) => uploadAttachment(slug, messageId, file),
    onSuccess: (_data, variables) => {
      void qc.invalidateQueries({ queryKey: [MESSAGES_KEY, variables.slug] });
    },
  });
}
