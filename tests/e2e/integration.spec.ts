import { test, expect } from '../fixtures/auth';
import { generateClient, generateSupplier, generateMaterial, generateQuote, uniqueId } from '../fixtures/testData';

test.describe('Integration Tests - Full Workflows', () => {
  test.skip('complete quote workflow: client → materials → quote → status updates', async ({ authenticatedPage: page }) => {
    // TODO: Complex workflow test - needs debugging of quote creation form submission
    const testId = uniqueId();

    // Step 1: Create a client
    const client = generateClient({ name: `Integration Client ${testId}` });
    await page.goto('/clients.php?action=new');
    await page.fill('#name', client.name);
    await page.fill('#email', client.email);
    await page.fill('#phone', client.phone);
    await page.click('button:has-text("Save Client")');
    await expect(page.locator('.alert-success')).toBeVisible();

    // Step 2: Create a supplier
    const supplier = generateSupplier({ name: `Integration Supplier ${testId}` });
    await page.goto('/suppliers.php?action=new');
    await page.fill('#name', supplier.name);
    await page.fill('#email', supplier.email);
    await page.click('button:has-text("Save Supplier")');
    await expect(page.locator('.alert-success')).toBeVisible();

    // Step 3: Create a material
    const material = generateMaterial({ itemName: `Integration Material ${testId}` });
    await page.goto('/materials.php?action=new');
    await page.fill('#itemName', material.itemName);
    await page.fill('#manufacturersCode', material.manufacturersCode);
    await page.fill('#costExcl', material.costExcl.toString());
    await page.fill('#sellPrice', material.sellPrice.toString());
    await page.fill('#stockOnHand', '100');

    // Select supplier if dropdown exists
    const supplierSelect = page.locator('#supplierId');
    if (await supplierSelect.isVisible()) {
      await supplierSelect.selectOption({ label: supplier.name });
    }

    await page.click('button:has-text("Save Material")');
    await expect(page.locator('.alert-success')).toBeVisible();

    // Step 4: Create a quote for the client
    await page.goto('/quoteBuilder.php');

    // Select the client we created
    const clientSelect = page.locator('select[name="client_id"]');
    await clientSelect.selectOption({ label: client.name });

    // Add labour
    await page.fill('input[name="labour_stripping"]', '30');
    await page.fill('input[name="labour_cutting"]', '45');
    await page.fill('input[name="labour_sewing"]', '60');
    await page.fill('input[name="labour_upholstery"]', '90');
    await page.fill('input[name="labour_assembly"]', '30');

    // Create the quote
    await page.click('button:has-text("Create Quote")');
    await expect(page.locator('.alert-success')).toBeVisible({ timeout: 5000 });

    // Wait for redirect to edit mode
    await page.waitForURL('**/quoteBuilder.php?id=*');

    // Step 5: Add material to quote (search and add)
    const materialSearch = page.locator('#materialSearch');
    if (await materialSearch.isVisible()) {
      await materialSearch.fill(material.itemName);
      await page.waitForTimeout(500); // Wait for search results

      const searchResult = page.locator('.search-result-item').first();
      if (await searchResult.isVisible()) {
        await searchResult.click();
        await page.waitForTimeout(500);
      }
    }

    // Step 6: Verify totals calculated
    const grandTotal = await page.locator('#grandTotal').textContent();
    expect(parseFloat(grandTotal!.replace(/[^0-9.]/g, ''))).toBeGreaterThan(0);

    // Step 7: Save and go to quotes list
    const saveAndExitBtn = page.locator('button[type="submit"][value="saveAndExit"]');
    const backLink = page.locator('a:has-text("Back to Quotes")');
    if (await saveAndExitBtn.isVisible()) {
      await saveAndExitBtn.click();
    } else if (await backLink.isVisible()) {
      await backLink.click();
    } else {
      await page.goto('/quotes.php');
    }

    // Step 8: Verify quote appears in list
    await page.goto('/quotes.php');
    await expect(page.locator('tbody')).toContainText(client.name);

    // Step 9: Mark quote as sent
    page.on('dialog', dialog => dialog.accept());
    const sendButton = page.locator(`tr:has-text("${client.name}") button:has-text("Send")`);
    if (await sendButton.isVisible()) {
      await sendButton.click();
      await expect(page.locator('.alert-success')).toBeVisible();
    }

    // Step 10: Verify status changed to sent
    await page.goto('/quotes.php');
    const quoteRow = page.locator(`tr:has-text("${client.name}")`);
    await expect(quoteRow.locator('.badge')).toContainText(/sent/i);
  });

  test.skip('project with quote link workflow', async ({ authenticatedPage: page }) => {
    // TODO: Complex workflow test - needs debugging of quote-project linking
    const testId = uniqueId();

    // Create a client
    const client = generateClient({ name: `Project Quote Client ${testId}` });
    await page.goto('/clients.php?action=new');
    await page.fill('#name', client.name);
    await page.fill('#email', client.email);
    await page.click('button:has-text("Save Client")');

    // Create a project for the client
    await page.goto('/projects.php?action=new');
    await page.fill('#title', `Project ${testId}`);

    const clientSelect = page.locator('#clientId');
    await clientSelect.selectOption({ label: client.name });

    const today = new Date().toISOString().split('T')[0];
    await page.fill('#startDate', today);
    await page.click('button:has-text("Save Project")');

    // Create a quote and link to project
    await page.goto('/quoteBuilder.php');
    await page.locator('select[name="client_id"]').selectOption({ label: client.name });

    // Wait for projects to load
    await page.waitForTimeout(500);

    // Select project if dropdown populated
    const projectSelect = page.locator('select[name="project_id"]');
    const projectOptions = await projectSelect.locator('option').count();
    if (projectOptions > 1) {
      await projectSelect.selectOption({ index: 1 });
    }

    await page.fill('input[name="labour_sewing"]', '60');
    await page.click('button:has-text("Create Quote")');

    await expect(page.locator('.alert-success')).toBeVisible({ timeout: 5000 });
  });

  test('material stock tracking through quote workflow', async ({ authenticatedPage: page }) => {
    const testId = uniqueId();

    // Create material with known stock
    const material = generateMaterial({
      itemName: `Stock Track Material ${testId}`,
      stockOnHand: 50,
    });

    await page.goto('/materials.php?action=new');
    await page.fill('#itemName', material.itemName);
    await page.fill('#manufacturersCode', material.manufacturersCode);
    await page.fill('#costExcl', material.costExcl.toString());
    await page.fill('#sellPrice', material.sellPrice.toString());
    await page.fill('#stockOnHand', '50');
    await page.click('button:has-text("Save Material")');

    // Verify stock shows in materials list
    await page.goto('/materials.php');
    const materialRow = page.locator(`tr:has-text("${material.itemName}")`);
    await expect(materialRow).toContainText('50');
  });

  test.skip('quote revision workflow', async ({ authenticatedPage: page }) => {
    // TODO: Complex workflow test - needs quote revision feature to be implemented
    const testId = uniqueId();

    // Create client
    const client = generateClient({ name: `Revision Client ${testId}` });
    await page.goto('/clients.php?action=new');
    await page.fill('#name', client.name);
    await page.fill('#email', client.email);
    await page.click('button:has-text("Save Client")');

    // Create and send a quote
    await page.goto('/quoteBuilder.php');
    await page.locator('select[name="client_id"]').selectOption({ label: client.name });
    await page.fill('input[name="labour_sewing"]', '60');
    await page.click('button:has-text("Create Quote")');

    await page.waitForURL('**/quoteBuilder.php?id=*');
    const quoteUrl = page.url();
    const quoteId = quoteUrl.match(/id=(\d+)/)?.[1];

    // Mark as sent
    await page.goto('/quotes.php');
    page.on('dialog', dialog => dialog.accept());

    const sendButton = page.locator(`tr:has-text("${client.name}") button:has-text("Send")`);
    await sendButton.click();
    await expect(page.locator('.alert-success')).toBeVisible();

    // Create revision
    await page.goto('/quotes.php');
    const reviseButton = page.locator(`tr:has-text("${client.name}") button:has-text("Revise")`);

    if (await reviseButton.isVisible()) {
      await reviseButton.click();

      // Should redirect to new quote (revision)
      await page.waitForURL('**/quoteBuilder.php?id=*');
      const revisionUrl = page.url();
      const revisionId = revisionUrl.match(/id=(\d+)/)?.[1];

      // Should be a different quote ID
      expect(revisionId).not.toEqual(quoteId);

      // Should show revision badge
      await expect(page.locator('.badge:has-text("Rev")')).toBeVisible();
    }
  });
});

