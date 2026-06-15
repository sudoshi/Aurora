import { describe, it, expect, afterEach } from "vitest";
import { waitFor, act } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderHookWithProviders, resetStores } from "@/test/utils";
import { useAcmgCatalog, useCreateClassification } from "../useClassification";

afterEach(() => resetStores());

describe("useAcmgCatalog", () => {
  it("fetches the ACMG catalog", async () => {
    server.use(
      http.get("/api/acmg/criteria", () =>
        HttpResponse.json({ success: true, data: { PVS1: { category: "pathogenic", default_strength: "very_strong", automatable: false, standalone: false, description: "x" } } }),
      ),
    );
    const { result } = renderHookWithProviders(() => useAcmgCatalog());
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data!.PVS1.category).toBe("pathogenic");
  });
});

describe("useCreateClassification", () => {
  it("creates a classification from supplied evidence", async () => {
    server.use(
      http.post("/api/genomic-variants/42/classifications", () =>
        HttpResponse.json({ success: true, data: { id: 7, genomic_variant_id: 42, computed_classification: "vus", computed_points: 5, status: "computed", criteria: [] } }, { status: 201 }),
      ),
    );
    const { result } = renderHookWithProviders(() => useCreateClassification(42));
    await act(async () => { result.current.mutate({ population_af: 0, revel: 0.95 }); });
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data!.computed_points).toBe(5);
  });
});
