import { test as setup, expect } from "@playwright/test";
import fs from "fs";
import path from "path";

const authFile = path.join(__dirname, "..", ".auth", "admin.json");

function tokenFromStoredState(): string | null {
  if (!fs.existsSync(authFile)) return null;

  try {
    const storageState = JSON.parse(fs.readFileSync(authFile, "utf8")) as {
      origins?: Array<{
        localStorage?: Array<{ name: string; value: string }>;
      }>;
    };

    const authItem = storageState.origins
      ?.flatMap((origin) => origin.localStorage ?? [])
      .find((item) => item.name === "aurora-auth");
    if (!authItem) return null;

    const authState = JSON.parse(authItem.value) as {
      state?: { token?: string | null; isAuthenticated?: boolean };
    };

    if (!authState.state?.isAuthenticated || !authState.state.token) {
      return null;
    }

    return authState.state.token;
  } catch {
    return null;
  }
}

setup("authenticate as admin", async ({ page, request }) => {
  const storedToken = tokenFromStoredState();
  if (storedToken) {
    const response = await request.get("/api/auth/user", {
      headers: { Authorization: `Bearer ${storedToken}` },
    });

    if (response.ok()) {
      return;
    }
  }

  await page.goto("/login");
  await page.getByLabel(/email/i).fill("admin@acumenus.net");
  await page.getByLabel(/password/i).fill("superuser");
  await page.getByRole("button", { name: /sign in/i }).click();

  // Wait for successful login
  await expect(
    page.getByRole("navigation", { name: /main navigation/i })
  ).toBeVisible({ timeout: 15_000 });

  // Save signed-in state
  await page.context().storageState({ path: authFile });
});
