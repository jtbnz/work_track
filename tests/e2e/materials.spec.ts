import { test, expect } from '../fixtures/auth';
import { generateMaterial } from '../fixtures/testData';

test.describe('Materials', () => {
  test('should display materials list page', async ({ authenticatedPage: page }) => {
    await page.goto('/materials.php');

    await expect(page.locator('.page-title')).toContainText('Materials');
    await expect(page.locator('table')).toBeVisible();
  });

  test('should show empty state when no materials exist', async ({ authenticatedPage: page }) => {
    await page.goto('/materials.php');

    const tableBody = page.locator('tbody');
    const rows = await tableBody.locator('tr').count();

    if (rows === 0) {
      await expect(page.locator('text=No materials')).toBeVisible();
    }
  });

  test('should create a new material manually', async ({ authenticatedPage: page }) => {
    const material = generateMaterial();

    await page.goto('/materials.php?action=new');

    // Fill in form - use actual field IDs from the page
    await page.fill('#itemName', material.itemName);
    await page.fill('#manufacturersCode', material.manufacturersCode);
    await page.fill('#costExcl', material.costExcl.toString());
    await page.fill('#sellPrice', material.sellPrice.toString());
    await page.fill('#stockOnHand', material.stockOnHand.toString());
    await page.fill('#reorderQuantity', material.reorderQuantity.toString());
    await page.fill('#comments', material.comments);

    // Submit form
    await page.click('button:has-text("Save Material")');

    // Verify success
    await expect(page.locator('.alert-success')).toBeVisible();

    // Verify material appears in list
    await page.goto('/materials.php');
    await expect(page.locator('table')).toContainText(material.itemName);
  });

  test('should edit an existing material', async ({ authenticatedPage: page }) => {
    // First create a material
    const material = generateMaterial();

    await page.goto('/materials.php?action=new');

    await page.fill('#itemName', material.itemName);
    await page.fill('#manufacturersCode', material.manufacturersCode);
    await page.fill('#costExcl', material.costExcl.toString());
    await page.fill('#sellPrice', material.sellPrice.toString());
    await page.click('button:has-text("Save Material")');

    // Now edit it
    await page.goto('/materials.php');
    const editLink = page.locator(`tr:has-text("${material.itemName}") a:has-text("Edit")`);
    await editLink.click();

    // Update the name
    const updatedName = material.itemName + ' Updated';
    await page.fill('#itemName', updatedName);
    await page.click('button:has-text("Save Material")');

    // Verify update
    await expect(page.locator('.alert-success')).toBeVisible();

    await page.goto('/materials.php');
    await expect(page.locator('table')).toContainText(updatedName);
  });

  test('should delete a material not used in quotes', async ({ authenticatedPage: page }) => {
    // Create a material to delete
    const material = generateMaterial({ itemName: 'To Be Deleted Material ' + Date.now() });

    await page.goto('/materials.php?action=new');

    await page.fill('#itemName', material.itemName);
    await page.fill('#manufacturersCode', material.manufacturersCode);
    await page.fill('#costExcl', material.costExcl.toString());
    await page.fill('#sellPrice', material.sellPrice.toString());
    await page.click('button:has-text("Save Material")');

    // Verify it was created
    await expect(page.locator('.alert-success')).toBeVisible();

    // Go back to list and delete
    await page.goto('/materials.php');

    // Handle confirmation dialog
    page.on('dialog', dialog => dialog.accept());

    const deleteButton = page.locator(`tr:has-text("${material.itemName}") button:has-text("Delete")`);
    await expect(deleteButton).toBeVisible();
    await deleteButton.click();

    // Verify deleted
    await expect(page.locator('.alert-success, .alert-warning')).toBeVisible();

    // Verify not in list
    await page.goto('/materials.php');
    await expect(page.locator('table')).not.toContainText(material.itemName);
  });

  test('should display stock levels and indicators', async ({ authenticatedPage: page }) => {
    // Create a material with low stock
    const material = generateMaterial({
      itemName: 'Low Stock Material ' + Date.now(),
      stockOnHand: 2,
    });

    await page.goto('/materials.php?action=new');

    await page.fill('#itemName', material.itemName);
    await page.fill('#manufacturersCode', material.manufacturersCode);
    await page.fill('#costExcl', material.costExcl.toString());
    await page.fill('#sellPrice', material.sellPrice.toString());
    await page.fill('#stockOnHand', '2');
    await page.fill('#reorderLevel', '10'); // Set reorder level higher than stock
    await page.click('button:has-text("Save Material")');

    // Check materials list for stock display
    await page.goto('/materials.php');
    const materialRow = page.locator(`tr:has-text("${material.itemName}")`);
    await expect(materialRow).toBeVisible();

    // Should show stock level and Low badge
    await expect(materialRow).toContainText('2');
    await expect(materialRow.locator('.badge-warning')).toBeVisible();
  });

  test('should filter materials by low stock', async ({ authenticatedPage: page }) => {
    await page.goto('/materials.php');

    // Check the low stock filter checkbox
    const lowStockCheckbox = page.locator('input[name="lowStock"]');

    if (await lowStockCheckbox.isVisible()) {
      await lowStockCheckbox.check();
      await page.click('button:has-text("Filter")');

      // URL should contain lowStock parameter
      await expect(page.url()).toContain('lowStock=1');
    }
  });

  test('should search materials by name or code', async ({ authenticatedPage: page }) => {
    // Create materials with unique names
    const uniqueId = Date.now();
    const material1 = generateMaterial({ itemName: `UniqueSearch Fabric A ${uniqueId}`, manufacturersCode: `USFA-${uniqueId}` });
    const material2 = generateMaterial({ itemName: `UniqueSearch Fabric B ${uniqueId}`, manufacturersCode: `USFB-${uniqueId}` });

    // Create first material
    await page.goto('/materials.php?action=new');
    await page.fill('#itemName', material1.itemName);
    await page.fill('#manufacturersCode', material1.manufacturersCode);
    await page.fill('#costExcl', '50');
    await page.fill('#sellPrice', '75');
    await page.click('button:has-text("Save Material")');

    // Create second material
    await page.goto('/materials.php?action=new');
    await page.fill('#itemName', material2.itemName);
    await page.fill('#manufacturersCode', material2.manufacturersCode);
    await page.fill('#costExcl', '60');
    await page.fill('#sellPrice', '85');
    await page.click('button:has-text("Save Material")');

    // Search by code
    await page.goto('/materials.php');
    await page.fill('input[name="search"]', `USFA-${uniqueId}`);
    await page.click('button:has-text("Filter")');

    // Should show first material
    await expect(page.locator('table')).toContainText(material1.itemName);
  });

  test('should show import button and form', async ({ authenticatedPage: page }) => {
    await page.goto('/materials.php');

    // Look for import button
    const importButton = page.locator('a:has-text("Import Excel")');
    await expect(importButton).toBeVisible();

    // Click it
    await importButton.click();

    // Should show import form
    await expect(page.locator('form#importForm, form:has(input[name="excelFile"])')).toBeVisible();
    await expect(page.locator('input[type="file"]')).toBeVisible();
  });
});
