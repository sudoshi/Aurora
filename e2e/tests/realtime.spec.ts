import { test, expect, type BrowserContext } from "@playwright/test";
import path from "path";

/**
 * Multi-user realtime collaboration (W1-T08).
 *
 * Two independent browser contexts (separate WebSocket connections) both signed
 * in. A message posted in one must appear in the other WITHOUT a reload, within
 * a window tighter than the 8s polling fallback — so this specifically proves
 * the Reverb push path, not polling. Requires Reverb running and the frontend
 * built with VITE_REVERB_* (see docs/deployment/realtime-reverb.md); it is
 * expected to fail if realtime is disabled.
 */

const authFile = path.join(__dirname, "..", ".auth", "admin.json");
const PUSH_WINDOW_MS = 6_000; // < the 8s poll interval, so success ⇒ push

// This test needs a running Reverb server AND a frontend built with VITE_REVERB_*
// (see docs/deployment/realtime-reverb.md). CI serves a plain `artisan serve`
// build with no reverb, so the test is opt-in: run it where realtime is actually
// provisioned with `E2E_REALTIME=1` (prod is verified via the WS 101 handshake).
const REALTIME_PROVISIONED = process.env.E2E_REALTIME === "1";

async function openGeneralChannel(context: BrowserContext) {
  const page = await context.newPage();
  await page.goto("/commons/general");
  await expect(page.getByPlaceholder(/write a message/i)).toBeVisible({
    timeout: 15_000,
  });
  return page;
}

test.describe("Realtime collaboration", () => {
  test.skip(
    !REALTIME_PROVISIONED,
    "Realtime (Reverb) not provisioned here — set E2E_REALTIME=1 with reverb running and a VITE_REVERB_* build to exercise it.",
  );

  test("a message posted in one session appears live in another", async ({
    browser,
  }) => {
    const ctxA = await browser.newContext({ storageState: authFile });
    const ctxB = await browser.newContext({ storageState: authFile });

    try {
      const pageA = await openGeneralChannel(ctxA);
      const pageB = await openGeneralChannel(ctxB);

      // Give both sockets a moment to complete the /broadcasting/auth handshake.
      await pageA.waitForTimeout(1_500);

      const unique = `live-check ${Date.now()}-${Math.floor(
        Math.random() * 1e6,
      )}`;

      const composer = pageA.getByPlaceholder(/write a message/i);
      await composer.click();
      await composer.fill(unique);
      await composer.press("Enter");

      // Sender sees it (own optimistic/echoed update)…
      await expect(pageA.getByText(unique)).toBeVisible({ timeout: 5_000 });

      // …and the OTHER session receives it via push, faster than polling would.
      await expect(pageB.getByText(unique)).toBeVisible({
        timeout: PUSH_WINDOW_MS,
      });
    } finally {
      await ctxA.close();
      await ctxB.close();
    }
  });
});
