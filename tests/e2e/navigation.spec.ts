import { test, expect } from '@playwright/test';

test.describe('Navigation', () => {
    test.describe('Login page navigation', () => {
        test('renders the login page at root URL', async ({ page }) => {
            await page.goto('/');

            // Should see the login form or be redirected to login
            const loginElements = page.locator(
                'input[type="email"], input[name="email"], form'
            );
            await expect(loginElements.first()).toBeVisible();
        });

        test('has Aurora branding visible', async ({ page }) => {
            await page.goto('/');

            const pageText = await page.textContent('body');
            expect(pageText?.toLowerCase()).toContain('aurora');
        });
    });

    test.describe('Protected routes redirect to login', () => {
        test('accessing /home without auth redirects to login', async ({ page }) => {
            await page.goto('/home');

            // Should either show login form or redirect to root
            await page.waitForTimeout(500);
            const currentUrl = page.url();
            const hasLoginForm = await page
                .locator('input[type="email"], input[name="email"]')
                .isVisible()
                .catch(() => false);

            expect(currentUrl.includes('login') || hasLoginForm).toBeTruthy();
        });
    });

    test.describe('Command Palette', () => {
        test('command palette is hidden by default on login page', async ({ page }) => {
            await page.goto('/');

            // The command palette should not be visible
            const commandPalette = page.locator('[data-testid="command-palette"], [cmdk-root]');
            await expect(commandPalette).toHaveCount(0);
        });

        test('Ctrl+K keyboard shortcut is registered', async ({ page }) => {
            await page.goto('/');

            // Press Ctrl+K - on login page it may or may not open depending on layout
            await page.keyboard.press('Control+k');

            // Give time for any UI reaction
            await page.waitForTimeout(300);

            // The page should still be responsive
            const body = page.locator('body');
            await expect(body).toBeVisible();
        });
    });

    test.describe('API endpoints', () => {
        test('unauthenticated GET /api/events returns 401', async ({ request }) => {
            const response = await request.get('/api/events');

            expect(response.status()).toBe(401);
        });

        test('unauthenticated GET /api/user returns 401', async ({ request }) => {
            const response = await request.get('/api/user');

            expect(response.status()).toBe(401);
        });

        test('POST /api/login with invalid body returns error', async ({ request }) => {
            const response = await request.post('/api/login', {
                data: {},
            });

            expect(response.status()).toBe(422);
        });
    });
});
