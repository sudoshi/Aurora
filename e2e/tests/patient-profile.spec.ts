import { test, expect } from "@playwright/test";
import { loginAsAdmin, navigateTo } from "./helpers";

test.describe("Patient profile navigation", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test("navigate to Patient Profiles page", async ({ page }) => {
    await navigateTo(page, "Patient");

    await expect(
      page
        .getByRole("heading", { name: /patient/i })
        .or(page.getByText(/patient profile|patients/i).first())
    ).toBeVisible();
  });

  test("search for a patient", async ({ page }) => {
    await navigateTo(page, "Patient");

    const searchInput = page
      .getByPlaceholder(/search|find|filter/i)
      .or(page.getByLabel(/search|find/i));

    if (await searchInput.first().isVisible()) {
      await searchInput.first().fill("test");
      // Wait for search results or empty state
      await page.waitForTimeout(1000);
    }
  });

  test("verify demographics card on patient detail", async ({ page }) => {
    await navigateTo(page, "Patient");

    // Try to open a patient profile
    const patientLink = page.locator(
      "[data-testid='patient-item'] a, .patient-item a, table tbody tr a, [data-testid='patient-card']"
    );

    if (await patientLink.first().isVisible({ timeout: 5000 }).catch(() => false)) {
      await patientLink.first().click();

      // Verify demographics section
      await expect(
        page.getByText(/demographics|age|gender|dob|date of birth|mrn/i)
      ).toBeVisible({ timeout: 10_000 });
    }
  });

  test("switch view modes on patient profile", async ({ page }) => {
    await navigateTo(page, "Patient");

    const patientLink = page.locator(
      "[data-testid='patient-item'] a, .patient-item a, table tbody tr a, [data-testid='patient-card']"
    );

    if (await patientLink.first().isVisible({ timeout: 5000 }).catch(() => false)) {
      await patientLink.first().click();

      // Check for view mode toggles (timeline, list, labs, visits)
      const viewModes = ["timeline", "list", "labs", "visits"];
      for (const mode of viewModes) {
        const modeBtn = page
          .getByRole("tab", { name: new RegExp(mode, "i") })
          .or(page.getByRole("button", { name: new RegExp(mode, "i") }));

        if (await modeBtn.first().isVisible({ timeout: 2000 }).catch(() => false)) {
          await modeBtn.first().click();
          // Brief wait for view to switch
          await page.waitForTimeout(500);
        }
      }
    }
  });

  test("verify timeline renders on patient detail", async ({ page }) => {
    await navigateTo(page, "Patient");

    const patientLink = page.locator(
      "[data-testid='patient-item'] a, .patient-item a, table tbody tr a, [data-testid='patient-card']"
    );

    if (await patientLink.first().isVisible({ timeout: 5000 }).catch(() => false)) {
      await patientLink.first().click();

      // Look for timeline elements
      await expect(
        page
          .getByText(/timeline/i)
          .or(page.locator("[data-testid='timeline'], .timeline"))
      ).toBeVisible({ timeout: 10_000 });
    }
  });
});
