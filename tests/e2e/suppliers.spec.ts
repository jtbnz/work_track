import { test, expect } from '../fixtures/auth';
import { generateSupplier } from '../fixtures/testData';

test.describe('Suppliers', () => {
  test('should display suppliers list page', async ({ authenticatedPage: page }) => {
    await page.goto('/suppliers.php');

    await expect(page.locator('.page-title')).toContainText('Suppliers');
    await expect(page.locator('table')).toBeVisible();
  });

  test('should show empty state when no suppliers exist', async ({ authenticatedPage: page }) => {
    await page.goto('/suppliers.php');

    // Look for empty state message or table with no data rows
    const tableBody = page.locator('tbody');
    const rows = await tableBody.locator('tr').count();

    // If no rows, check for empty message
    if (rows === 0) {
      await expect(page.locator('text=No suppliers')).toBeVisible();
    }
  });

  test('should create a new supplier', async ({ authenticatedPage: page }) => {
    const supplier = generateSupplier();

    await page.goto('/suppliers.php?action=new');

    // Should show the supplier form (POST form with supplier fields)
    await expect(page.locator('#name')).toBeVisible();

    // Fill in form - use actual field names from the page
    await page.fill('#name', supplier.name);
    await page.fill('#contactName', supplier.contactName);
    await page.fill('#email', supplier.email);
    await page.fill('#phone', supplier.phone);
    await page.fill('#address', supplier.address);
    await page.fill('#notes', supplier.notes);

    // Submit form
    await page.click('button:has-text("Save Supplier")');

    // Verify success
    await expect(page.locator('.alert-success')).toBeVisible();

    // Verify supplier appears in list
    await page.goto('/suppliers.php');
    await expect(page.locator('table')).toContainText(supplier.name);
  });

  test('should edit an existing supplier', async ({ authenticatedPage: page }) => {
    // First create a supplier
    const supplier = generateSupplier();

    await page.goto('/suppliers.php?action=new');

    await page.fill('#name', supplier.name);
    await page.fill('#email', supplier.email);
    await page.click('button:has-text("Save Supplier")');

    // Now edit it - find Edit link for this supplier
    await page.goto('/suppliers.php');
    const editLink = page.locator(`tr:has-text("${supplier.name}") a:has-text("Edit")`);
    await editLink.click();

    // Update the name
    const updatedName = supplier.name + ' Updated';
    await page.fill('#name', updatedName);
    await page.click('button:has-text("Save Supplier")');

    // Verify update
    await expect(page.locator('.alert-success')).toBeVisible();

    await page.goto('/suppliers.php');
    await expect(page.locator('table')).toContainText(updatedName);
  });

  test('should delete a supplier without materials', async ({ authenticatedPage: page }) => {
    // Create a supplier to delete
    const supplier = generateSupplier({ name: 'To Be Deleted Supplier ' + Date.now() });

    await page.goto('/suppliers.php?action=new');

    await page.fill('#name', supplier.name);
    await page.fill('#email', supplier.email);
    await page.click('button:has-text("Save Supplier")');

    // Verify it was created
    await expect(page.locator('.alert-success')).toBeVisible();

    // Go back to list and delete
    await page.goto('/suppliers.php');

    // Handle confirmation dialog
    page.on('dialog', dialog => dialog.accept());

    // Find and click the delete button for this supplier
    const deleteButton = page.locator(`tr:has-text("${supplier.name}") button:has-text("Delete")`);
    await expect(deleteButton).toBeVisible();
    await deleteButton.click();

    // Verify deleted (success or warning message)
    await expect(page.locator('.alert-success, .alert-warning')).toBeVisible();

    // Verify not in list
    await page.goto('/suppliers.php');
    await expect(page.locator('table')).not.toContainText(supplier.name);
  });

  test('should search and filter suppliers', async ({ authenticatedPage: page }) => {
    // Create suppliers with unique names
    const uniqueId = Date.now();
    const supplier1 = generateSupplier({ name: `SearchTest Alpha ${uniqueId}` });
    const supplier2 = generateSupplier({ name: `SearchTest Beta ${uniqueId}` });

    // Create first supplier
    await page.goto('/suppliers.php?action=new');
    await page.fill('#name', supplier1.name);
    await page.fill('#email', supplier1.email);
    await page.click('button:has-text("Save Supplier")');

    // Create second supplier
    await page.goto('/suppliers.php?action=new');
    await page.fill('#name', supplier2.name);
    await page.fill('#email', supplier2.email);
    await page.click('button:has-text("Save Supplier")');

    // Search for "Alpha"
    await page.goto('/suppliers.php');
    await page.fill('input[name="search"]', `Alpha ${uniqueId}`);
    await page.click('button:has-text("Search")');

    // Should show Alpha supplier
    await expect(page.locator('table')).toContainText(supplier1.name);
  });
});
