import { test as base, Page, expect } from '@playwright/test';

/**
 * Authentication fixture for WorkTrack tests
 * Provides an authenticated page context for tests that require login
 */

export interface AuthFixtures {
  authenticatedPage: Page;
}

export const test = base.extend<AuthFixtures>({
  authenticatedPage: async ({ page }, use) => {
    // Navigate to login page
    await page.goto('/login.php');

    // Fill in credentials (default admin user)
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin');

    // Submit login form
    await page.click('button[type="submit"]');

    // Wait for redirect to dashboard
    await page.waitForURL('**/index.php');

    // Verify we're logged in by checking for nav elements
    await expect(page.locator('.nav-menu')).toBeVisible();

    // Use the authenticated page
    await use(page);
  },
});

export { expect };

/**
 * Login helper function for tests that need to login manually
 */
export async function login(page: Page, username: string = 'admin', password: string = 'admin') {
  await page.goto('/login.php');
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForURL('**/index.php');
}

/**
 * Logout helper function
 */
export async function logout(page: Page) {
  await page.goto('/logout.php');
  await page.waitForURL('**/login.php');
}
