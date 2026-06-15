import { z } from "zod";

const hpoId = z.string().regex(/^HP:\d{7}$/, "Each phenotype type.id must be a valid HPO id (HP:nnnnnnn)");

export const phenopacketImportSchema = z
  .object({
    phenotypicFeatures: z
      .array(
        z.object({
          type: z.object({ id: hpoId, label: z.string().optional() }),
          excluded: z.boolean().optional(),
        }).passthrough(),
      )
      .min(1, "Phenopacket has no phenotypicFeatures to import"),
  })
  .passthrough();

export type ValidatedPhenopacket = z.infer<typeof phenopacketImportSchema>;
