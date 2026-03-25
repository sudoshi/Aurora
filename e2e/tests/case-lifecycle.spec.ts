import { test, expect } from "@playwright/test";

test.describe.serial("Case lifecycle", () => {
  /** Unique title used by the create test and referenced by the detail test. */
  const caseTitle = `E2E Test Case ${Date.now()}`;

  test("case list page loads", async ({ page }) => {
    await page.goto("/cases");

    // Heading
    await expect(
      page.getByRole("heading", { name: /cases/i })
    ).toBeVisible();

    // New Case button
    await expect(
      page.getByRole("button", { name: /new case/i })
    ).toBeVisible();
  });

  test("can create a new case", async ({ page }) => {
    await page.goto("/cases");

    // Open the create-case modal
    await page.getByRole("button", { name: /new case/i }).click();

    // Fill the title field (label "Title", id "case-title")
    await page.getByLabel(/title/i).fill(caseTitle);

    // Leave specialty (oncology), case type (tumor_board), urgency (routine) at defaults

    // Submit the form
    await page.getByRole("button", { name: /create case/i }).click();

    // Wait for modal to close -- the CaseForm overlay disappears on success
    await expect(page.getByRole("button", { name: /create case/i })).toBeHidden({
      timeout: 10_000,
    });

    // Assert the newly created case title appears in the case list
    await expect(page.getByText(caseTitle)).toBeVisible({ timeout: 10_000 });
  });

  test("can view case detail and team tab", async ({ page }) => {
    await page.goto("/cases");

    // Wait for case list to load
    await expect(
      page.getByRole("heading", { name: /cases/i })
    ).toBeVisible();

    // Click on the case we just created (rendered as a CaseCard with onClick navigate)
    const caseLink = page.getByText(caseTitle);
    await expect(caseLink).toBeVisible({ timeout: 10_000 });
    await caseLink.click();

    // Assert we are on the case detail page -- the case title appears as the h1 heading
    await expect(
      page.getByRole("heading", { name: new RegExp(caseTitle.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), "i") })
    ).toBeVisible({ timeout: 10_000 });

    // Click the "Team" tab (role="tab" with aria-selected)
    const teamTab = page.getByRole("tab", { name: /team/i });
    await expect(teamTab).toBeVisible();
    await teamTab.click();

    // Assert the Team panel renders -- "Add Member" button is visible
    await expect(
      page.getByRole("button", { name: /add member/i })
    ).toBeVisible({ timeout: 10_000 });
  });
});
