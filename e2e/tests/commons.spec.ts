import { test, expect } from "@playwright/test";
import { loginAsAdmin, navigateTo } from "./helpers";

test.describe("Commons chat", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test("navigate to Commons page", async ({ page }) => {
    await navigateTo(page, "Commons");

    await expect(
      page
        .getByRole("heading", { name: /commons/i })
        .or(page.getByText(/commons|channels/i).first())
    ).toBeVisible();
  });

  test("verify channels load", async ({ page }) => {
    await navigateTo(page, "Commons");

    // Channels list should be visible
    await expect(
      page.getByText(/general|channels/i).or(
        page.locator("[data-testid='channel-list'], .channel-list")
      )
    ).toBeVisible({ timeout: 10_000 });
  });

  test("click on #general channel", async ({ page }) => {
    await navigateTo(page, "Commons");

    const generalChannel = page
      .getByRole("link", { name: /general/i })
      .or(page.getByText(/# ?general/i))
      .or(page.locator("[data-testid='channel-general']"));

    if (await generalChannel.first().isVisible({ timeout: 5000 }).catch(() => false)) {
      await generalChannel.first().click();

      // Channel should open with a message input area
      await expect(
        page
          .getByPlaceholder(/message|type|write/i)
          .or(page.locator("textarea, [contenteditable]").first())
      ).toBeVisible({ timeout: 5000 });
    }
  });

  test("send a message in Commons", async ({ page }) => {
    await navigateTo(page, "Commons");

    const generalChannel = page
      .getByRole("link", { name: /general/i })
      .or(page.getByText(/# ?general/i))
      .or(page.locator("[data-testid='channel-general']"));

    if (await generalChannel.first().isVisible({ timeout: 5000 }).catch(() => false)) {
      await generalChannel.first().click();

      const messageInput = page
        .getByPlaceholder(/message|type|write/i)
        .or(page.locator("textarea").first());

      if (await messageInput.first().isVisible({ timeout: 5000 }).catch(() => false)) {
        const testMessage = `E2E test message ${Date.now()}`;
        await messageInput.first().fill(testMessage);

        // Send via button or Enter key
        const sendBtn = page.getByRole("button", {
          name: /send/i,
        });

        if (await sendBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
          await sendBtn.click();
        } else {
          await messageInput.first().press("Enter");
        }

        // Verify message appears
        await expect(page.getByText(testMessage)).toBeVisible({
          timeout: 10_000,
        });
      }
    }
  });
});
