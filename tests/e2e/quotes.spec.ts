import { test, expect } from '../fixtures/auth';
import { generateClient } from '../fixtures/testData';

test.describe('Quotes', () => {
  test.beforeEach(async ({ authenticatedPage: page }) => {
    // Create a test client for quotes if needed
    await page.goto('/clients.php');

    const hasClients = await page.locator('tbody tr').count() > 0;

    if (!hasClients) {
      await page.goto('/clients.php?action=new');
      await page.fill('#name', 'Test Quote Client');
      await page.fill('#email', 'quote-test@test.com');
      await page.click('button:has-text("Save Client")');
    }
  });

  test('should display quotes list page', async ({ authenticatedPage: page }) => {
    await page.goto('/quotes.php');

    await expect(page.locator('.page-title')).toContainText('Quotes');
    await expect(page.locator('table')).toBeVisible();
  });

  test('should show empty state when no quotes exist', async ({ authenticatedPage: page }) => {
    await page.goto('/quotes.php');

    const tableBody = page.locator('tbody');
    const rows = await tableBody.locator('tr').count();

    if (rows === 1) {
      // Check for empty state message
      const text = await tableBody.textContent();
      expect(text?.toLowerCase()).toMatch(/no quotes|create your first/i);
    }
  });

  test('should navigate to quote builder for new quote', async ({ authenticatedPage: page }) => {
    await page.goto('/quotes.php');

    // Click new quote button
    await page.click('a:has-text("New Quote")');

    // Should be on quote builder page
    await expect(page.url()).toContain('quoteBuilder');
  });

  test('should create a new quote with basic details', async ({ authenticatedPage: page }) => {
    await page.goto('/quoteBuilder.php');

    // Select a client
    const clientSelect = page.locator('select[name="client_id"]');
    await clientSelect.selectOption({ index: 1 }); // Select first available client

    // Set dates
    const today = new Date().toISOString().split('T')[0];
    await page.fill('input[name="quote_date"]', today);

    // Add labour (in minutes)
    await page.fill('input[name="labour_stripping"]', '30');
    await page.fill('input[name="labour_cutting"]', '45');
    await page.fill('input[name="labour_sewing"]', '60');
    await page.fill('input[name="labour_upholstery"]', '90');

    // Submit to create quote
    await page.click('button:has-text("Create Quote")');

    // Should redirect or show success
    await expect(page.locator('.alert-success')).toBeVisible({ timeout: 5000 });
  });

  test.skip('should calculate labour totals live', async ({ authenticatedPage: page }) => {
    // TODO: Fix dispatchEvent triggering for input events - Playwright fill() doesn't trigger input events
    await page.goto('/quoteBuilder.php');

    // Select a client first (required to enable form)
    const clientSelect = page.locator('select[name="client_id"]');
    const options = await clientSelect.locator('option').count();
    if (options > 1) {
      await clientSelect.selectOption({ index: 1 });
    }

    // Enter labour minutes and trigger input event
    await page.locator('input[name="labour_stripping"]').fill('60');
    await page.locator('input[name="labour_stripping"]').dispatchEvent('input');

    await page.locator('input[name="labour_sewing"]').fill('120');
    await page.locator('input[name="labour_sewing"]').dispatchEvent('input');

    // Wait for calculation
    await page.waitForTimeout(500);

    // Check total time display - should show 3h
    const totalTimeElement = page.locator('#totalLabourTime');
    await expect(totalTimeElement).toBeVisible();
    const timeText = await totalTimeElement.textContent();
    expect(timeText).toContain('3h');
  });

  test.skip('should change labour rate type', async ({ authenticatedPage: page }) => {
    // TODO: Fix dispatchEvent triggering for input events - Playwright fill() doesn't trigger input events
    await page.goto('/quoteBuilder.php');

    // Select a client first (required to enable form)
    const clientSelect = page.locator('select[name="client_id"]');
    const options = await clientSelect.locator('option').count();
    if (options > 1) {
      await clientSelect.selectOption({ index: 1 });
    }

    // Enter some labour and trigger input event
    await page.locator('input[name="labour_sewing"]').fill('60');
    await page.locator('input[name="labour_sewing"]').dispatchEvent('input');

    // Select standard rate first
    await page.selectOption('select[name="labour_rate_type"]', 'standard');
    await page.waitForTimeout(500);

    const standardTotal = await page.locator('#labourTotal').textContent();

    // Switch to premium rate
    await page.selectOption('select[name="labour_rate_type"]', 'premium');
    await page.waitForTimeout(500);

    const premiumTotal = await page.locator('#labourTotal').textContent();

    // Premium should be different (higher)
    expect(premiumTotal).not.toEqual(standardTotal);
  });

  test('should filter quotes by status', async ({ authenticatedPage: page }) => {
    await page.goto('/quotes.php');

    // Find status filter
    const statusFilter = page.locator('select[name="status"]');

    if (await statusFilter.isVisible()) {
      // Select Draft status
      await statusFilter.selectOption('draft');
      await page.click('button:has-text("Filter")');

      // URL should contain status parameter
      await expect(page.url()).toContain('status=draft');
    }
  });

  test('should filter quotes by client', async ({ authenticatedPage: page }) => {
    await page.goto('/quotes.php');

    // Find client filter
    const clientFilter = page.locator('select[name="client"]');

    if (await clientFilter.isVisible()) {
      const options = await clientFilter.locator('option').count();

      if (options > 1) {
        // Select first client
        await clientFilter.selectOption({ index: 1 });
        await page.click('button:has-text("Filter")');

        // URL should contain client parameter
        await expect(page.url()).toContain('client=');
      }
    }
  });

  test('should clear filters', async ({ authenticatedPage: page }) => {
    await page.goto('/quotes.php?status=draft&client=1');

    // Click clear filter button
    const clearButton = page.locator('a:has-text("Clear")');

    if (await clearButton.isVisible()) {
      await clearButton.click();

      // Should be on clean quotes page
      await expect(page.url()).not.toContain('status=');
    }
  });
});

