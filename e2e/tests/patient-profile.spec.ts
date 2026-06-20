import { test, expect, type Page } from "@playwright/test";

interface PatientListRow {
  id: number;
  mrn: string;
}

function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

async function authToken(page: Page): Promise<string> {
  await page.goto("/");

  const token = await page.evaluate(() => {
    const persisted = window.localStorage.getItem("aurora-auth");
    if (!persisted) return null;

    try {
      return JSON.parse(persisted).state?.token ?? null;
    } catch {
      return null;
    }
  });

  expect(token, "admin auth token should be present").toBeTruthy();

  return token as string;
}

async function firstResolvablePatient(page: Page): Promise<PatientListRow | null> {
  const token = await authToken(page);
  const headers = { Authorization: `Bearer ${token}` };
  const listResponse = await page.request.get("/api/patients?per_page=25", {
    headers,
  });

  expect(listResponse.ok()).toBeTruthy();

  const payload = await listResponse.json() as {
    data?: { data?: PatientListRow[] };
  };

  for (const patient of payload.data?.data ?? []) {
    const profileResponse = await page.request.get(
      `/api/patients/${patient.id}/profile`,
      { headers },
    );

    if (profileResponse.ok()) {
      return patient;
    }
  }

  return null;
}

async function openResolvablePatientProfile(page: Page): Promise<void> {
  const patient = await firstResolvablePatient(page);
  test.skip(
    patient === null,
    "No API-resolvable patient profiles available in the public E2E dataset",
  );

  await page.goto("/profiles");
  const row = page
    .getByRole("row", { name: new RegExp(escapeRegExp(patient!.mrn), "i") })
    .first();

  await expect(row).toBeVisible({ timeout: 10_000 });
  await row.click();

  await expect(
    page.getByRole("heading", { name: /patient profile/i })
  ).toBeVisible({ timeout: 10_000 });
  await expect(page.getByText(`Patient #${patient!.id}`)).toBeVisible();
}

test.describe("Patient profile navigation", () => {
  test("patient list page loads with table", async ({ page }) => {
    await page.goto("/profiles");

    // Heading visible
    await expect(
      page.getByRole("heading", { name: /patient profiles/i })
    ).toBeVisible();

    // The table renders either patient rows or an explicit empty state.
    const rows = page.locator("table tbody tr");
    await expect(rows.first()).toBeVisible({ timeout: 10_000 });
    expect(await rows.count()).toBeGreaterThan(0);
  });

  test("navigate to patient profile and view tabs", async ({ page }) => {
    await openResolvablePatientProfile(page);

    // View mode buttons are visible
    await expect(
      page.getByRole("button", { name: /timeline/i })
    ).toBeVisible();
    await expect(
      page.getByRole("button", { name: /labs/i })
    ).toBeVisible();
  });

  test("can switch between view modes", async ({ page }) => {
    await openResolvablePatientProfile(page);

    // Click Timeline button and assert content appears
    await page.getByRole("button", { name: /timeline/i }).click();
    await expect(
      page.locator("main").getByText(/timeline|visit|event|date/i).first()
    ).toBeVisible({ timeout: 10_000 });

    // Click Labs button and assert content appears
    await page.getByRole("button", { name: /labs/i }).click();
    await expect(
      page.locator("main").getByText(/labs|results|test|value/i).first()
    ).toBeVisible({ timeout: 10_000 });
  });
});
