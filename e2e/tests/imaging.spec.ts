import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./helpers";

test.describe("Imaging", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto("/imaging");
    await expect(page.getByRole("heading", { name: /medical imaging/i })).toBeVisible();
  });

  test("loads the Imaging page", async ({ page }) => {
    await expect(page.getByRole("heading", { name: /medical imaging/i })).toBeVisible();
  });

  test("verify study browser loads", async ({ page }) => {
    await expect(page.getByRole("button", { name: /studies/i })).toBeVisible();
    await expect(page.getByRole("table").first()).toBeVisible({ timeout: 10_000 });
    await expect(page.getByText("indexed").first()).toBeVisible({ timeout: 10_000 });
  });

  test("verify stats bar renders", async ({ page }) => {
    await expect(page.getByText("Total Studies", { exact: true }).first()).toBeVisible({ timeout: 10_000 });
    await expect(page.getByText("AI Features", { exact: true }).first()).toBeVisible();
    await expect(page.getByText("Persons with Imaging", { exact: true })).toBeVisible();
  });
});