test.describe('Quote Builder - Editing', () => {
  test('should save quote and show success message', async ({ authenticatedPage: page }) => {
    // Ensure client exists
    await page.goto('/clients.php');
    const hasClients = await page.locator('tbody tr').count() > 0;
    if (!hasClients) {
      await page.goto('/clients.php?action=new');
      await page.fill('#name', 'Test Quote Client');
      await page.fill('#email', 'quote-test@test.com');
      await page.click('button:has-text("Save Client")');
    }

    // Create a quote
    await page.goto('/quoteBuilder.php');

    const clientSelect = page.locator('select[name="client_id"]');
    await clientSelect.selectOption({ index: 1 });

    await page.fill('input[name="labour_sewing"]', '60');

    await page.click('button:has-text("Create Quote")');

    // Should show success
    await expect(page.locator('.alert-success')).toBeVisible({ timeout: 5000 });

    // Should now be in edit mode with quote ID in URL
    await expect(page.url()).toContain('id=');
  });

  test.skip('should update totals when labour values change', async ({ authenticatedPage: page }) => {
    // TODO: Fix dispatchEvent triggering for input events - Playwright fill() doesn't trigger input events
    // Ensure client exists
    await page.goto('/clients.php');
    const hasClients = await page.locator('tbody tr').count() > 0;
    if (!hasClients) {
      await page.goto('/clients.php?action=new');
      await page.fill('#name', 'Test Quote Client');
      await page.fill('#email', 'quote-test@test.com');
      await page.click('button:has-text("Save Client")');
    }

    // Create a quote first
    await page.goto('/quoteBuilder.php');

    const clientSelect = page.locator('select[name="client_id"]');
    await clientSelect.selectOption({ index: 1 });

    // Add some initial labour and trigger input event
    await page.locator('input[name="labour_sewing"]').fill('30');
    await page.locator('input[name="labour_sewing"]').dispatchEvent('input');

    await page.click('button:has-text("Create Quote")');

    await page.waitForURL('**/quoteBuilder.php?id=*', { timeout: 10000 });

    // Get initial labour total
    const initialLabour = await page.locator('#labourTotal').textContent();

    // Add more labour and trigger input event
    await page.locator('input[name="labour_sewing"]').fill('120');
    await page.locator('input[name="labour_sewing"]').dispatchEvent('input');
    await page.waitForTimeout(500);

    // Labour total should change
    const newLabour = await page.locator('#labourTotal').textContent();
    expect(newLabour).not.toEqual(initialLabour);
  });
});

