import { describe, it, expect, afterEach, vi } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithProviders, resetStores } from "@/test/utils";
import { createMockVariant } from "@/test/factories";
import { ActionableVariantsPanel } from "../ActionableVariantsPanel";

vi.mock("@/features/patient-profile/components/InlineActionMenu", () => ({
  InlineActionMenu: () => null,
}));

describe("ActionableVariantsPanel", () => {
  afterEach(() => {
    resetStores();
  });

  const defaultProps = {
    interactions: [],
    correlations: [],
    drugExposures: [],
    patientId: 100,
  };

  it("returns null when no actionable or VUS variants", () => {
    const benignVariant = createMockVariant({
      id: 1,
      clinvar_significance: "Benign",
    });
    const { container } = renderWithProviders(
      <ActionableVariantsPanel
        {...defaultProps}
        variants={[benignVariant]}
      />,
    );
    expect(container.firstChild).toBeNull();
  });

  it("renders actionable variants section for pathogenic variants", () => {
    const pathogenicVariant = createMockVariant({
      id: 1,
      clinvar_significance: "Pathogenic",
    });
    renderWithProviders(
      <ActionableVariantsPanel
        {...defaultProps}
        variants={[pathogenicVariant]}
      />,
    );
    expect(screen.getByText("Actionable Variants")).toBeInTheDocument();
  });

  it("renders VUS accordion and toggles on click", async () => {
    const user = userEvent.setup();
    const vusVariant = createMockVariant({
      id: 2,
      clinvar_significance: "Uncertain significance",
      gene_symbol: "TP53",
    });
    renderWithProviders(
      <ActionableVariantsPanel
        {...defaultProps}
        variants={[vusVariant]}
      />,
    );
    expect(
      screen.getByText("Variants of Uncertain Significance"),
    ).toBeInTheDocument();

    // Gene symbol should not be visible before expanding
    expect(screen.queryByText("TP53")).not.toBeInTheDocument();

    // Click to expand
    await user.click(screen.getByRole("button"));
    expect(screen.getByText("TP53")).toBeInTheDocument();
  });

  it("shows correct count badges", () => {
    const variants = [
      createMockVariant({ id: 1, clinvar_significance: "Pathogenic" }),
      createMockVariant({ id: 2, clinvar_significance: "Pathogenic" }),
      createMockVariant({
        id: 3,
        clinvar_significance: "Uncertain significance",
      }),
    ];
    renderWithProviders(
      <ActionableVariantsPanel {...defaultProps} variants={variants} />,
    );
    expect(screen.getByText("(2)")).toBeInTheDocument();
    expect(screen.getByText("(1)")).toBeInTheDocument();
  });
});
