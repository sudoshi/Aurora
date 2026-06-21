import { describe, it, expect, afterEach, vi } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithProviders, resetStores } from "@/test/utils";
import type { ImagingMeasurement, MeasurementSource } from "../types";
import MeasurementPanel from "./MeasurementPanel";

function mockMeasurement(
  id: number,
  source: MeasurementSource | null,
  name: string,
): ImagingMeasurement {
  return {
    id,
    study_id: 1,
    person_id: 10,
    series_id: null,
    measurement_type: "longest_diameter",
    measurement_name: name,
    value_as_number: 27.4,
    unit: "mm",
    body_site: "Liver",
    laterality: null,
    algorithm_name: source === "computed" ? "aurora-ai-volumetric" : null,
    confidence: null,
    source,
    created_by: null,
    measured_at: "2026-02-02T12:00:00Z",
    is_target_lesion: false,
    target_lesion_number: null,
    created_at: "2026-02-02T12:00:00Z",
    updated_at: "2026-02-02T12:00:00Z",
  };
}

const measurements: ImagingMeasurement[] = [
  mockMeasurement(1, "clinician", "Clinician lesion"),
  mockMeasurement(2, "computed", "AI lesion"),
];

vi.mock("../hooks/useImaging", () => ({
  useStudyMeasurements: () => ({ data: measurements, isLoading: false }),
  useCreateMeasurement: () => ({ mutate: vi.fn(), isPending: false }),
  useDeleteMeasurement: () => ({ mutate: vi.fn(), isPending: false }),
  useAiExtractMeasurements: () => ({
    mutate: vi.fn(),
    isPending: false,
    isSuccess: false,
    isError: false,
  }),
}));

describe("MeasurementPanel source provenance", () => {
  afterEach(() => {
    resetStores();
  });

  it("renders a Source column", () => {
    renderWithProviders(<MeasurementPanel studyId={1} personId={10} />);
    expect(screen.getByText("Source")).toBeInTheDocument();
  });

  it("labels clinician-entered and computed measurements with accessible text", () => {
    renderWithProviders(<MeasurementPanel studyId={1} personId={10} />);

    // Not color-only: each provenance is conveyed by an explicit text label.
    expect(screen.getByText("Clinician")).toBeInTheDocument();
    expect(screen.getByText("AI / computed")).toBeInTheDocument();
  });
});
