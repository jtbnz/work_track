import { test, expect } from '../fixtures/auth';

test.describe('Quote Creation Workflow', () => {
  test('should add materials BEFORE saving quote', async ({ authenticatedPage: page }) => {
    // Ensure client exists first
    await page.goto('/clients.php');
    const hasClients = await page.locator('tbody tr').count() > 0;
    if (!hasClients) {
      await page.goto('/clients.php?action=new');
      await page.fill('#name', 'Workflow Test Client');
      await page.fill('#email', 'workflow@test.com');
      await page.click('button:has-text("Save Client")');
      await page.waitForLoadState('networkidle');
    }

    // Go to new quote builder
    await page.goto('/quoteBuilder.php');
    console.log('Step 1: On new quote builder page');

    // Verify material search is available for new quotes (no "save first" message)
    await expect(page.locator('#materialSearch')).toBeVisible();
    console.log('Step 2: Material search is available for new quote');

    // Select client
    const clientSelect = page.locator('select[name="client_id"]');
    await clientSelect.selectOption({ index: 1 });

    // Add labour
    await page.fill('input[name="labour_cutting"]', '30');
    await page.fill('input[name="labour_sewing"]', '60');
    console.log('Step 3: Selected client and added labour');

    // Search for and add a material BEFORE saving
    const materialSearch = page.locator('#materialSearch');
    await materialSearch.fill('Torch');

    // Wait for search results
    await page.waitForSelector('.search-results.active .search-result-item', { timeout: 5000 });

    // Click first result to add material (client-side)
    await page.locator('.search-result-item').first().click();
    console.log('Step 4: Added material before saving');

    // Wait for material to appear in table
    await page.waitForTimeout(500);

    // Verify material was added to the table (client-side)
    const materialRows = page.locator('#materialsBody tr:not(.no-materials)');
    const rowCountBeforeSave = await materialRows.count();
    console.log('Step 5: Materials in table before save:', rowCountBeforeSave);
    expect(rowCountBeforeSave).toBe(1);

    // Check materials subtotal is updated (client-side calculation)
    const materialsSubtotalBeforeSave = await page.locator('#materialsSubtotal').textContent();
    console.log('Step 6: Materials subtotal before save:', materialsSubtotalBeforeSave);
    expect(materialsSubtotalBeforeSave).not.toBe('$0.00');

    // Now save the quote
    await page.click('button:has-text("Create Quote")');
    console.log('Step 7: Clicked Create Quote');

    // Wait for redirect
    await page.waitForURL('**/quoteBuilder.php?id=*', { timeout: 10000 });
    const savedUrl = page.url();
    console.log('Step 8: Quote saved, URL:', savedUrl);

    // Verify material is still there after save
    await page.waitForLoadState('networkidle');
    const rowCountAfterSave = await page.locator('#materialsBody tr:not(.no-materials)').count();
    console.log('Step 9: Materials in table after save:', rowCountAfterSave);
    expect(rowCountAfterSave).toBe(1);

    // Check totals are persisted
    const materialsSubtotalAfterSave = await page.locator('#materialsSubtotal').textContent();
    const grandTotal = await page.locator('#grandTotal').textContent();
    console.log('Step 10: Materials subtotal after save:', materialsSubtotalAfterSave);
    console.log('Step 10: Grand total:', grandTotal);
    expect(materialsSubtotalAfterSave).not.toBe('$0.00');
  });

  test('should have misc charges unchecked by default with quantity', async ({ authenticatedPage: page }) => {
    // Ensure client exists first
    await page.goto('/clients.php');
    const hasClients = await page.locator('tbody tr').count() > 0;
    if (!hasClients) {
      await page.goto('/clients.php?action=new');
      await page.fill('#name', 'Misc Test Client');
      await page.fill('#email', 'misc@test.com');
      await page.click('button:has-text("Save Client")');
      await page.waitForLoadState('networkidle');
    }

    // Go to new quote builder
    await page.goto('/quoteBuilder.php');

    // Verify misc checkboxes are present
    const miscCheckboxes = page.locator('.misc-checkbox');
    const checkboxCount = await miscCheckboxes.count();
    console.log('Misc checkbox count:', checkboxCount);
    expect(checkboxCount).toBeGreaterThan(0);

    // Verify misc checkboxes are UNCHECKED by default
    const checkedCheckboxes = page.locator('.misc-checkbox:checked');
    const checkedCount = await checkedCheckboxes.count();
    console.log('Checked misc checkboxes (should be 0):', checkedCount);
    expect(checkedCount).toBe(0);

    // Verify quantity inputs exist
    const qtyInputs = page.locator('.misc-qty');
    const qtyCount = await qtyInputs.count();
    console.log('Misc quantity inputs:', qtyCount);
    expect(qtyCount).toBeGreaterThan(0);

    // Verify misc subtotal is $0.00 (none selected)
    const miscSubtotal = await page.locator('#miscSubtotal').textContent();
    console.log('Misc subtotal (should be $0.00):', miscSubtotal);
    expect(miscSubtotal).toBe('$0.00');

    // Check a misc item and verify total updates
    await miscCheckboxes.first().check();
    await page.waitForTimeout(200);

    const miscSubtotalAfterCheck = await page.locator('#miscSubtotal').textContent();
    console.log('Misc subtotal after checking one:', miscSubtotalAfterCheck);
    expect(miscSubtotalAfterCheck).not.toBe('$0.00');
  });
});
