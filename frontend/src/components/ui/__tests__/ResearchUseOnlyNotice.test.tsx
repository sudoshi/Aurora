import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";
import { ResearchUseOnlyNotice } from "../ResearchUseOnlyNotice";

describe("ResearchUseOnlyNotice", () => {
  it("renders the Research Use Only text", () => {
    render(<ResearchUseOnlyNotice />);
    expect(screen.getByText(/Research Use Only/i)).toBeInTheDocument();
  });

  it("exposes role=note with an accessible label", () => {
    render(<ResearchUseOnlyNotice />);
    const note = screen.getByRole("note");
    expect(note).toBeInTheDocument();
    expect(note).toHaveAttribute("aria-label", "Research Use Only disclaimer");
  });

  it("supports the chip variant", () => {
    render(<ResearchUseOnlyNotice variant="chip" />);
    expect(screen.getByRole("note")).toHaveClass("fixed");
  });
});
