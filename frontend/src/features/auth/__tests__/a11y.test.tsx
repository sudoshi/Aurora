import { describe, it, expect, afterEach } from "vitest";
import { render } from "@testing-library/react";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { axe } from "@/test/a11y";
import LoginPage from "@/features/auth/pages/LoginPage";
import ChangePasswordModal from "@/features/auth/components/ChangePasswordModal";

describe("a11y: auth surfaces", () => {
  afterEach(() => {
    resetStores();
    server.resetHandlers();
  });

  it("LoginPage has no detectable a11y violations", async () => {
    const { container } = renderWithProviders(<LoginPage />);
    expect(await axe(container)).toHaveNoViolations();
  });

  it("ChangePasswordModal has no detectable a11y violations", async () => {
    // Renders standalone (no router/query deps), so plain render is sufficient.
    const { container } = render(<ChangePasswordModal />);
    expect(await axe(container)).toHaveNoViolations();
  });
});
