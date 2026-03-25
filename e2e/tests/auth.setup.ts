import { test as setup, expect } from "@playwright/test";
import path from "path";

const authFile = path.join(__dirname, "..", ".auth", "admin.json");

setup("authenticate as admin", async ({ page }) => {
  await page.goto("/login");
  await page.getByLabel(/email/i).fill("admin@acumenus.net");
  await page.getByLabel(/password/i).fill("superuser");
  await page.getByRole("button", { name: /sign in/i }).click();

  // Wait for successful login
  await expect(
    page.getByRole("heading", { name: /dashboard/i })
  ).toBeVisible({ timeout: 15_000 });

  // Save signed-in state
  await page.context().storageState({ path: authFile });
});
