import { test, expect } from "@playwright/test";
import { loginAsAdmin, navigateTo } from "./helpers";

test.describe("AI Copilot", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test("navigate to AI Copilot page", async ({ page }) => {
    await navigateTo(page, "Copilot");

    await expect(
      page
        .getByRole("heading", { name: /copilot|ai|abby/i })
        .or(page.getByText(/copilot|ai assistant/i).first())
    ).toBeVisible();
  });

  test("verify copilot tabs load", async ({ page }) => {
    await navigateTo(page, "Copilot");

    const expectedTabs = ["Trials", "Guidelines", "Drugs", "Genomics", "Prognosis"];

    for (const tabName of expectedTabs) {
      const tab = page
        .getByRole("tab", { name: new RegExp(tabName, "i") })
        .or(page.getByRole("button", { name: new RegExp(tabName, "i") }))
        .or(page.getByText(new RegExp(tabName, "i")));

      await expect(tab.first()).toBeVisible({ timeout: 5000 });
    }
  });

  test("switch between copilot tabs", async ({ page }) => {
    await navigateTo(page, "Copilot");

    const tabs = ["Trials", "Guidelines", "Drugs", "Genomics", "Prognosis"];

    for (const tabName of tabs) {
      const tab = page
        .getByRole("tab", { name: new RegExp(tabName, "i") })
        .or(page.getByRole("button", { name: new RegExp(tabName, "i") }));

      if (await tab.first().isVisible({ timeout: 3000 }).catch(() => false)) {
        await tab.first().click();
        // Brief wait for tab content to switch
        await page.waitForTimeout(500);
      }
    }
  });
});
