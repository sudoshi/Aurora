import { test, expect } from "@playwright/test";

test.describe("Rare-disease diagnostic odyssey", () => {
  test("worklist loads and the New Odyssey dialog opens", async ({ page }) => {
    await page.goto("/rare-disease");

    // Worklist heading
    await expect(
      page.getByRole("heading", { name: /rare-disease odysseys/i })
    ).toBeVisible();

    // Open the create-odyssey dialog ("+ New Odyssey" button)
    await page.getByRole("button", { name: /new odyssey/i }).click();

    // Dialog renders with role="dialog" and the "New Diagnostic Odyssey" title
    await expect(page.getByRole("dialog")).toBeVisible();
    await expect(page.getByText(/new diagnostic odyssey/i)).toBeVisible();
  });
});
