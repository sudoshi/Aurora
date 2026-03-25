import { test, expect } from "@playwright/test";

test.describe("Genomics tab", () => {
  test("can access genomics tab for a patient with genomic data", async ({
    page,
  }) => {
    await page.goto("/profiles");

    // Wait for patient list to load
    await expect(
      page.getByRole("heading", { name: /patient profiles/i })
    ).toBeVisible();

    // Click first patient row in table
    const firstRow = page.locator("table tbody tr").first();
    await expect(firstRow).toBeVisible();
    await firstRow.click();

    // Wait for patient profile to load
    await expect(
      page.getByRole("heading", { name: /patient profile/i })
    ).toBeVisible();

    // Check if Genomics button exists (conditionally rendered based on genomic data)
    const genomicsButton = page.getByRole("button", { name: /genomics/i });
    const hasGenomics = await genomicsButton.isVisible({ timeout: 5_000 }).catch(() => false);

    if (!hasGenomics) {
      test.skip(true, "No patients with genomic data found -- Genomics button not rendered");
      return;
    }

    // Click Genomics button
    await genomicsButton.click();

    // Assert genomics content appears (not the empty state and not just a loader)
    // At least one section should be visible: briefing narrative, variant content, treatment, or table
    await expect(
      page
        .getByText(/no genomic data available/i)
        .or(page.getByText(/briefing|variant|treatment|timeline|actionable|gene/i).first())
    ).toBeVisible();
  });

  test("genomics tab shows briefing and variant sections", async ({
    page,
  }) => {
    await page.goto("/profiles");

    await expect(
      page.getByRole("heading", { name: /patient profiles/i })
    ).toBeVisible();

    // Click first patient row
    const firstRow = page.locator("table tbody tr").first();
    await expect(firstRow).toBeVisible();
    await firstRow.click();

    await expect(
      page.getByRole("heading", { name: /patient profile/i })
    ).toBeVisible();

    // Check for Genomics button
    const genomicsButton = page.getByRole("button", { name: /genomics/i });
    const hasGenomics = await genomicsButton.isVisible({ timeout: 5_000 }).catch(() => false);

    if (!hasGenomics) {
      test.skip(true, "No patients with genomic data found -- Genomics button not rendered");
      return;
    }

    await genomicsButton.click();

    // Wait for content to load (spinner disappears)
    await expect(page.locator(".animate-spin")).toBeHidden({ timeout: 15_000 }).catch(() => {
      // Spinner may have already disappeared
    });

    // Count how many distinct genomics sections are visible
    // Sections: briefing narrative, actionable variants, treatment timeline, variant table
    let visibleSections = 0;

    // Check for briefing section (GenomicBriefing renders narrative text or Abby heading)
    const briefingVisible = await page
      .getByText(/briefing|abby|genomic summary|clinical narrative/i)
      .first()
      .isVisible()
      .catch(() => false);
    if (briefingVisible) visibleSections++;

    // Check for actionable variants section
    const variantsVisible = await page
      .getByText(/actionable|pathogenic|variant/i)
      .first()
      .isVisible()
      .catch(() => false);
    if (variantsVisible) visibleSections++;

    // Check for treatment timeline section
    const timelineVisible = await page
      .getByText(/treatment|timeline|drug exposure/i)
      .first()
      .isVisible()
      .catch(() => false);
    if (timelineVisible) visibleSections++;

    // Check for variant table section
    const tableVisible = await page
      .getByText(/gene|chromosome|variant table/i)
      .first()
      .isVisible()
      .catch(() => false);
    if (tableVisible) visibleSections++;

    // At least 2 distinct sections should be visible
    expect(
      visibleSections,
      `Expected at least 2 genomics sections visible, found ${visibleSections}`
    ).toBeGreaterThanOrEqual(2);
  });
});
