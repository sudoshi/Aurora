import { type Page, expect } from "@playwright/test";

/**
 * Log in as the admin superuser. Navigates to login, fills credentials,
 * and waits for the dashboard to load.
 */
export async function loginAsAdmin(page: Page): Promise<void> {
  await page.goto("/login");
  await page.getByLabel(/email/i).fill("admin@acumenus.net");
  await page.getByLabel(/password/i).fill("superuser");
  await page.getByRole("button", { name: /sign in|log in|login/i }).click();
  // Wait for navigation away from login page
  await expect(page).not.toHaveURL(/\/login/);
}

/**
 * Navigate to a sidebar item by its visible text.
 */
export async function navigateTo(page: Page, label: string): Promise<void> {
  await page.getByRole("link", { name: new RegExp(label, "i") }).click();
}
