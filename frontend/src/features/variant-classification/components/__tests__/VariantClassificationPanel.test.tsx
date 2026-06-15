import { describe, it, expect, afterEach } from "vitest";
import { screen, waitFor, fireEvent } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { VariantClassificationPanel } from "../VariantClassificationPanel";

afterEach(() => resetStores());

const catalog = { PVS1: { category: "pathogenic", default_strength: "very_strong", automatable: false, standalone: false, description: "x" } };

describe("VariantClassificationPanel", () => {
  it("creates a classification and renders the computed result with criteria", async () => {
    server.use(
      http.get("/api/acmg/criteria", () => HttpResponse.json({ success: true, data: catalog })),
      http.post("/api/genomic-variants/42/classifications", () =>
        HttpResponse.json({ success: true, data: {
          id: 7, genomic_variant_id: 42, computed_classification: "vus", computed_points: 1, status: "computed",
          criteria: [{ id: 1, classification_id: 7, code: "PM2", applied_strength: "supporting", points: 1, data_source: "auto:gnomad", evidence_value: "gnomAD AF=0", rationale: null, set_by: "auto", set_by_user_id: null }],
        } }, { status: 201 }),
      ),
    );

    renderWithProviders(<VariantClassificationPanel genomicVariantId={42} />);

    fireEvent.click(await screen.findByRole("button", { name: /classify variant/i }));

    await waitFor(() => expect(screen.getAllByText(/VUS/).length).toBeGreaterThan(0));
    expect(screen.getByText("PM2")).toBeInTheDocument();
    expect(screen.getByText(/gnomAD AF=0/)).toBeInTheDocument();
  });
});
