import { describe, it, expect, afterEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import KbAlertWorklistPage from "../KbAlertWorklistPage";

afterEach(() => resetStores());

describe("KbAlertWorklistPage", () => {
  it("renders a ClinVar alert row with gene, patient link, and source label", async () => {
    server.use(
      http.get("/api/kb-alerts", () =>
        HttpResponse.json({
          success: true,
          data: [
            {
              id: 1, patient_id: 7, genomic_variant_id: 3, source: "clinvar",
              clinvar_variation_id: "12345", from_bucket: "vus", to_bucket: "pathogenic",
              from_stars: 1, to_stars: 3, severity: "high",
              evidence: { gene: "TP53", variation_url: "https://clinvar.example/12345" },
              status: "new", task_id: null, acknowledged_by: null, acknowledged_at: null,
              resolution_note: null, created_at: "2026-06-15T00:00:00Z",
              variant: { id: 3, gene: "TP53", patient_id: 7 },
            },
          ],
          meta: { total: 1, current_page: 1, last_page: 1, per_page: 25 },
        }),
      ),
    );

    renderWithProviders(<KbAlertWorklistPage />);

    await waitFor(() => expect(screen.getByText("TP53")).toBeInTheDocument());
    expect(screen.getByRole("link", { name: "#7" })).toBeInTheDocument();
    expect(screen.getByText("ClinVar")).toBeInTheDocument();
  });
});