test.describe('Quote Builder - Materials and Misc', () => {
  test('should show materials section available for new quotes', async ({ authenticatedPage: page }) => {
    // Ensure client exists
    await page.goto('/clients.php');
    const hasClients = await page.locator('tbody tr').count() > 0;
    if (!hasClients) {
      await page.goto('/clients.php?action=new');
      await page.fill('#name', 'Test Quote Client');
      await page.fill('#email', 'quote-test@test.com');
      await page.click('button:has-text("Save Client")');
    }

    // Go to new quote builder
    await page.goto('/quoteBuilder.php');

    // Materials section should be immediately available (client-side mode)
    await expect(page.locator('#materialSearch')).toBeVisible();

    // Misc checkboxes should also be available
    await expect(page.locator('.misc-checkbox').first()).toBeVisible();

    // Verify misc checkboxes are unchecked by default
    const checkedCount = await page.locator('.misc-checkbox:checked').count();
    expect(checkedCount).toBe(0);

    // Select a client
    const clientSelect = page.locator('select[name="client_id"]');
    await clientSelect.selectOption({ index: 1 });

    // Add some labour
    await page.fill('input[name="labour_sewing"]', '60');

    // Create the quote
    await page.click('button:has-text("Create Quote")');

    // Wait for redirect to saved quote
    await page.waitForURL('**/quoteBuilder.php?id=*', { timeout: 10000 });

    // Materials section should still be available after save
    await expect(page.locator('#materialSearch')).toBeVisible();

    // Misc checkboxes should still be visible
    await expect(page.locator('.misc-checkbox').first()).toBeVisible();
  });

  test('should persist materials/misc after page reload', async ({ authenticatedPage: page }) => {
    // Ensure client exists
    await page.goto('/clients.php');
    const hasClients = await page.locator('tbody tr').count() > 0;
    if (!hasClients) {
      await page.goto('/clients.php?action=new');
      await page.fill('#name', 'Test Quote Client');
      await page.fill('#email', 'quote-test@test.com');
      await page.click('button:has-text("Save Client")');
    }

    // Create a quote first
    await page.goto('/quoteBuilder.php');
    const clientSelect = page.locator('select[name="client_id"]');
    await clientSelect.selectOption({ index: 1 });
    await page.fill('input[name="labour_sewing"]', '60');
    await page.click('button:has-text("Create Quote")');

    // Wait for redirect
    await page.waitForURL('**/quoteBuilder.php?id=*', { timeout: 10000 });

    // Get the current URL with ID
    const urlWithId = page.url();
    console.log('Quote URL:', urlWithId);

    // Verify materials section is available
    await expect(page.locator('#materialSearch')).toBeVisible();

    // Reload the page
    await page.reload();

    // Wait for page to load
    await page.waitForLoadState('networkidle');

    // Verify materials section is STILL available after reload
    await expect(page.locator('.materials-section .alert-info')).not.toBeVisible();
    await expect(page.locator('#materialSearch')).toBeVisible();

    // Verify misc section is STILL available after reload
    await expect(page.locator('.misc-section .alert-info')).not.toBeVisible();
    await expect(page.locator('.misc-checkbox').first()).toBeVisible();
  });

  test('should be able to add materials after page reload', async ({ authenticatedPage: page }) => {
    // Ensure client exists
    await page.goto('/clients.php');
    const hasClients = await page.locator('tbody tr').count() > 0;
    if (!hasClients) {
      await page.goto('/clients.php?action=new');
      await page.fill('#name', 'Test Quote Client');
      await page.fill('#email', 'quote-test@test.com');
      await page.click('button:has-text("Save Client")');
    }

    // Create a quote first
    await page.goto('/quoteBuilder.php');
    const clientSelect = page.locator('select[name="client_id"]');
    await clientSelect.selectOption({ index: 1 });
    await page.fill('input[name="labour_sewing"]', '60');
    await page.click('button:has-text("Create Quote")');

    // Wait for redirect
    await page.waitForURL('**/quoteBuilder.php?id=*', { timeout: 10000 });
    const quoteUrl = page.url();
    console.log('Created quote URL:', quoteUrl);

    // Reload the page to simulate user coming back to existing quote
    await page.reload();
    await page.waitForLoadState('networkidle');

    // Check quoteId JavaScript variable value
    const quoteId = await page.evaluate(() => {
      // @ts-ignore
      return window.quoteId !== undefined ? window.quoteId : 'undefined';
    });
    console.log('JavaScript quoteId after reload:', quoteId);

    // Try to search for a material
    const materialSearch = page.locator('#materialSearch');
    await expect(materialSearch).toBeVisible();
    await materialSearch.fill('Torch');

    // Wait for search results
    await page.waitForSelector('.search-results.active .search-result-item', { timeout: 5000 });

    // Click the first result to add material
    await page.locator('.search-result-item').first().click();

    // Wait for page reload after adding material
    await page.waitForLoadState('networkidle');

    // Verify material was added
    const materialRows = page.locator('#materialsBody tr:not(.no-materials)');
    await expect(materialRows).toHaveCount(1);

    console.log('Successfully added material after reload!');
  });
});

