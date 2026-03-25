import { describe, it, expect, afterEach, beforeAll, afterAll } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import { http, HttpResponse, delay } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { GenomicBriefing } from "../GenomicBriefing";
import type { GenomicBriefingRequest } from "../../types";

beforeAll(() => server.listen({ onUnhandledRequest: "warn" }));
afterAll(() => server.close());

const briefingDataWithVariants: GenomicBriefingRequest = {
  patient_id: 100,
  variants: [
    {
      gene: "BRCA1",
      variant: "p.Gln1756fs",
      classification: "Pathogenic",
      evidence_level: "1A",
      therapies: ["Olaparib"],
    },
  ],
  drug_exposures: [],
  interactions: [],
  total_variant_count: 1,
};

const briefingDataEmpty: GenomicBriefingRequest = {
  patient_id: 100,
  variants: [],
  drug_exposures: [],
  interactions: [],
  total_variant_count: 0,
};

describe("GenomicBriefing", () => {
  afterEach(() => {
    resetStores();
    server.resetHandlers();
  });

  it("shows empty state when no variants", () => {
    renderWithProviders(
      <GenomicBriefing briefingData={briefingDataEmpty} />,
    );
    expect(
      screen.getByText("No variants available for briefing."),
    ).toBeInTheDocument();
  });

  it("shows loading state while fetching briefing", async () => {
    server.use(
      http.post("http://localhost:8100/api/decision-support/genomic-briefing", async () => {
        await delay(2000);
        return HttpResponse.json({
          briefing: "delayed",
          generated_at: "2026-03-25T12:00:00Z",
          variant_count: 1,
          actionable_count: 1,
        });
      }),
    );

    renderWithProviders(
      <GenomicBriefing briefingData={briefingDataWithVariants} />,
    );

    await waitFor(() => {
      expect(
        screen.getByText("Generating genomic briefing..."),
      ).toBeInTheDocument();
    });
  });

  it("renders briefing text on success", async () => {
    server.use(
      http.post("http://localhost:8100/api/decision-support/genomic-briefing", () => {
        return HttpResponse.json({
          briefing: "Patient has BRCA1 frameshift mutation with therapeutic implications",
          generated_at: "2026-03-25T12:00:00Z",
          variant_count: 3,
          actionable_count: 1,
        });
      }),
    );

    renderWithProviders(
      <GenomicBriefing briefingData={briefingDataWithVariants} />,
    );

    await waitFor(() => {
      expect(
        screen.getByText(/Patient has BRCA1 frameshift mutation/),
      ).toBeInTheDocument();
    });
  });

  it("renders error state with retry button", async () => {
    server.use(
      http.post("http://localhost:8100/api/decision-support/genomic-briefing", () => {
        return HttpResponse.json({
          briefing: "",
          generated_at: "",
          variant_count: 0,
          actionable_count: 0,
          error: "Service unavailable",
        });
      }),
    );

    renderWithProviders(
      <GenomicBriefing briefingData={briefingDataWithVariants} />,
    );

    await waitFor(() => {
      expect(screen.getByText("Service unavailable")).toBeInTheDocument();
    });
    expect(screen.getByText("Retry")).toBeInTheDocument();
  });
});
