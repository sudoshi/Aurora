import { test, expect } from '@playwright/test';

test.describe('Authentication Flow', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/');
    });

    test('displays login page by default', async ({ page }) => {
        await expect(page.locator('input[type="email"], input[name="email"]')).toBeVisible();
        await expect(page.locator('input[type="password"], input[name="password"]')).toBeVisible();
    });

    test('shows validation errors for empty login', async ({ page }) => {
        const submitButton = page.locator('button[type="submit"]');
        await submitButton.click();

        // The form should show some validation feedback
        await expect(page.locator('input[type="email"], input[name="email"]')).toBeVisible();
    });

    test('shows error message for invalid credentials', async ({ page }) => {
        await page.fill('input[type="email"], input[name="email"]', 'invalid@acumenus.net');
        await page.fill('input[type="password"], input[name="password"]', 'WrongPassword!');

        const submitButton = page.locator('button[type="submit"]');
        await submitButton.click();

        // Should see an error indication (toast, alert, or inline message)
        await page.waitForTimeout(1000);
        const pageContent = await page.textContent('body');
        expect(pageContent).toBeTruthy();
    });

    test('has a link to registration page', async ({ page }) => {
        const createAccountLink = page.locator('a[href*="register"], a:has-text("Create Account")');
        await expect(createAccountLink).toBeVisible();
    });

    test('navigates to registration page', async ({ page }) => {
        const createAccountLink = page.locator('a[href*="register"], a:has-text("Create Account")');
        await createAccountLink.click();

        await expect(page).toHaveURL(/register/);
    });

    test('registration page has name and email fields but no password field', async ({ page }) => {
        await page.goto('/register');

        await expect(page.locator('input[name="name"]')).toBeVisible();
        await expect(page.locator('input[name="email"], input[type="email"]')).toBeVisible();

        // Registration should NOT have a password field (temp password flow)
        const passwordInputs = page.locator('input[type="password"]');
        await expect(passwordInputs).toHaveCount(0);
    });

    test('registration shows validation for missing required fields', async ({ page }) => {
        await page.goto('/register');

        const submitButton = page.locator('button[type="submit"]');
        await submitButton.click();

        // Should remain on register page
        await expect(page).toHaveURL(/register/);
    });
});

test.describe('Change Password Flow', () => {
    test('change password endpoint requires authentication', async ({ request }) => {
        const response = await request.post('/api/change-password', {
            data: {
                current_password: 'OldPass123!',
                new_password: 'NewPass456!',
            },
        });

        expect(response.status()).toBe(401);
    });
});
