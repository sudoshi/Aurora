import { test, expect } from '@playwright/test';

test.describe('Smoke tests', () => {
  test('app loads login page', async ({ page }) => {
    await page.goto('/login');
    // The login page should have an email input
    await expect(page.getByLabel(/email/i)).toBeVisible();
  });

  test('app returns 200 on base URL', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.status()).toBeLessThan(400);
  });
});
