import { test, expect } from "@playwright/test";
import { loginAsAdmin, navigateTo } from "./helpers";

test.describe("Imaging", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test("navigate to Imaging page", async ({ page }) => {
    await navigateTo(page, "Imaging");

    await expect(
      page
        .getByRole("heading", { name: /imaging/i })
        .or(page.getByText(/imaging|studies|dicom/i).first())
    ).toBeVisible();
  });

  test("verify study browser loads", async ({ page }) => {
    await navigateTo(page, "Imaging");

    // Study browser or study list should be visible
    await expect(
      page
        .getByText(/studies|study browser|study list|no studies/i)
        .or(page.locator("[data-testid='study-browser'], .study-browser, table"))
    ).toBeVisible({ timeout: 10_000 });
  });

  test("verify stats bar renders", async ({ page }) => {
    await navigateTo(page, "Imaging");

    // Stats bar with imaging metrics
    await expect(
      page
        .getByText(/total|studies|series|images|patients/i)
        .or(page.locator("[data-testid='stats-bar'], .stats-bar, .stats"))
    ).toBeVisible({ timeout: 10_000 });
  });
});
