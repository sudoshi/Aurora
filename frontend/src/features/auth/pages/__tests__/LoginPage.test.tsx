import { describe, it, expect, afterEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { useAuthStore } from "@/stores/authStore";
import { createMockUser } from "@/test/factories";
import LoginPage from "../LoginPage";

describe("LoginPage", () => {
  afterEach(() => {
    resetStores();
    server.resetHandlers();
  });

  it("renders login form with email and password fields", () => {
    renderWithProviders(<LoginPage />);

    expect(screen.getByLabelText("Email")).toBeInTheDocument();
    expect(screen.getByLabelText("Password")).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: /sign in/i }),
    ).toBeInTheDocument();
  });

  it("submits form and sets auth state on successful login", async () => {
    const user = userEvent.setup();
    const mockUser = createMockUser({ email: "admin@acumenus.net" });

    server.use(
      http.post("/api/auth/login", () => {
        return HttpResponse.json({
          access_token: "tok-123",
          user: mockUser,
        });
      }),
    );

    renderWithProviders(<LoginPage />);

    await user.type(screen.getByLabelText("Email"), "admin@acumenus.net");
    await user.type(screen.getByLabelText("Password"), "superuser");
    await user.click(screen.getByRole("button", { name: /sign in/i }));

    await waitFor(() => {
      expect(useAuthStore.getState().isAuthenticated).toBe(true);
    });
    expect(useAuthStore.getState().token).toBe("tok-123");
  });

  it("shows error message on invalid credentials", async () => {
    const user = userEvent.setup();

    server.use(
      http.post("/api/auth/login", () => {
        return HttpResponse.json(
          { message: "Invalid credentials" },
          { status: 401 },
        );
      }),
    );

    renderWithProviders(<LoginPage />);

    await user.type(screen.getByLabelText("Email"), "bad@example.com");
    await user.type(screen.getByLabelText("Password"), "wrong");
    await user.click(screen.getByRole("button", { name: /sign in/i }));

    await waitFor(() => {
      expect(screen.getByText("Invalid credentials")).toBeInTheDocument();
    });
  });

  it("has link to register page", () => {
    renderWithProviders(<LoginPage />);

    const link = screen.getByText(/create account/i);
    expect(link).toBeInTheDocument();
    expect(link.closest("a")).toHaveAttribute("href", "/register");
  });

  it("renders a Login with Authentik button", () => {
    renderWithProviders(<LoginPage />);

    expect(
      screen.getByRole("button", { name: /login with authentik/i }),
    ).toBeInTheDocument();
  });

  it("links to the Authentik OIDC endpoint", () => {
    server.use(
      http.get("/api/auth/providers", () => {
        return HttpResponse.json({
          oidc_enabled: true,
          oidc_label: "Authentik OpenID Connect",
          oidc_redirect_path: "/api/auth/oidc/redirect",
        });
      }),
    );

    renderWithProviders(<LoginPage />);

    expect(
      screen.getByRole("button", { name: /login with authentik/i }).closest("a"),
    ).toHaveAttribute("href", "/api/auth/oidc/redirect");
  });

  it("shows an error instead of navigating when Authentik is disabled", async () => {
    const user = userEvent.setup();

    renderWithProviders(<LoginPage />);

    const authentikButton = screen.getByRole("button", { name: /login with authentik/i });
    await waitFor(() => {
      expect(authentikButton).toHaveAttribute("aria-disabled", "true");
    });

    await user.click(authentikButton);

    expect(
      await screen.findByText("Authentik login is not enabled for this environment."),
    ).toBeInTheDocument();
  });
});
