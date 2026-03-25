import { describe, it, expect, afterEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import RegisterPage from "../RegisterPage";

describe("RegisterPage", () => {
  afterEach(() => {
    resetStores();
    server.resetHandlers();
  });

  it("renders registration form with name, email, and phone fields", () => {
    renderWithProviders(<RegisterPage />);

    expect(screen.getByLabelText("Full Name")).toBeInTheDocument();
    expect(screen.getByLabelText("Email")).toBeInTheDocument();
    expect(screen.getByLabelText(/phone/i)).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: /create account/i }),
    ).toBeInTheDocument();
  });

  it("submits form and shows success message", async () => {
    const user = userEvent.setup();

    server.use(
      http.post("/api/auth/register", () => {
        return HttpResponse.json({
          data: { message: "Check your email" },
        });
      }),
    );

    renderWithProviders(<RegisterPage />);

    await user.type(screen.getByLabelText("Full Name"), "Jane Doe");
    await user.type(screen.getByLabelText("Email"), "jane@example.com");
    await user.click(screen.getByRole("button", { name: /create account/i }));

    await waitFor(() => {
      expect(
        screen.getByText("Check your email for a temporary password"),
      ).toBeInTheDocument();
    });
  });

  it("shows error message on registration failure", async () => {
    const user = userEvent.setup();

    server.use(
      http.post("/api/auth/register", () => {
        return HttpResponse.json(
          { message: "Email already taken" },
          { status: 422 },
        );
      }),
    );

    renderWithProviders(<RegisterPage />);

    await user.type(screen.getByLabelText("Full Name"), "Jane Doe");
    await user.type(screen.getByLabelText("Email"), "existing@example.com");
    await user.click(screen.getByRole("button", { name: /create account/i }));

    await waitFor(() => {
      expect(screen.getByText("Email already taken")).toBeInTheDocument();
    });
  });

  it("has link back to login page", () => {
    renderWithProviders(<RegisterPage />);

    const link = screen.getByText(/back to login/i);
    expect(link).toBeInTheDocument();
    expect(link.closest("a")).toHaveAttribute("href", "/login");
  });
});
