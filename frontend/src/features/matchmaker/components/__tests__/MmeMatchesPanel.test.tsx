import { describe, it, expect, afterEach } from "vitest";
import { screen, waitFor, fireEvent } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { MmeMatchesPanel } from "../MmeMatchesPanel";

afterEach(() => resetStores());

describe("MmeMatchesPanel", () => {
  it("lists matches and triggers a matchmaker search", async () => {
    let posted = false;
    server.use(
      http.get("/api/odysseys/5/mme-matches", () =>
        HttpResponse.json({ success: true, data: [
          { id: 1, odyssey_id: 5, direction: "outbound", peer_id: 2, score: 0.9, matched_label: "Case 1", matched_contact_name: "Dr Y", matched_contact_href: "mailto:y@z", status: "new", created_at: "2026-06-16T00:00:00Z" },
        ] }),
      ),
      http.post("/api/odysseys/5/mme-search", () => { posted = true; return HttpResponse.json({ success: true, data: { stored: 1 } }); }),
    );

    renderWithProviders(<MmeMatchesPanel odysseyId={5} />);

    await waitFor(() => expect(screen.getByText(/Case 1/)).toBeInTheDocument());
    expect(screen.getByText(/Dr Y/)).toBeInTheDocument();
    expect(screen.getByText(/90%/)).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: /search matchmaker/i }));
    await waitFor(() => expect(posted).toBe(true));
  });

  it("shows an empty state when there are no matches", async () => {
    server.use(http.get("/api/odysseys/5/mme-matches", () => HttpResponse.json({ success: true, data: [] })));
    renderWithProviders(<MmeMatchesPanel odysseyId={5} />);
    await waitFor(() => expect(screen.getByText(/no matchmaker matches/i)).toBeInTheDocument());
  });
});
