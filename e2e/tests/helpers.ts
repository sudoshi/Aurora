import { type Page, expect } from "@playwright/test";

/**
 * Log in as the admin superuser. Navigates to login, fills credentials,
 * and waits for the dashboard to load.
 */
export async function loginAsAdmin(page: Page): Promise<void> {
  await page.goto("/");

  const hasPersistedAuth = await page.evaluate(() => {
    const persisted = window.localStorage.getItem("aurora-auth");
    if (!persisted) return false;

    try {
      const parsed = JSON.parse(persisted) as {
        state?: { token?: string | null; isAuthenticated?: boolean };
      };

      return Boolean(parsed.state?.isAuthenticated && parsed.state.token);
    } catch {
      return false;
    }
  });

  if (hasPersistedAuth) {
    await expect(page).not.toHaveURL(/\/login/);
    await expect(
      page.getByRole("navigation", { name: /main navigation/i }),
    ).toBeVisible({ timeout: 15_000 });
    return;
  }

  await page.goto("/login");
  await page.getByLabel(/email/i).fill("admin@acumenus.net");
  await page.getByLabel(/password/i).fill("superuser");
  await page.getByRole("button", { name: /^sign in$/i }).click();
  // Wait for navigation away from login page
  await expect(page).not.toHaveURL(/\/login/);
}

/**
 * Navigate to a sidebar item by its visible text.
 */
export async function navigateTo(page: Page, label: string): Promise<void> {
  const itemName = new RegExp(label, "i");
  const directLink = page.getByRole("link", { name: itemName });

  if (await directLink.first().isVisible({ timeout: 1_000 }).catch(() => false)) {
    await directLink.first().click();
    return;
  }

  for (const group of ["Clinical", "Intelligence", "Admin"]) {
    const groupButton = page.getByRole("button", { name: new RegExp(group, "i") });
    if (! await groupButton.first().isVisible({ timeout: 1_000 }).catch(() => false)) {
      continue;
    }

    await groupButton.first().click();

    const menuItem = page.getByRole("menuitem", { name: itemName });
    if (await menuItem.first().isVisible({ timeout: 1_000 }).catch(() => false)) {
      await menuItem.first().click();
      return;
    }
  }

  throw new Error(`Could not find navigation item "${label}"`);
}
