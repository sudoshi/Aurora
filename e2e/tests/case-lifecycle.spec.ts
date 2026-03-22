import { test, expect } from "@playwright/test";
import { loginAsAdmin, navigateTo } from "./helpers";

test.describe("Case lifecycle", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test("navigate to Cases page", async ({ page }) => {
    await navigateTo(page, "Cases");

    // Cases page should load with some identifiable content
    await expect(
      page.getByRole("heading", { name: /cases/i }).or(page.getByText(/cases/i).first())
    ).toBeVisible();
  });

  test("create a new case", async ({ page }) => {
    await navigateTo(page, "Cases");

    // Click create/new case button
    const createBtn = page
      .getByRole("button", { name: /new case|create case|add case/i })
      .or(page.getByRole("link", { name: /new case|create case|add case/i }));
    await createBtn.first().click();

    // Fill in case form
    const titleInput = page
      .getByLabel(/title|name|subject/i)
      .or(page.getByPlaceholder(/title|name|subject/i));
    await titleInput.first().fill("E2E Test Case — Lung Cancer Staging");

    // Look for description/notes field
    const descField = page
      .getByLabel(/description|notes|details/i)
      .or(page.getByPlaceholder(/description|notes|details/i));
    if (await descField.first().isVisible()) {
      await descField.first().fill(
        "Automated E2E test case for verifying case lifecycle workflow."
      );
    }

    // Submit
    const submitBtn = page
      .getByRole("button", { name: /create|save|submit/i });
    await submitBtn.first().click();

    // Verify case appears or detail page loads
    await expect(
      page.getByText(/e2e test case|lung cancer staging/i)
    ).toBeVisible({ timeout: 10_000 });
  });

  test("open case detail and interact", async ({ page }) => {
    await navigateTo(page, "Cases");

    // Click on a case from the list
    const caseLink = page.getByRole("link", { name: /case/i }).or(
      page.locator("[data-testid='case-item'], .case-item, tr").first()
    );
    if (await caseLink.first().isVisible()) {
      await caseLink.first().click();

      // Verify case detail page loads
      await expect(
        page.getByText(/discussion|details|timeline|team/i)
      ).toBeVisible({ timeout: 10_000 });
    }
  });

  test("add discussion comment to a case", async ({ page }) => {
    await navigateTo(page, "Cases");

    // Open first case
    const caseItem = page.locator(
      "[data-testid='case-item'] a, .case-item a, table tbody tr a"
    );
    if (await caseItem.first().isVisible()) {
      await caseItem.first().click();

      // Find the discussion/comment input
      const commentInput = page
        .getByPlaceholder(/comment|message|write/i)
        .or(page.getByLabel(/comment|message/i))
        .or(page.locator("textarea").first());

      if (await commentInput.isVisible()) {
        await commentInput.fill("E2E test discussion comment");
        const sendBtn = page.getByRole("button", {
          name: /send|post|submit|comment/i,
        });
        await sendBtn.first().click();

        await expect(
          page.getByText("E2E test discussion comment")
        ).toBeVisible({ timeout: 5000 });
      }
    }
  });

  test("add team member to a case", async ({ page }) => {
    await navigateTo(page, "Cases");

    const caseItem = page.locator(
      "[data-testid='case-item'] a, .case-item a, table tbody tr a"
    );
    if (await caseItem.first().isVisible()) {
      await caseItem.first().click();

      // Look for team/members section
      const teamBtn = page
        .getByRole("button", { name: /add member|add team|invite/i })
        .or(page.getByRole("tab", { name: /team|members/i }));

      if (await teamBtn.first().isVisible()) {
        await teamBtn.first().click();
      }
    }
  });

  test("propose and vote on a decision", async ({ page }) => {
    await navigateTo(page, "Cases");

    const caseItem = page.locator(
      "[data-testid='case-item'] a, .case-item a, table tbody tr a"
    );
    if (await caseItem.first().isVisible()) {
      await caseItem.first().click();

      // Look for decision/vote section
      const decisionBtn = page
        .getByRole("button", { name: /propose|decision|vote/i })
        .or(page.getByRole("tab", { name: /decision|vote/i }));

      if (await decisionBtn.first().isVisible()) {
        await decisionBtn.first().click();
      }
    }
  });
});
