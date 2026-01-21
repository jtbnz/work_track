import { test, expect } from '../fixtures/auth';
import * as fs from 'fs';
import * as path from 'path';

test.describe('Quoting Settings', () => {
  test('should display quoting settings page', async ({ authenticatedPage: page }) => {
    await page.goto('/quotingSettings.php');

    // Check page title
    await expect(page.locator('h1')).toContainText('Quoting Settings');

    // Check main sections exist
    await expect(page.locator('h3:has-text("Company Logo")')).toBeVisible();
    await expect(page.locator('h3:has-text("Company Details")')).toBeVisible();
    await expect(page.locator('h3:has-text("Rates & Charges")')).toBeVisible();
    await expect(page.locator('h3:has-text("Quote PDF Settings")')).toBeVisible();
  });

  test('should save company details', async ({ authenticatedPage: page }) => {
    await page.goto('/quotingSettings.php');

    // Fill in company details
    await page.fill('#company_name', 'Test Company Pty Ltd');
    await page.fill('#company_abn', '12 345 678 901');
    await page.fill('#company_address', '123 Test Street, Sydney NSW 2000');
    await page.fill('#company_phone', '(02) 1234 5678');
    await page.fill('#company_email', 'test@testcompany.com');
    await page.fill('#company_website', 'https://www.testcompany.com');

    // Save settings
    await page.click('button:has-text("Save Settings")');

    // Check success message
    await expect(page.locator('.alert-success')).toContainText('Settings saved successfully');

    // Reload page and verify values persisted
    await page.reload();

    await expect(page.locator('#company_name')).toHaveValue('Test Company Pty Ltd');
    await expect(page.locator('#company_abn')).toHaveValue('12 345 678 901');
    await expect(page.locator('#company_phone')).toHaveValue('(02) 1234 5678');
    await expect(page.locator('#company_email')).toHaveValue('test@testcompany.com');
    await expect(page.locator('#company_website')).toHaveValue('https://www.testcompany.com');
  });

  test('should save rates and charges', async ({ authenticatedPage: page }) => {
    await page.goto('/quotingSettings.php');

    // Update rates
    await page.fill('#labour_rate_standard', '80');
    await page.fill('#labour_rate_premium', '100');
    await page.fill('#gst_rate', '10');
    await page.fill('#quote_validity_days', '14');

    // Save settings
    await page.click('button:has-text("Save Settings")');

    // Check success message
    await expect(page.locator('.alert-success')).toContainText('Settings saved successfully');

    // Reload and verify
    await page.reload();

    await expect(page.locator('#labour_rate_standard')).toHaveValue('80');
    await expect(page.locator('#labour_rate_premium')).toHaveValue('100');
    await expect(page.locator('#gst_rate')).toHaveValue('10');
    await expect(page.locator('#quote_validity_days')).toHaveValue('14');
  });

  test('should save quote PDF settings', async ({ authenticatedPage: page }) => {
    await page.goto('/quotingSettings.php');

    // Update terms and footer
    const customTerms = '1. Custom term one.\n2. Custom term two.\n3. Payment due within 7 days.';
    const customFooter = 'Thank you for your business! Questions? Contact us anytime.';

    await page.fill('#quote_terms', customTerms);
    await page.fill('#quote_footer_text', customFooter);

    // Save settings
    await page.click('button:has-text("Save Settings")');

    // Check success message
    await expect(page.locator('.alert-success')).toContainText('Settings saved successfully');

    // Reload and verify
    await page.reload();

    await expect(page.locator('#quote_terms')).toHaveValue(customTerms);
    await expect(page.locator('#quote_footer_text')).toHaveValue(customFooter);
  });

  test('should navigate to settings from quoting dropdown', async ({ authenticatedPage: page }) => {
    await page.goto('/quotes.php');

    // Hover over Quoting dropdown
    await page.hover('.nav-dropdown:has-text("Quoting")');

    // Click Settings link
    await page.click('.dropdown-menu a:has-text("Settings")');

    // Should be on settings page
    await expect(page).toHaveURL(/quotingSettings\.php/);
    await expect(page.locator('h1')).toContainText('Quoting Settings');
  });
});

test.describe('PDF with Company Details', () => {
  test('should generate PDF with configured company details', async ({ authenticatedPage: page }) => {
    // First configure company details
    await page.goto('/quotingSettings.php');

    await page.fill('#company_name', 'PDF Test Company');
    await page.fill('#company_phone', '1800 TEST');
    await page.fill('#company_email', 'pdf@test.com');
    await page.fill('#company_website', 'https://pdftest.com');
    await page.fill('#quote_footer_text', 'Thank you for choosing PDF Test Company!');
    await page.click('button:has-text("Save Settings")');
    await expect(page.locator('.alert-success')).toBeVisible();

    // Now generate a PDF and check it has content
    await page.goto('/quotes.php');

    // Get first quote's PDF
    const pdfButton = page.locator('.action-buttons a:has-text("PDF")').first();
    if (await pdfButton.isVisible()) {
      const downloadPromise = page.waitForEvent('download');
      await pdfButton.click();
      const download = await downloadPromise;

      // Save and verify PDF
      const filename = download.suggestedFilename();
      const downloadPath = path.join('/tmp', filename);
      await download.saveAs(downloadPath);

      const fileStats = fs.statSync(downloadPath);
      console.log('PDF file size with company details:', fileStats.size, 'bytes');
      expect(fileStats.size).toBeGreaterThan(1000);

      // Cleanup
      fs.unlinkSync(downloadPath);
    }
  });
});
