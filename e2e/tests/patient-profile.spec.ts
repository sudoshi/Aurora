import { test, expect } from "@playwright/test";

test.describe("Patient profile navigation", () => {
  test("patient list page loads with table", async ({ page }) => {
    await page.goto("/profiles");

    // Heading visible
    await expect(
      page.getByRole("heading", { name: /patient profiles/i })
    ).toBeVisible();

    // At least one table row exists
    const rows = page.locator("table tbody tr");
    await expect(rows.first()).toBeVisible({ timeout: 10_000 });
    expect(await rows.count()).toBeGreaterThan(0);
  });

  test("navigate to patient profile and view tabs", async ({ page }) => {
    await page.goto("/profiles");

    // Wait for table to load
    await expect(page.locator("table tbody tr").first()).toBeVisible({
      timeout: 10_000,
    });

    // Click first patient row
    await page.locator("table tbody tr").first().click();

    // Patient detail page loads (heading "Patient Profile")
    await expect(
      page.getByRole("heading", { name: /patient profile/i })
    ).toBeVisible({ timeout: 10_000 });

    // View mode buttons are visible
    await expect(
      page.getByRole("button", { name: /timeline/i })
    ).toBeVisible();
    await expect(
      page.getByRole("button", { name: /labs/i })
    ).toBeVisible();
  });

  test("can switch between view modes", async ({ page }) => {
    await page.goto("/profiles");

    // Wait for table and click first patient
    await expect(page.locator("table tbody tr").first()).toBeVisible({
      timeout: 10_000,
    });
    await page.locator("table tbody tr").first().click();

    // Wait for profile to load
    await expect(
      page.getByRole("heading", { name: /patient profile/i })
    ).toBeVisible({ timeout: 10_000 });

    // Click Timeline button and assert content appears
    await page.getByRole("button", { name: /timeline/i }).click();
    await expect(
      page.locator("main").getByText(/timeline|visit|event|date/i).first()
    ).toBeVisible({ timeout: 10_000 });

    // Click Labs button and assert content appears
    await page.getByRole("button", { name: /labs/i }).click();
    await expect(
      page.locator("main").getByText(/labs|results|test|value/i).first()
    ).toBeVisible({ timeout: 10_000 });
  });
});
