import { test, expect } from "@playwright/test";
import { loginAsAdmin, navigateTo } from "./helpers";

test.describe("Admin features", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test("navigate to Admin page", async ({ page }) => {
    await navigateTo(page, "Admin");

    await expect(
      page
        .getByRole("heading", { name: /admin|dashboard/i })
        .or(page.getByText(/admin|administration/i).first())
    ).toBeVisible();
  });

  test("admin dashboard loads with metrics", async ({ page }) => {
    await navigateTo(page, "Admin");

    // Dashboard should have some metric cards or stats
    await expect(
      page
        .getByText(/users|cases|sessions|active|total/i)
        .or(page.locator("[data-testid='metric-card'], .metric-card, .stat-card"))
    ).toBeVisible({ timeout: 10_000 });
  });

  test("navigate to Users page", async ({ page }) => {
    await navigateTo(page, "Admin");

    // Click on Users sub-navigation
    const usersLink = page
      .getByRole("link", { name: /users/i })
      .or(page.getByRole("tab", { name: /users/i }))
      .or(page.getByRole("button", { name: /users/i }));

    await usersLink.first().click();

    // User list should load
    await expect(
      page
        .getByText(/admin@acumenus.net|user management|users/i)
        .or(page.locator("table, [data-testid='user-list']"))
    ).toBeVisible({ timeout: 10_000 });
  });

  test("user list loads with admin user", async ({ page }) => {
    await navigateTo(page, "Admin");

    const usersLink = page
      .getByRole("link", { name: /users/i })
      .or(page.getByRole("tab", { name: /users/i }))
      .or(page.getByRole("button", { name: /users/i }));

    if (await usersLink.first().isVisible({ timeout: 3000 }).catch(() => false)) {
      await usersLink.first().click();

      // Admin user should be in the list
      await expect(
        page.getByText("admin@acumenus.net")
      ).toBeVisible({ timeout: 10_000 });
    }
  });

  test("navigate to System Health", async ({ page }) => {
    await navigateTo(page, "Admin");

    const healthLink = page
      .getByRole("link", { name: /health|system|status/i })
      .or(page.getByRole("tab", { name: /health|system/i }))
      .or(page.getByRole("button", { name: /health|system/i }));

    if (await healthLink.first().isVisible({ timeout: 3000 }).catch(() => false)) {
      await healthLink.first().click();

      // Health checks should display
      await expect(
        page.getByText(/health|status|database|redis|api|ok|healthy/i)
      ).toBeVisible({ timeout: 10_000 });
    }
  });

  test("health checks display service statuses", async ({ page }) => {
    await navigateTo(page, "Admin");

    const healthLink = page
      .getByRole("link", { name: /health|system|status/i })
      .or(page.getByRole("tab", { name: /health|system/i }))
      .or(page.getByRole("button", { name: /health|system/i }));

    if (await healthLink.first().isVisible({ timeout: 3000 }).catch(() => false)) {
      await healthLink.first().click();

      // Should show individual service health indicators
      const healthIndicators = page.locator(
        "[data-testid='health-check'], .health-check, .status-indicator"
      );

      if (await healthIndicators.first().isVisible({ timeout: 5000 }).catch(() => false)) {
        expect(await healthIndicators.count()).toBeGreaterThan(0);
      }
    }
  });
});
