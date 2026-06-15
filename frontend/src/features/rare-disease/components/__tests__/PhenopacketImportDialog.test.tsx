import { describe, it, expect, afterEach, vi } from "vitest";
import { screen, waitFor, fireEvent } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { PhenopacketImportDialog } from "../PhenopacketImportDialog";

afterEach(() => resetStores());

describe("PhenopacketImportDialog", () => {
  it("rejects invalid JSON / schema before sending", async () => {
    renderWithProviders(<PhenopacketImportDialog odysseyId={7} open onClose={vi.fn()} />);
    fireEvent.change(screen.getByLabelText(/phenopacket json/i), { target: { value: "{ not json" } });
    fireEvent.click(screen.getByRole("button", { name: /^import$/i }));
    expect(await screen.findByText(/invalid json|valid hpo|phenotypicfeatures/i)).toBeInTheDocument();
  });

  it("imports a valid phenopacket and reports the result", async () => {
    server.use(
      http.post("/api/odysseys/7/import-phenopacket", () =>
        HttpResponse.json({ success: true, data: { imported: 1, skipped: 0 } }),
      ),
    );
    const onClose = vi.fn();
    renderWithProviders(<PhenopacketImportDialog odysseyId={7} open onClose={onClose} />);

    const valid = JSON.stringify({ phenotypicFeatures: [{ type: { id: "HP:0001250", label: "Seizure" } }] });
    fireEvent.change(screen.getByLabelText(/phenopacket json/i), { target: { value: valid } });
    fireEvent.click(screen.getByRole("button", { name: /^import$/i }));

    await waitFor(() => expect(screen.getByText(/imported 1/i)).toBeInTheDocument());
  });
});
