import { describe, it, expect, vi, afterEach } from "vitest";
import { screen, waitFor, fireEvent } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { HpoAutocomplete } from "../HpoAutocomplete";

afterEach(() => resetStores());

describe("HpoAutocomplete", () => {
  it("shows results and fires onSelect with the chosen term", async () => {
    server.use(
      http.get("/api/hpo/search", () =>
        HttpResponse.json({ success: true, data: [{ id: "HP:0001250", label: "Seizure", definition: null, synonyms: [] }] }),
      ),
    );
    const onSelect = vi.fn();
    renderWithProviders(<HpoAutocomplete onSelect={onSelect} />);

    fireEvent.change(screen.getByLabelText(/hpo term/i), { target: { value: "seizure" } });

    const option = await screen.findByText(/Seizure/);
    fireEvent.click(option);

    await waitFor(() =>
      expect(onSelect).toHaveBeenCalledWith(
        expect.objectContaining({ id: "HP:0001250", label: "Seizure" }),
      ),
    );
  });

  it("does not query for inputs shorter than 2 chars", async () => {
    renderWithProviders(<HpoAutocomplete onSelect={vi.fn()} />);
    fireEvent.change(screen.getByLabelText(/hpo term/i), { target: { value: "s" } });
    await waitFor(() => expect(screen.queryByRole("listbox")).toBeNull());
  });
});
