import { test, expect } from "@playwright/test";

test.describe("Login flow", () => {
  test("admin can log in and see the dashboard", async ({ page }) => {
    await page.goto("/login");
    await page.getByLabel(/email/i).fill("admin@acumenus.net");
    await page.getByLabel(/password/i).fill("superuser");
    await page.getByRole("button", { name: /sign in/i }).click();

    // Dashboard heading visible (auto-waits for navigation + render)
    await expect(
      page.getByRole("heading", { name: /dashboard/i })
    ).toBeVisible({ timeout: 15_000 });

    // Total Patients metric visible
    await expect(page.getByText(/total patients/i)).toBeVisible();
  });

  test("invalid credentials show error", async ({ page }) => {
    await page.goto("/login");
    await page.getByLabel(/email/i).fill("wrong@example.com");
    await page.getByLabel(/password/i).fill("wrongpassword");
    await page.getByRole("button", { name: /sign in/i }).click();

    // Error message should appear (backend: "do not match" or frontend fallback)
    await expect(
      page.getByText(/invalid|error|incorrect|do not match|credentials|failed/i)
    ).toBeVisible({ timeout: 10_000 });

    // Should remain on login page
    await expect(page).toHaveURL(/\/login/);
  });

  test("login page has create account link", async ({ page }) => {
    await page.goto("/login");

    await expect(
      page.getByRole("link", { name: /create account/i })
    ).toBeVisible();
  });
});
