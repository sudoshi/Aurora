import { describe, it, expect, afterEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { useAuthStore } from "@/stores/authStore";
import { createMockUser } from "@/test/factories";
import OidcCallbackPage from "../OidcCallbackPage";

describe("OidcCallbackPage", () => {
  afterEach(() => {
    resetStores();
    server.resetHandlers();
  });

  it("exchanges the code and stores the returned token on success", async () => {
    const mockUser = createMockUser({ email: "sso@acumenus.net" });
    server.use(
      http.post("/api/auth/oidc/exchange", () =>
        HttpResponse.json({ access_token: "sanctum-from-exchange", user: mockUser }),
      ),
    );

    renderWithProviders(<OidcCallbackPage />, { initialRoute: "/auth/callback?code=good-code" });

    await waitFor(() => {
      expect(useAuthStore.getState().isAuthenticated).toBe(true);
    });
    expect(useAuthStore.getState().token).toBe("sanctum-from-exchange");
  });

  it("prefers `token` over `access_token` (Acumenus standardization)", async () => {
    server.use(
      http.post("/api/auth/oidc/exchange", () =>
        HttpResponse.json({
          token: "preferred-token",
          access_token: "legacy-token",
          user: createMockUser(),
        }),
      ),
    );

    renderWithProviders(<OidcCallbackPage />, { initialRoute: "/auth/callback?code=good-code" });

    await waitFor(() => expect(useAuthStore.getState().token).toBe("preferred-token"));
  });

  it("shows a deterministic failure state when the code is missing", async () => {
    renderWithProviders(<OidcCallbackPage />, { initialRoute: "/auth/callback" });

    expect(await screen.findByText(/did not include a valid login code/i)).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /return to sign in/i })).toBeInTheDocument();
    expect(useAuthStore.getState().isAuthenticated).toBe(false);
  });

  it("shows an error when the exchange is rejected", async () => {
    server.use(
      http.post("/api/auth/oidc/exchange", () =>
        HttpResponse.json({ reason: "unknown_code" }, { status: 400 }),
      ),
    );

    renderWithProviders(<OidcCallbackPage />, { initialRoute: "/auth/callback?code=stale-code" });

    expect(await screen.findByText(/unknown_code/i)).toBeInTheDocument();
    expect(useAuthStore.getState().isAuthenticated).toBe(false);
  });
});
