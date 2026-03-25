import { describe, it, expect, afterEach, beforeAll, afterAll, vi } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import { http, HttpResponse, delay } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { createMockVariant, createMockInteraction } from "@/test/factories";
import { GenomicVariantTable } from "../GenomicVariantTable";

vi.mock("../VariantExpandedRow", () => ({
  VariantExpandedRow: () => <div data-testid="expanded-row">Expanded</div>,
}));

beforeAll(() => server.listen({ onUnhandledRequest: "warn" }));
afterAll(() => server.close());

describe("GenomicVariantTable", () => {
  afterEach(() => {
    resetStores();
    server.resetHandlers();
  });

  const interactions = [createMockInteraction()];

  it("shows loading state", async () => {
    server.use(
      http.get("/api/genomics/variants", async () => {
        await delay(2000);
        return HttpResponse.json({
          data: [],
          current_page: 1,
          last_page: 1,
          per_page: 25,
          total: 0,
        });
      }),
    );

    renderWithProviders(
      <GenomicVariantTable patientId={100} interactions={interactions} />,
    );

    await waitFor(() => {
      expect(screen.getByText("Loading variants...")).toBeInTheDocument();
    });
  });

  it("renders variant rows from API", async () => {
    const variants = [
      createMockVariant({ id: 1, gene_symbol: "BRCA1", hgvs_p: "p.Gln1756fs" }),
      createMockVariant({ id: 2, gene_symbol: "EGFR", hgvs_p: "p.Leu858Arg" }),
    ];

    server.use(
      http.get("/api/genomics/variants", () => {
        return HttpResponse.json({
          data: variants,
          current_page: 1,
          last_page: 1,
          per_page: 25,
          total: 2,
        });
      }),
    );

    renderWithProviders(
      <GenomicVariantTable patientId={100} interactions={interactions} />,
    );

    await waitFor(() => {
      expect(screen.getByText("BRCA1")).toBeInTheDocument();
    });
    expect(screen.getByText("EGFR")).toBeInTheDocument();
  });

  it("shows empty state when no variants match", async () => {
    server.use(
      http.get("/api/genomics/variants", () => {
        return HttpResponse.json({
          data: [],
          current_page: 1,
          last_page: 1,
          per_page: 25,
          total: 0,
        });
      }),
    );

    renderWithProviders(
      <GenomicVariantTable patientId={100} interactions={interactions} />,
    );

    await waitFor(() => {
      expect(screen.getByText("No variants match filters")).toBeInTheDocument();
    });
  });

  it("renders pagination when multiple pages", async () => {
    const variants = Array.from({ length: 25 }, (_, i) =>
      createMockVariant({ id: i + 1, gene_symbol: `GENE${i}` }),
    );

    server.use(
      http.get("/api/genomics/variants", () => {
        return HttpResponse.json({
          data: variants,
          current_page: 1,
          last_page: 3,
          per_page: 25,
          total: 75,
        });
      }),
    );

    renderWithProviders(
      <GenomicVariantTable patientId={100} interactions={interactions} />,
    );

    await waitFor(() => {
      expect(screen.getByText("Page 1 of 3")).toBeInTheDocument();
    });
  });
});
