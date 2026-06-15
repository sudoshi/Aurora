// Lightweight, dependency-free validation of a pasted GA4GH Phenopacket v2
// before sending it to the backend importer (which re-validates authoritatively
// and returns 422 on malformed input). Avoids adding an undeclared `zod` dependency.

export interface ValidatedPhenopacket {
  phenotypicFeatures: Array<{
    type: { id: string; label?: string };
    excluded?: boolean;
    [key: string]: unknown;
  }>;
  [key: string]: unknown;
}

export interface PhenopacketValidationResult {
  success: boolean;
  data?: ValidatedPhenopacket;
  error?: string;
}

const HPO_ID = /^HP:\d{7}$/;

export function validatePhenopacket(input: unknown): PhenopacketValidationResult {
  if (typeof input !== "object" || input === null) {
    return { success: false, error: "Phenopacket must be a JSON object" };
  }

  const features = (input as Record<string, unknown>).phenotypicFeatures;
  if (!Array.isArray(features) || features.length === 0) {
    return { success: false, error: "Phenopacket has no phenotypicFeatures to import" };
  }

  for (const feature of features) {
    const type =
      typeof feature === "object" && feature !== null
        ? ((feature as Record<string, unknown>).type as Record<string, unknown> | undefined)
        : undefined;
    const id = type?.id;
    if (typeof id !== "string" || !HPO_ID.test(id)) {
      return {
        success: false,
        error: "Each phenotype type.id must be a valid HPO id (HP:nnnnnnn)",
      };
    }
  }

  return { success: true, data: input as ValidatedPhenopacket };
}
