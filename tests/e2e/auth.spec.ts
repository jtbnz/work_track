import { test, expect } from '@playwright/test';
import { login, logout } from '../fixtures/auth';

test.describe('Authentication', () => {
  test('should display login page', async ({ page }) => {
    await page.goto('/login.php');

    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('should login with valid credentials', async ({ page }) => {
    await page.goto('/login.php');

    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin');
    await page.click('button[type="submit"]');

    // Should redirect to dashboard
    await page.waitForURL('**/index.php');
    await expect(page.locator('.nav-menu')).toBeVisible();
  });

  test('should show error with invalid credentials', async ({ page }) => {
    await page.goto('/login.php');

    await page.fill('input[name="username"]', 'invalid');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');

    // Should show error message
    await expect(page.locator('.alert-danger, .error-message')).toBeVisible();
    // Should still be on login page
    await expect(page.url()).toContain('login.php');
  });

  test('should redirect to login when accessing protected page without authentication', async ({ page }) => {
    // Try to access protected page directly
    await page.goto('/projects.php');

    // Should redirect to login
    await expect(page.url()).toContain('login.php');
  });

  test('should logout successfully', async ({ page }) => {
    // Login first
    await login(page);

    // Verify logged in
    await expect(page.url()).toContain('index.php');

    // Logout
    await logout(page);

    // Should be on login page
    await expect(page.url()).toContain('login.php');
  });

  test('should maintain session after page navigation', async ({ page }) => {
    await login(page);

    // Navigate to different pages
    await page.goto('/projects.php');
    await expect(page.locator('.page-title')).toBeVisible();

    await page.goto('/clients.php');
    await expect(page.locator('.page-title')).toBeVisible();

    // Should still be logged in
    await expect(page.locator('.nav-menu')).toBeVisible();
  });
});
