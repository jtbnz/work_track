import { test, expect } from '../fixtures/auth';
import * as fs from 'fs';
import * as path from 'path';

test.describe('Quote PDF Generation', () => {
  test('should generate PDF for existing quote', async ({ authenticatedPage: page }) => {
    // Go to quotes page
    await page.goto('/quotes.php');

    // Check if we have any quotes
    const quoteRows = page.locator('tbody tr');
    const rowCount = await quoteRows.count();

    if (rowCount === 0 || await page.locator('tbody tr td[colspan]').count() > 0) {
      // No quotes exist, create one first
      await page.goto('/quoteBuilder.php');

      // Ensure client exists
      await page.goto('/clients.php');
      const hasClients = await page.locator('tbody tr').count() > 0;
      if (!hasClients) {
        await page.goto('/clients.php?action=new');
        await page.fill('#name', 'PDF Test Client');
        await page.fill('#email', 'pdf@test.com');
        await page.click('button:has-text("Save Client")');
        await page.waitForLoadState('networkidle');
      }

      // Create a quote
      await page.goto('/quoteBuilder.php');
      const clientSelect = page.locator('select[name="client_id"]');
      await clientSelect.selectOption({ index: 1 });
      await page.fill('input[name="labour_sewing"]', '60');
      await page.click('button:has-text("Create Quote")');
      await page.waitForURL('**/quoteBuilder.php?id=*', { timeout: 10000 });
    }

    // Go to quotes list
    await page.goto('/quotes.php');

    // Click the PDF button for the first quote
    const pdfButton = page.locator('.action-buttons a:has-text("PDF")').first();
    await expect(pdfButton).toBeVisible();

    // Click PDF and wait for download
    const downloadPromise = page.waitForEvent('download');
    await pdfButton.click();
    const download = await downloadPromise;

    // Verify the download
    const filename = download.suggestedFilename();
    console.log('Downloaded PDF filename:', filename);
    expect(filename).toMatch(/Quote_Q\d{4}-\d{4}\.pdf/);

    // Save the file temporarily and verify it's a valid PDF
    const downloadPath = path.join('/tmp', filename);
    await download.saveAs(downloadPath);

    // Check file exists and has content
    const fileStats = fs.statSync(downloadPath);
    console.log('PDF file size:', fileStats.size, 'bytes');
    expect(fileStats.size).toBeGreaterThan(1000); // PDF should be at least 1KB

    // Check PDF magic bytes
    const fileBuffer = fs.readFileSync(downloadPath);
    const pdfHeader = fileBuffer.slice(0, 4).toString();
    console.log('PDF header:', pdfHeader);
    expect(pdfHeader).toBe('%PDF');

    // Clean up
    fs.unlinkSync(downloadPath);
  });

  test('should have PDF button on quote builder page', async ({ authenticatedPage: page }) => {
    // Go to quotes page first to find an existing quote
    await page.goto('/quotes.php');

    // Click edit/view on first quote to go to quote builder
    const editButton = page.locator('a:has-text("Edit"), a:has-text("View")').first();
    if (await editButton.isVisible()) {
      await editButton.click();
      await page.waitForLoadState('networkidle');

      // Verify we're on the quote builder page
      expect(page.url()).toContain('quoteBuilder.php?id=');

      // Check that PDF button exists
      const pdfButton = page.locator('a:has-text("Download PDF")');
      await expect(pdfButton).toBeVisible();

      // Verify the PDF button has correct href
      const href = await pdfButton.getAttribute('href');
      console.log('PDF button href:', href);
      expect(href).toMatch(/api\/quotePdf\.php\?id=\d+/);
    }
  });

  test('should create quote with materials and generate PDF', async ({ authenticatedPage: page }) => {
    // Ensure client exists
    await page.goto('/clients.php');
    const hasClients = await page.locator('tbody tr').count() > 0;
    if (!hasClients) {
      await page.goto('/clients.php?action=new');
      await page.fill('#name', 'Full Quote PDF Client');
      await page.fill('#email', 'fullquote@test.com');
      await page.click('button:has-text("Save Client")');
      await page.waitForLoadState('networkidle');
    }

    // Create a quote with materials
    await page.goto('/quoteBuilder.php');

    // Select client
    const clientSelect = page.locator('select[name="client_id"]');
    await clientSelect.selectOption({ index: 1 });

    // Add labour
    await page.fill('input[name="labour_cutting"]', '30');
    await page.fill('input[name="labour_sewing"]', '60');

    // Add a material
    const materialSearch = page.locator('#materialSearch');
    await materialSearch.fill('Torch');
    await page.waitForSelector('.search-results.active .search-result-item', { timeout: 5000 });
    await page.locator('.search-result-item').first().click();
    await page.waitForTimeout(500);

    // Check a misc item
    const miscCheckbox = page.locator('.misc-checkbox').first();
    await miscCheckbox.check();

    // Add special instructions
    const specialInstructions = page.locator('textarea[name="special_instructions"]');
    await specialInstructions.fill('Test instructions for PDF generation');

    // Save the quote
    await page.click('button:has-text("Create Quote")');
    await page.waitForURL('**/quoteBuilder.php?id=*', { timeout: 10000 });

    // Verify PDF button is visible
    const pdfButton = page.locator('a:has-text("Download PDF")');
    await expect(pdfButton).toBeVisible();

    // Download the PDF
    const downloadPromise = page.waitForEvent('download');
    await pdfButton.click();
    const download = await downloadPromise;

    // Verify PDF was generated
    const filename = download.suggestedFilename();
    console.log('Full quote PDF filename:', filename);
    expect(filename).toMatch(/Quote_Q\d{4}-\d{4}\.pdf/);

    // Save and verify size (should be larger with materials)
    const downloadPath = path.join('/tmp', filename);
    await download.saveAs(downloadPath);
    const fileStats = fs.statSync(downloadPath);
    console.log('Full quote PDF size:', fileStats.size, 'bytes');
    expect(fileStats.size).toBeGreaterThan(2000); // Should be larger with content

    // Clean up
    fs.unlinkSync(downloadPath);
  });
});
