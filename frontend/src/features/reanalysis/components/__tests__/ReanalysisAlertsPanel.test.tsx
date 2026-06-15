import { describe, it, expect, afterEach } from "vitest";
import { screen, waitFor, fireEvent } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { ReanalysisAlertsPanel } from "../ReanalysisAlertsPanel";

afterEach(() => resetStores());

describe("ReanalysisAlertsPanel", () => {
  it("lists alerts with the transition and lets a clinician dismiss one", async () => {
    server.use(
      http.get("/api/patients/5/kb-alerts", () =>
        HttpResponse.json({ success: true, data: [
          { id: 9, patient_id: 5, genomic_variant_id: 3, source: "clinvar", clinvar_variation_id: "55555",
            from_bucket: "vus", to_bucket: "pathogenic", from_stars: 1, to_stars: 3, severity: "high",
            evidence: { gene: "BRCA1", variation_url: "https://x/" }, status: "new", task_id: 1,
            acknowledged_by: null, acknowledged_at: null, resolution_note: null, created_at: "2026-06-15T00:00:00Z" },
        ] }),
      ),
      http.post("/api/kb-alerts/9/acknowledge", () =>
        HttpResponse.json({ success: true, data: { id: 9, status: "dismissed" } }),
      ),
    );

    renderWithProviders(<ReanalysisAlertsPanel patientId={5} />);

    await waitFor(() => expect(screen.getByText(/VUS/)).toBeInTheDocument());
    expect(screen.getByText(/Pathogenic/)).toBeInTheDocument();
    expect(screen.getByText(/BRCA1/)).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: /dismiss/i }));
    await waitFor(() => expect(screen.queryByRole("button", { name: /dismiss/i })).toBeNull());
  });

  it("shows an empty state when there are no alerts", async () => {
    server.use(http.get("/api/patients/5/kb-alerts", () => HttpResponse.json({ success: true, data: [] })));
    renderWithProviders(<ReanalysisAlertsPanel patientId={5} />);
    await waitFor(() => expect(screen.getByText(/no reanalysis alerts/i)).toBeInTheDocument());
  });
});
