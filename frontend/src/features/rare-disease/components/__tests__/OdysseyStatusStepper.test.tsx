import { describe, it, expect, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { OdysseyStatusStepper } from "../OdysseyStatusStepper";

describe("OdysseyStatusStepper", () => {
  it("marks the current status and renders allowed transitions as buttons", () => {
    const onTransition = vi.fn();
    render(
      <OdysseyStatusStepper
        current="phenotyping"
        allowed={["testing", "mdt_review"]}
        onTransition={onTransition}
        isPending={false}
      />,
    );

    expect(screen.getByText(/phenotyping/i).closest("[aria-current]")).toHaveAttribute("aria-current", "step");

    fireEvent.click(screen.getByRole("button", { name: /testing/i }));
    expect(onTransition).toHaveBeenCalledWith("testing");
  });

  it("disables transition buttons while a transition is pending", () => {
    render(
      <OdysseyStatusStepper current="referral" allowed={["phenotyping"]} onTransition={vi.fn()} isPending />,
    );
    expect(screen.getByRole("button", { name: /phenotyping/i })).toBeDisabled();
  });
});
