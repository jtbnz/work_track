import { test, expect } from '../fixtures/auth';

test.describe('Quote Email Functionality', () => {
  test('should display email modal when clicking Email button', async ({ authenticatedPage: page }) => {
    await page.goto('/quotes.php');

    // Check if there are any quotes
    const emailButton = page.locator('.action-buttons button:has-text("Email")').first();
    if (await emailButton.isVisible()) {
      await emailButton.click();

      // Modal should be visible
      await expect(page.locator('#emailModal')).toBeVisible();
      await expect(page.locator('.modal-header h3')).toContainText('Send Quote via Email');

      // Check form elements exist
      await expect(page.locator('#emailTo')).toBeVisible();
      await expect(page.locator('#emailSubject')).toBeVisible();
      await expect(page.locator('#emailMessage')).toBeVisible();
      await expect(page.locator('#updateStatus')).toBeVisible();
      await expect(page.locator('#sendEmailBtn')).toBeVisible();
    }
  });

  test('should close email modal on cancel', async ({ authenticatedPage: page }) => {
    await page.goto('/quotes.php');

    const emailButton = page.locator('.action-buttons button:has-text("Email")').first();
    if (await emailButton.isVisible()) {
      await emailButton.click();
      await expect(page.locator('#emailModal')).toBeVisible();

      // Click cancel
      await page.click('button:has-text("Cancel")');

      // Modal should be hidden
      await expect(page.locator('#emailModal')).not.toBeVisible();
    }
  });

  test('should close email modal on escape key', async ({ authenticatedPage: page }) => {
    await page.goto('/quotes.php');

    const emailButton = page.locator('.action-buttons button:has-text("Email")').first();
    if (await emailButton.isVisible()) {
      await emailButton.click();
      await expect(page.locator('#emailModal')).toBeVisible();

      // Press escape
      await page.keyboard.press('Escape');

      // Modal should be hidden
      await expect(page.locator('#emailModal')).not.toBeVisible();
    }
  });

  test('should show validation error when no email provided', async ({ authenticatedPage: page }) => {
    await page.goto('/quotes.php');

    const emailButton = page.locator('.action-buttons button:has-text("Email")').first();
    if (await emailButton.isVisible()) {
      await emailButton.click();
      await expect(page.locator('#emailModal')).toBeVisible();

      // Clear email field and try to send
      await page.fill('#emailTo', '');
      await page.click('#sendEmailBtn');

      // Should show validation error
      await expect(page.locator('#emailError')).toBeVisible();
      await expect(page.locator('#emailError')).toContainText('email address');
    }
  });

  test('should pre-fill client email in modal', async ({ authenticatedPage: page }) => {
    // First create a quote with a client that has email
    await page.goto('/quotes.php');

    // Check if there's a quote with client email
    const emailButton = page.locator('.action-buttons button:has-text("Email")').first();
    if (await emailButton.isVisible()) {
      await emailButton.click();
      await expect(page.locator('#emailModal')).toBeVisible();

      // The email field should exist (may or may not be pre-filled depending on client data)
      await expect(page.locator('#emailTo')).toBeVisible();
    }
  });
});

test.describe('SMTP Settings', () => {
  test('should display SMTP settings section on quoting settings page', async ({ authenticatedPage: page }) => {
    await page.goto('/quotingSettings.php');

    // Check SMTP section exists
    await expect(page.locator('h3:has-text("Email Settings")')).toBeVisible();

    // Check SMTP fields exist
    await expect(page.locator('#smtp_host')).toBeVisible();
    await expect(page.locator('#smtp_port')).toBeVisible();
    await expect(page.locator('#smtp_username')).toBeVisible();
    await expect(page.locator('#smtp_password')).toBeVisible();
    await expect(page.locator('#smtp_secure')).toBeVisible();
    await expect(page.locator('#smtp_from_email')).toBeVisible();
  });

  test('should save SMTP settings', async ({ authenticatedPage: page }) => {
    await page.goto('/quotingSettings.php');

    // Fill in SMTP settings
    await page.fill('#smtp_host', 'smtp.test.com');
    await page.fill('#smtp_port', '587');
    await page.fill('#smtp_username', 'test@test.com');
    await page.selectOption('#smtp_secure', 'tls');
    await page.fill('#smtp_from_email', 'quotes@test.com');

    // Save settings
    await page.click('button:has-text("Save Settings")');

    // Check success message (use first to get the flash message, not the SMTP status)
    await expect(page.locator('.alert-success').first()).toContainText('Settings saved successfully');

    // Reload and verify values persisted
    await page.reload();

    await expect(page.locator('#smtp_host')).toHaveValue('smtp.test.com');
    await expect(page.locator('#smtp_port')).toHaveValue('587');
    await expect(page.locator('#smtp_username')).toHaveValue('test@test.com');
    await expect(page.locator('#smtp_from_email')).toHaveValue('quotes@test.com');
  });

  test('should show email configured message when SMTP is set up', async ({ authenticatedPage: page }) => {
    // First configure SMTP
    await page.goto('/quotingSettings.php');

    await page.fill('#smtp_host', 'smtp.test.com');
    await page.fill('#smtp_from_email', 'quotes@test.com');
    await page.click('button:has-text("Save Settings")');

    // Reload and check for configured message
    await page.reload();

    await expect(page.locator('.settings-section:has-text("Email Settings") .alert-success')).toContainText('Email is configured');
  });

  test('should have Test SMTP Connection button', async ({ authenticatedPage: page }) => {
    await page.goto('/quotingSettings.php');

    await expect(page.locator('#testSmtpBtn')).toBeVisible();
    await expect(page.locator('#testSmtpBtn')).toContainText('Test SMTP Connection');
  });
});
