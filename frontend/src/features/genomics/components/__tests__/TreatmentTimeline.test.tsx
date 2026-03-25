import { describe, it, expect, afterEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithProviders, resetStores } from "@/test/utils";
import { TreatmentTimeline } from "../TreatmentTimeline";
import type { DrugExposure, VariantDrugCorrelation } from "../../types";

const mockDrug1: DrugExposure = {
  drug_name: "Olaparib",
  drug_class: "PARP inhibitor",
  start_date: "2025-01-01",
  end_date: "2025-06-01",
  total_days: 151,
};

const mockDrug2: DrugExposure = {
  drug_name: "Erlotinib",
  drug_class: "EGFR TKI",
  start_date: "2025-03-01",
  end_date: "2025-09-01",
  total_days: 184,
};

const mockCorrelation: VariantDrugCorrelation = {
  variant_id: 1,
  gene_symbol: "BRCA1",
  variant: "p.Gln1756fs",
  clinical_significance: "Pathogenic",
  drug_name: "Olaparib",
  relationship: "sensitive",
  evidence_level: "1A",
  mechanism: "Synthetic lethality",
  source: "oncokb",
  last_verified_at: "2026-03-01T00:00:00Z",
  patient_exposed: true,
  exposure_start: "2025-01-01",
  exposure_end: "2025-06-01",
};

describe("TreatmentTimeline", () => {
  afterEach(() => {
    resetStores();
  });

  it("returns null when no drug exposures", () => {
    const { container } = renderWithProviders(
      <TreatmentTimeline drugExposures={[]} correlations={[]} />,
    );
    expect(container.firstChild).toBeNull();
  });

  it("renders treatment history header with drug count", () => {
    renderWithProviders(
      <TreatmentTimeline
        drugExposures={[mockDrug1, mockDrug2]}
        correlations={[mockCorrelation]}
      />,
    );
    expect(screen.getByText("Treatment History")).toBeInTheDocument();
    expect(
      screen.getByText("2 drugs, 1 with genomic interactions"),
    ).toBeInTheDocument();
  });

  it("expands on click to show drug names", async () => {
    const user = userEvent.setup();
    renderWithProviders(
      <TreatmentTimeline
        drugExposures={[mockDrug1]}
        correlations={[]}
      />,
    );

    // Drug name should not be visible before expanding
    expect(screen.queryByText("Olaparib")).not.toBeInTheDocument();

    // Click to expand
    await user.click(screen.getByRole("button"));
    expect(screen.getByText("Olaparib")).toBeInTheDocument();
  });
});
