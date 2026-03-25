import { describe, it, expect, afterEach } from "vitest";
import { waitFor, act } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderHookWithProviders, resetStores } from "@/test/utils";
import {
  useGeneDrugInteractions,
  useGenomicVariants,
  useRadiogenomicsPanel,
  useGenomicBriefing,
} from "../useGenomics";

afterEach(() => {
  resetStores();
});

// ---------------------------------------------------------------------------
// useGeneDrugInteractions
// ---------------------------------------------------------------------------

describe("useGeneDrugInteractions", () => {
  it("fetches gene-drug interactions", async () => {
    server.use(
      http.get("/api/genomics/interactions", () =>
        HttpResponse.json({
          success: true,
          data: [
            { id: 1, gene: "BRCA1", drug: "Olaparib", evidence_level: "A" },
          ],
        }),
      ),
    );

    const { result } = renderHookWithProviders(() =>
      useGeneDrugInteractions("BRCA1"),
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(Array.isArray(result.current.data)).toBe(true);
    expect(result.current.data!.length).toBeGreaterThanOrEqual(1);
    expect(result.current.data![0]).toMatchObject({
      gene: "BRCA1",
      drug: "Olaparib",
    });
  });

  it("returns empty array when no gene specified", async () => {
    server.use(
      http.get("/api/genomics/interactions", () =>
        HttpResponse.json({ success: true, data: [] }),
      ),
    );

    const { result } = renderHookWithProviders(() =>
      useGeneDrugInteractions(),
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(Array.isArray(result.current.data)).toBe(true);
    expect(result.current.data).toHaveLength(0);
  });
});

// ---------------------------------------------------------------------------
// useGenomicVariants
// ---------------------------------------------------------------------------

describe("useGenomicVariants", () => {
  it("fetches variants when person_id is provided", async () => {
    server.use(
      http.get("/api/genomics/variants", () =>
        HttpResponse.json({
          data: [{ id: 1, gene_symbol: "BRCA1", chromosome: "17" }],
          current_page: 1,
          last_page: 1,
          per_page: 25,
          total: 1,
        }),
      ),
    );

    const { result } = renderHookWithProviders(() =>
      useGenomicVariants({ person_id: 100 }),
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data).toBeDefined();
    expect(result.current.data).toHaveProperty("data");
  });

  it("does not fire query when no params provided", () => {
    const { result } = renderHookWithProviders(() =>
      useGenomicVariants({}),
    );

    // enabled guard: !!(params?.upload_id || params?.person_id || params?.gene)
    // All are undefined so query should never fire
    expect(result.current.fetchStatus).toBe("idle");
    expect(result.current.isSuccess).toBe(false);
  });
});

// ---------------------------------------------------------------------------
// useRadiogenomicsPanel
// ---------------------------------------------------------------------------

describe("useRadiogenomicsPanel", () => {
  it("fetches panel data for valid patient", async () => {
    server.use(
      http.get("/api/radiogenomics/patients/:id", () =>
        HttpResponse.json({
          success: true,
          data: {
            patient: { person_id: 100 },
            variants: { all: 5 },
            drug_exposures: [],
            correlations: [],
            recommendations: [],
          },
        }),
      ),
    );

    const { result } = renderHookWithProviders(() =>
      useRadiogenomicsPanel(100),
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data).toBeDefined();
    expect(result.current.data).toHaveProperty("patient");
  });
});

// ---------------------------------------------------------------------------
// useGenomicBriefing
// ---------------------------------------------------------------------------

describe("useGenomicBriefing", () => {
  it("mutation sends briefing request and returns response", async () => {
    server.use(
      http.post("http://localhost:8100/api/decision-support/genomic-briefing", () =>
        HttpResponse.json({
          briefing: "Test genomic briefing for patient",
          generated_at: "2026-03-25T12:00:00Z",
          variant_count: 3,
          actionable_count: 1,
        }),
      ),
    );

    const { result } = renderHookWithProviders(() => useGenomicBriefing());

    await act(async () => {
      result.current.mutate({
        patient_id: 100,
        variants: [
          {
            gene: "BRCA1",
            variant: "c.5266dupC",
            classification: "Pathogenic",
            evidence_level: "1A",
            therapies: ["Olaparib"],
          },
        ],
        drug_exposures: [],
        interactions: [],
        total_variant_count: 1,
      });
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data).toMatchObject({
      briefing: "Test genomic briefing for patient",
      variant_count: 3,
      actionable_count: 1,
    });
  });
});
