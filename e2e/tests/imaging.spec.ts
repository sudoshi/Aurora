import { test, expect, type Page } from "@playwright/test";
import { loginAsAdmin } from "./helpers";

type IndexedStudy = {
  id: number;
  study_instance_uid: string;
  status: string;
  wadors_uri?: string | null;
};

async function fetchFirstIndexedStudy(page: Page): Promise<IndexedStudy | null> {
  return page.evaluate(async () => {
    const persisted = window.localStorage.getItem("aurora-auth");
    let token: string | null = null;

    if (persisted) {
      try {
        token = JSON.parse(persisted)?.state?.token ?? null;
      } catch {
        token = null;
      }
    }

    const response = await fetch("/api/imaging/studies?per_page=25", {
      headers: {
        Accept: "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
    });

    if (!response.ok) return null;

    const payload = await response.json();
    const studies = Array.isArray(payload.data) ? payload.data : [];

    return studies.find(
      (study: IndexedStudy) => study.status === "indexed" && Boolean(study.study_instance_uid),
    ) ?? null;
  });
}

test.describe("Imaging", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto("/imaging");
    await expect(page.getByRole("heading", { name: /medical imaging/i })).toBeVisible();
  });

  test("loads the Imaging page", async ({ page }) => {
    await expect(page.getByRole("heading", { name: /medical imaging/i })).toBeVisible();
  });

  test("verify study browser loads", async ({ page }) => {
    await expect(page.getByRole("button", { name: /studies/i })).toBeVisible();
    const table = page.getByRole("table").first();
    await expect(table).toBeVisible({ timeout: 10_000 });
    // At least one study row with an index status renders. Don't require
    // "indexed" specifically: that status only exists after Orthanc indexing,
    // which isn't available in CI (studies seed as "pending"). Match either.
    await expect(table.getByText(/pending|indexed/i).first()).toBeVisible({
      timeout: 10_000,
    });
  });

  test("verify stats bar renders", async ({ page }) => {
    await expect(page.getByText("Total Studies", { exact: true }).first()).toBeVisible({ timeout: 10_000 });
    await expect(page.getByText("AI Features", { exact: true }).first()).toBeVisible();
    await expect(page.getByText("Persons with Imaging", { exact: true })).toBeVisible();
  });

  test("opens an indexed study detail page with StudyInstanceUID in the OHIF URL", async ({ page }) => {
    const study = await fetchFirstIndexedStudy(page);
    test.skip(!study, "No indexed imaging study is available in this environment");
    if (!study) return;

    await page.goto(`/imaging/studies/${study.id}`);

    await expect(page.getByRole("heading", { name: /dicom study/i })).toBeVisible();
    await expect(page.getByText(study.study_instance_uid).first()).toBeVisible();

    const iframe = page.locator('iframe[title="OHIF DICOM Viewer"]');
    await expect(iframe).toBeVisible({ timeout: 15_000 });

    const src = await iframe.getAttribute("src");
    expect(src).toContain("/ohif/viewer");
    expect(decodeURIComponent(src ?? "")).toContain(`StudyInstanceUIDs=${study.study_instance_uid}`);
  });
});
