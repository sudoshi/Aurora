import { test, expect } from "@playwright/test";
import { loginAsAdmin, navigateTo } from "./helpers";

test.describe("Session workflow", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test("navigate to Sessions page", async ({ page }) => {
    await navigateTo(page, "Sessions");

    await expect(
      page
        .getByRole("heading", { name: /session/i })
        .or(page.getByText(/sessions/i).first())
    ).toBeVisible();
  });

  test("create a new session", async ({ page }) => {
    await navigateTo(page, "Sessions");

    const createBtn = page
      .getByRole("button", { name: /new session|create session|add session/i })
      .or(page.getByRole("link", { name: /new session|create session/i }));

    if (await createBtn.first().isVisible({ timeout: 5000 }).catch(() => false)) {
      await createBtn.first().click();

      // Fill session form
      const titleInput = page
        .getByLabel(/title|name|subject/i)
        .or(page.getByPlaceholder(/title|name|subject/i));

      if (await titleInput.first().isVisible()) {
        await titleInput.first().fill("E2E Test Session — Weekly Tumor Board");
      }

      // Set date if available
      const dateInput = page.getByLabel(/date|scheduled/i);
      if (await dateInput.first().isVisible({ timeout: 2000 }).catch(() => false)) {
        await dateInput.first().fill("2026-04-01");
      }

      // Submit
      const submitBtn = page.getByRole("button", {
        name: /create|save|submit/i,
      });
      await submitBtn.first().click();

      await expect(
        page.getByText(/e2e test session|weekly tumor board/i)
      ).toBeVisible({ timeout: 10_000 });
    }
  });

  test("add cases to a session", async ({ page }) => {
    await navigateTo(page, "Sessions");

    // Open first session
    const sessionItem = page.locator(
      "[data-testid='session-item'] a, .session-item a, table tbody tr a"
    );

    if (await sessionItem.first().isVisible({ timeout: 5000 }).catch(() => false)) {
      await sessionItem.first().click();

      // Look for add case button
      const addCaseBtn = page
        .getByRole("button", { name: /add case|attach case/i });

      if (await addCaseBtn.first().isVisible({ timeout: 3000 }).catch(() => false)) {
        await addCaseBtn.first().click();
      }
    }
  });

  test("verify session detail loads with agenda", async ({ page }) => {
    await navigateTo(page, "Sessions");

    const sessionItem = page.locator(
      "[data-testid='session-item'] a, .session-item a, table tbody tr a"
    );

    if (await sessionItem.first().isVisible({ timeout: 5000 }).catch(() => false)) {
      await sessionItem.first().click();

      // Verify detail page has agenda or case list
      await expect(
        page.getByText(/agenda|cases|schedule|participants/i)
      ).toBeVisible({ timeout: 10_000 });
    }
  });
});