test.describe('Quote with Materials', () => {
  test('should create a comprehensive quote with 5 materials and 2 labour charges', async ({ authenticatedPage: page }) => {
    // Ensure client exists
    await page.goto('/clients.php');
    const hasClients = await page.locator('tbody tr').count() > 0;
    if (!hasClients) {
      await page.goto('/clients.php?action=new');
      await page.fill('#name', 'Comprehensive Quote Client');
      await page.fill('#email', 'comprehensive@test.com');
      await page.click('button:has-text("Save Client")');
    }

    // Create a new quote
    await page.goto('/quoteBuilder.php');

    // Select client
    const clientSelect = page.locator('select[name="client_id"]');
    await clientSelect.selectOption({ index: 1 });

    // Add 2 labour charges
    await page.fill('input[name="labour_cutting"]', '45');  // 45 minutes cutting
    await page.fill('input[name="labour_sewing"]', '90');   // 90 minutes sewing

    // Create the quote first
    await page.click('button:has-text("Create Quote")');

    // Wait for redirect to saved quote
    await page.waitForURL('**/quoteBuilder.php?id=*', { timeout: 10000 });

    // Now add 5 materials using the search
    const materialSearch = page.locator('#materialSearch');
    await expect(materialSearch).toBeVisible();

    // Search and add materials one by one
    const searchTerms = ['Torch', 'Head', 'Concept', 'Torch', 'Head'];

    for (let i = 0; i < 5; i++) {
      // Type search term
      await materialSearch.fill(searchTerms[i]);

      // Wait for search results to appear
      await page.waitForSelector('.search-results.active .search-result-item', { timeout: 5000 });

      // Click the first result
      await page.locator('.search-result-item').first().click();

      // Wait for page reload after adding material
      await page.waitForLoadState('networkidle');

      // Clear search for next iteration
      await materialSearch.fill('');
    }

    // Verify we have 5 materials in the table
    const materialRows = page.locator('#materialsBody tr:not(.no-materials)');
    await expect(materialRows).toHaveCount(5);

    // Verify labour totals are displayed
    const labourTotal = await page.locator('#labourTotal').textContent();
    expect(labourTotal).not.toBe('$0.00');

    // Verify materials subtotal is not zero
    const materialsSubtotal = await page.locator('#materialsSubtotal').textContent();
    expect(materialsSubtotal).not.toBe('$0.00');

    // Verify grand total is displayed
    const grandTotal = await page.locator('#grandTotal').textContent();
    expect(grandTotal).not.toBe('$0.00');

    // Log the final URL and totals
    console.log('Quote URL:', page.url());
    console.log('Labour Total:', labourTotal);
    console.log('Materials Subtotal:', materialsSubtotal);
    console.log('Grand Total:', grandTotal);
  });
});

test.describe('Quote Status Workflow', () => {
  test('should show appropriate action buttons for draft quote', async ({ authenticatedPage: page }) => {
    // Ensure client exists
    await page.goto('/clients.php');
    const hasClients = await page.locator('tbody tr').count() > 0;
    if (!hasClients) {
      await page.goto('/clients.php?action=new');
      await page.fill('#name', 'Test Quote Client');
      await page.fill('#email', 'quote-test@test.com');
      await page.click('button:has-text("Save Client")');
    }

    // Create a draft quote first
    await page.goto('/quoteBuilder.php');

    const clientSelect = page.locator('select[name="client_id"]');
    await clientSelect.selectOption({ index: 1 });
    await page.fill('input[name="labour_sewing"]', '60');
    await page.click('button:has-text("Create Quote")');

    await page.waitForURL('**/quoteBuilder.php?id=*');

    // Go to quotes list
    await page.goto('/quotes.php');

    // Find the latest quote row
    const quoteRow = page.locator('tbody tr').first();

    // Draft quotes should have Edit/View and Send buttons
    await expect(quoteRow.locator('a:has-text("Edit"), a:has-text("View")')).toBeVisible();
    await expect(quoteRow.locator('button:has-text("Send")')).toBeVisible();
  });
});
