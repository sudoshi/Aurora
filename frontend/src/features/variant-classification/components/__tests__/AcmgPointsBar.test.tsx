import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";
import { AcmgPointsBar } from "../AcmgPointsBar";

describe("AcmgPointsBar", () => {
  it("shows the classification label, point total, and threshold ladder", () => {
    render(<AcmgPointsBar classification="likely_pathogenic" points={7} />);
    expect(screen.getByText(/likely pathogenic/i)).toBeInTheDocument();
    expect(screen.getByText(/\+7/)).toBeInTheDocument();
    expect(screen.getByText(/≥ ?10/)).toBeInTheDocument();
  });
});
