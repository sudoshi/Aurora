import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./helpers";

test.describe("Authentication flow", () => {
  test("login page loads with expected elements", async ({ page }) => {
    await page.goto("/login");

    await expect(page.getByLabel(/email/i)).toBeVisible();
    await expect(page.getByLabel(/password/i)).toBeVisible();
    await expect(
      page.getByRole("button", { name: /sign in|log in|login/i })
    ).toBeVisible();
    // Create Account link must always be present (auth-system rule #1)
    await expect(
      page.getByRole("link", { name: /create account|register|sign up/i })
    ).toBeVisible();
  });

  test("admin can log in and see the dashboard", async ({ page }) => {
    await loginAsAdmin(page);

    // Dashboard should be visible
    await expect(page.locator("[data-testid='sidebar'], nav, aside")).toBeVisible();
    // URL should not be /login anymore
    await expect(page).not.toHaveURL(/\/login/);
  });

  test("sidebar navigation appears after login", async ({ page }) => {
    await loginAsAdmin(page);

    // At least one navigation link should be present in sidebar/nav
    const navLinks = page.locator("nav a, aside a, [data-testid='sidebar'] a");
    await expect(navLinks.first()).toBeVisible();
    expect(await navLinks.count()).toBeGreaterThan(0);
  });

  test("logout redirects to login page", async ({ page }) => {
    await loginAsAdmin(page);

    // Look for logout button or link
    const logoutTrigger = page
      .getByRole("button", { name: /logout|sign out|log out/i })
      .or(page.getByRole("link", { name: /logout|sign out|log out/i }))
      .or(page.getByText(/logout|sign out|log out/i));

    await logoutTrigger.first().click();

    // Should redirect to login
    await expect(page).toHaveURL(/\/login/);
  });

  test("register page loads without password field", async ({ page }) => {
    await page.goto("/register");

    await expect(page.getByLabel(/name/i)).toBeVisible();
    await expect(page.getByLabel(/email/i)).toBeVisible();
    // Auth system rule: register page must NOT have a password field
    await expect(page.getByLabel(/password/i)).not.toBeVisible();
  });

  test("invalid credentials show error", async ({ page }) => {
    await page.goto("/login");

    await page.getByLabel(/email/i).fill("wrong@example.com");
    await page.getByLabel(/password/i).fill("wrongpassword");
    await page.getByRole("button", { name: /sign in|log in|login/i }).click();

    // Should remain on login page and show an error
    await expect(page).toHaveURL(/\/login/);
    // Error message should appear somewhere on the page
    await expect(
      page.getByText(/invalid|incorrect|failed|error|unauthorized/i)
    ).toBeVisible({ timeout: 5000 });
  });
});
