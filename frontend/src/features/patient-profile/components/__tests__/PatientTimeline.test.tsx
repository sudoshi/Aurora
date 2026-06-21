import { describe, it, expect, beforeAll, afterEach } from "vitest";
import { screen, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithProviders, resetStores } from "@/test/utils";
import { PatientTimeline } from "../PatientTimeline";
import type { ClinicalEvent, ObservationPeriod } from "../../types/profile";

// jsdom has no ResizeObserver; the timeline observes its container width.
beforeAll(() => {
  if (!("ResizeObserver" in globalThis)) {
    class ResizeObserverStub {
      observe(): void {}
      unobserve(): void {}
      disconnect(): void {}
    }
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (globalThis as any).ResizeObserver = ResizeObserverStub;
  }
});

// Representative cross-domain dataset: a single-day event, a duration event,
// a measurement with a numeric value, and events across multiple domains so
// every swim lane / legend / filter chip path is exercised.
const mockEvents: ClinicalEvent[] = [
  {
    id: 1,
    domain: "condition",
    concept_name: "Type 2 diabetes mellitus",
    concept_code: "E11",
    start_date: "2020-03-01",
    end_date: "2024-01-01",
  },
  {
    id: 2,
    domain: "medication",
    concept_name: "Metformin",
    concept_code: "RxN-6809",
    start_date: "2020-03-05",
    end_date: "2023-12-01",
  },
  {
    id: 3,
    domain: "procedure",
    concept_name: "Colonoscopy",
    concept_code: "45378",
    start_date: "2022-06-15",
    end_date: null,
  },
  {
    id: 4,
    domain: "measurement",
    concept_name: "Hemoglobin A1c",
    concept_code: "4548-4",
    start_date: "2023-01-10",
    end_date: null,
    value_numeric: 7.2,
    unit: "%",
  },
  {
    id: 5,
    domain: "observation",
    concept_name: "Tobacco smoking status",
    concept_code: "72166-2",
    start_date: "2021-09-01",
    end_date: null,
  },
  {
    id: 6,
    domain: "visit",
    concept_name: "Outpatient visit",
    concept_code: "AMB",
    start_date: "2023-01-10",
    end_date: "2023-01-10",
  },
];

const mockObservationPeriods: ObservationPeriod[] = [
  { id: 1, start_date: "2019-01-01", end_date: "2024-06-01", period_type: "EHR" },
];

describe("PatientTimeline", () => {
  afterEach(() => {
    resetStores();
  });

  it("renders the empty state when there are no events", () => {
    renderWithProviders(<PatientTimeline events={[]} />);
    expect(
      screen.getByText("No clinical events to display"),
    ).toBeInTheDocument();
  });

  it("renders the toolbar summary with event and domain counts", () => {
    renderWithProviders(
      <PatientTimeline
        events={mockEvents}
        observationPeriods={mockObservationPeriods}
      />,
    );
    // Summary is a single leaf <span> reading "6 events · 6 domains"
    // (the middle dot is the &middot; HTML entity).
    expect(screen.getByText("6 events · 6 domains")).toBeInTheDocument();
  });

  it("renders a domain filter chip per present domain with counts", () => {
    renderWithProviders(<PatientTimeline events={mockEvents} />);
    expect(screen.getByText("Domains:")).toBeInTheDocument();
    // Each present domain becomes a toggle button labelled "<Label> (count)".
    expect(
      screen.getByRole("button", { name: "Conditions (1)" }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "Medications (1)" }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "Procedures (1)" }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "Measurements (1)" }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "Observations (1)" }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "Visits (1)" }),
    ).toBeInTheDocument();
  });

  it("renders the swim-lane labels for each active domain in the SVG", () => {
    const { container } = renderWithProviders(
      <PatientTimeline events={mockEvents} />,
    );
    // The timeline chart is the one large SVG (icons are 24x24 viewBox SVGs).
    const chartSvg = Array.from(container.querySelectorAll("svg")).find(
      (el) => el.getAttribute("viewBox")?.startsWith("0 0 9") || (el.querySelectorAll("text").length > 3),
    );
    expect(chartSvg).toBeTruthy();
    const laneLabels = Array.from(chartSvg!.querySelectorAll("text")).map(
      (t) => t.textContent,
    );
    // Lane labels live inside <text> nodes within the chart SVG.
    for (const label of [
      "Conditions",
      "Medications",
      "Procedures",
      "Measurements",
      "Observations",
      "Visits",
    ]) {
      expect(laneLabels).toContain(label);
    }
  });

  it("renders the search highlight input and zoom controls", () => {
    renderWithProviders(<PatientTimeline events={mockEvents} />);
    expect(
      screen.getByPlaceholderText("Highlight events..."),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "Reset" }),
    ).toBeInTheDocument();
    // Zoom percentage indicator renders as "<n>%".
    expect(screen.getByText(/^\d+%$/)).toBeInTheDocument();
  });

  it("renders the legend footer hint", () => {
    renderWithProviders(<PatientTimeline events={mockEvents} />);
    expect(
      screen.getByText(/Ctrl\+scroll to zoom/),
    ).toBeInTheDocument();
  });

  it("hides a domain's lane when its filter chip is toggled off", async () => {
    const user = userEvent.setup();
    renderWithProviders(<PatientTimeline events={mockEvents} />);

    expect(screen.getByText("6 events · 6 domains")).toBeInTheDocument();

    const conditionsChip = screen.getByRole("button", {
      name: "Conditions (1)",
    });
    await user.click(conditionsChip);

    // Toolbar domain count drops once conditions are hidden (events count is
    // unchanged — it reflects the full dataset, domains reflects active lanes).
    expect(screen.getByText("6 events · 5 domains")).toBeInTheDocument();

    // The chip label itself is unchanged (count is derived from all events).
    expect(
      screen.getByRole("button", { name: "Conditions (1)" }),
    ).toBeInTheDocument();
  });

  it("clears the search query via the clear button", async () => {
    const user = userEvent.setup();
    renderWithProviders(<PatientTimeline events={mockEvents} />);
    const input = screen.getByPlaceholderText<HTMLInputElement>(
      "Highlight events...",
    );
    await user.type(input, "Metformin");
    expect(input.value).toBe("Metformin");

    // Clear button (X) appears only when there is a query.
    const toolbar = input.closest("div")!;
    const clearButton = within(toolbar).getByRole("button");
    await user.click(clearButton);
    expect(input.value).toBe("");
  });
});
