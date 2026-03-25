import { describe, it, expect, afterEach } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithProviders, resetStores } from "@/test/utils";
import { EvidenceBadge } from "../EvidenceBadge";

describe("EvidenceBadge", () => {
  afterEach(() => {
    resetStores();
  });

  it("renders Level text for known level", () => {
    renderWithProviders(<EvidenceBadge evidenceLevel="1A" />);
    expect(screen.getByText("Level 1A")).toBeInTheDocument();
  });

  it("renders source when provided", () => {
    renderWithProviders(<EvidenceBadge evidenceLevel="2A" source="oncokb" />);
    expect(screen.getByText("oncokb")).toBeInTheDocument();
  });

  it("shows stale warning when lastVerifiedAt is >30 days ago", () => {
    const sixtyDaysAgo = new Date(Date.now() - 60 * 86400000).toISOString();
    renderWithProviders(
      <EvidenceBadge evidenceLevel="1A" lastVerifiedAt={sixtyDaysAgo} />,
    );
    expect(
      screen.getByTitle("Evidence not verified in >30 days"),
    ).toBeInTheDocument();
  });

  it("does not show stale warning when recently verified", () => {
    const today = new Date().toISOString();
    renderWithProviders(
      <EvidenceBadge evidenceLevel="1A" lastVerifiedAt={today} />,
    );
    expect(
      screen.queryByTitle("Evidence not verified in >30 days"),
    ).not.toBeInTheDocument();
  });

  it("shows stale warning when lastVerifiedAt is null", () => {
    renderWithProviders(
      <EvidenceBadge evidenceLevel="1A" lastVerifiedAt={null} />,
    );
    expect(
      screen.getByTitle("Evidence not verified in >30 days"),
    ).toBeInTheDocument();
  });
});
