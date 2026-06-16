import { describe, it, expect, afterEach } from "vitest";
import { screen, waitFor, fireEvent } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { AbbyDecisionDraft } from "../AbbyDecisionDraft";

const MOCK_DRAFT = {
  decision_type: "treatment_recommendation",
  recommendation: "Proceed with BRAF inhibitor therapy",
  rationale: "Patient has BRAF V600E mutation",
  confidence: 0.82,
  guideline_references: ["NCCN"],
  sources: [
    {
      type: "article",
      id: "PMID:100",
      title: "BRAF in melanoma",
      url: "https://pubmed.ncbi.nlm.nih.gov/100/",
    },
  ],
  model: "claude-x",
  evidence_counts: { articles: 1, trials: 0, variants: 0 },
};

afterEach(() => resetStores());

describe("AbbyDecisionDraft", () => {
  it("drafts, renders recommendation + rationale, confidence, source link, and disclaimer", async () => {
    server.use(
      http.post("/api/cases/3/decisions/draft", () =>
        HttpResponse.json({ success: true, data: MOCK_DRAFT }),
      ),
    );

    renderWithProviders(<AbbyDecisionDraft caseId={3} />);

    // "Draft with Abby" button is present initially
    const draftBtn = screen.getByRole("button", { name: /draft with abby/i });
    expect(draftBtn).toBeInTheDocument();

    // Click to trigger draft
    fireEvent.click(draftBtn);

    // Wait for draft content to appear
    await waitFor(() =>
      expect(screen.getByDisplayValue(/Proceed with BRAF inhibitor therapy/i)).toBeInTheDocument(),
    );

    // Rationale rendered
    expect(screen.getByDisplayValue(/BRAF V600E mutation/i)).toBeInTheDocument();

    // Confidence shown as percentage
    expect(screen.getByText("82%")).toBeInTheDocument();

    // Source rendered as external link
    const sourceLink = screen.getByRole("link", { name: /BRAF in melanoma/i });
    expect(sourceLink).toBeInTheDocument();
    expect(sourceLink).toHaveAttribute("href", "https://pubmed.ncbi.nlm.nih.gov/100/");
    expect(sourceLink).toHaveAttribute("target", "_blank");

    // Disclaimer present
    expect(screen.getByText(/review before recording/i)).toBeInTheDocument();
  });

  it("posts with ai_generated:true, ai_model, and ai_sources when 'Confirm & record' is clicked", async () => {
    const capturedBodies: unknown[] = [];

    server.use(
      http.post("/api/cases/3/decisions/draft", () =>
        HttpResponse.json({ success: true, data: MOCK_DRAFT }),
      ),
      http.post("/api/cases/3/decisions", async ({ request }) => {
        capturedBodies.push(await request.json());
        return HttpResponse.json({ success: true, data: { id: 1 } }, { status: 201 });
      }),
    );

    renderWithProviders(<AbbyDecisionDraft caseId={3} />);

    // Trigger draft
    fireEvent.click(screen.getByRole("button", { name: /draft with abby/i }));
    await waitFor(() =>
      expect(screen.getByDisplayValue(/Proceed with BRAF inhibitor therapy/i)).toBeInTheDocument(),
    );

    // Edit recommendation
    const recTextarea = screen.getByDisplayValue(/Proceed with BRAF inhibitor therapy/i);
    fireEvent.change(recTextarea, { target: { value: "Start vemurafenib 960 mg PO BID" } });

    // Click confirm
    fireEvent.click(screen.getByRole("button", { name: /confirm & record/i }));

    await waitFor(() => expect(capturedBodies.length).toBe(1));

    const body = capturedBodies[0] as Record<string, unknown>;
    expect(body.ai_generated).toBe(true);
    expect(body.ai_model).toBe("claude-x");
    expect(Array.isArray(body.ai_sources)).toBe(true);
    expect((body.ai_sources as unknown[]).length).toBe(1);
    expect(body.recommendation).toBe("Start vemurafenib 960 mg PO BID");
  });
});
