import { describe, it, expect, afterEach } from "vitest";
import { screen, waitFor, fireEvent } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { PhenotypeCapturePanel } from "../PhenotypeCapturePanel";

afterEach(() => resetStores());

describe("PhenotypeCapturePanel", () => {
  it("lists existing features with an Absent badge for excluded ones", async () => {
    server.use(
      http.get("/api/odysseys/7/phenotypes", () =>
        HttpResponse.json({
          success: true,
          data: [
            { id: 1, odyssey_id: 7, hpo_id: "HP:0001250", hpo_label: "Seizure", excluded: false, onset_hpo_id: null, severity_hpo_id: null, frequency_hpo_id: null, evidence: null, created_at: "2026-06-15T00:00:00Z" },
            { id: 2, odyssey_id: 7, hpo_id: "HP:0001251", hpo_label: "Ataxia", excluded: true, onset_hpo_id: null, severity_hpo_id: null, frequency_hpo_id: null, evidence: null, created_at: "2026-06-15T00:00:00Z" },
          ],
        }),
      ),
    );

    renderWithProviders(<PhenotypeCapturePanel odysseyId={7} />);

    await waitFor(() => expect(screen.getByText("Seizure")).toBeInTheDocument());
    expect(screen.getByText("Ataxia")).toBeInTheDocument();
    expect(screen.getByText(/absent/i)).toBeInTheDocument();
  });

  it("adds a phenotype after selecting an HPO term", async () => {
    server.use(
      http.get("/api/odysseys/7/phenotypes", () => HttpResponse.json({ success: true, data: [] })),
      http.get("/api/hpo/search", () =>
        HttpResponse.json({ success: true, data: [{ id: "HP:0001250", label: "Seizure", definition: null, synonyms: [] }] }),
      ),
      http.post("/api/odysseys/7/phenotypes", async ({ request }) => {
        const body = (await request.json()) as Record<string, unknown>;
        return HttpResponse.json(
          { success: true, data: { id: 9, odyssey_id: 7, excluded: false, onset_hpo_id: null, severity_hpo_id: null, frequency_hpo_id: null, evidence: null, created_at: "2026-06-15T00:00:00Z", ...body } },
          { status: 201 },
        );
      }),
    );

    renderWithProviders(<PhenotypeCapturePanel odysseyId={7} />);

    fireEvent.change(await screen.findByLabelText(/hpo term/i), { target: { value: "seizure" } });
    fireEvent.click(await screen.findByText(/Seizure/));
    fireEvent.click(screen.getByRole("button", { name: /add phenotype/i }));

    await waitFor(() => expect(screen.getByText("Seizure")).toBeInTheDocument());
  });
});