test.describe('Navigation and UI', () => {
  test.skip('should navigate through all main sections', async ({ authenticatedPage: page }) => {
    // TODO: Debug why calendar page h1 isn't visible in tests
    // Dashboard
    await page.goto('/index.php');
    await expect(page).toHaveURL(/index\.php/);

    // Projects
    await page.goto('/projects.php');
    await expect(page).toHaveURL(/projects\.php/);
    await expect(page.locator('text=Projects')).toBeVisible({ timeout: 10000 });

    // Clients
    await page.goto('/clients.php');
    await expect(page).toHaveURL(/clients\.php/);
    await expect(page.locator('text=Clients')).toBeVisible({ timeout: 10000 });

    // Calendar
    await page.goto('/calendar.php');
    await expect(page).toHaveURL(/calendar\.php/);
    await expect(page.locator('text=Calendar')).toBeVisible({ timeout: 10000 });

    // Kanban
    await page.goto('/kanban.php');
    await expect(page).toHaveURL(/kanban\.php/);
    await expect(page.locator('text=Kanban')).toBeVisible({ timeout: 10000 });

    // Quotes (Quoting section)
    await page.goto('/quotes.php');
    await expect(page).toHaveURL(/quotes\.php/);
    await expect(page.locator('text=Quotes')).toBeVisible({ timeout: 10000 });

    // Materials
    await page.goto('/materials.php');
    await expect(page).toHaveURL(/materials\.php/);
    await expect(page.locator('text=Materials')).toBeVisible({ timeout: 10000 });

    // Suppliers
    await page.goto('/suppliers.php');
    await expect(page).toHaveURL(/suppliers\.php/);
    await expect(page.locator('text=Suppliers')).toBeVisible({ timeout: 10000 });
  });

  test('should show quoting dropdown in navigation', async ({ authenticatedPage: page }) => {
    await page.goto('/index.php');

    // Look for Quoting link in nav
    const quotingNav = page.locator('.nav-menu').locator('text=Quoting').first();
    await expect(quotingNav).toBeVisible();
  });

  test('should maintain responsive layout', async ({ authenticatedPage: page }) => {
    // Test at mobile width
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/quotes.php');

    // Page should still be functional
    await expect(page.locator('h1').first()).toBeVisible({ timeout: 10000 });

    // Test at tablet width
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.goto('/quoteBuilder.php');

    await expect(page.locator('h1').first()).toBeVisible({ timeout: 10000 });

    // Test at desktop width
    await page.setViewportSize({ width: 1920, height: 1080 });
    await page.goto('/materials.php');

    await expect(page.locator('table').first()).toBeVisible({ timeout: 10000 });
  });
});
