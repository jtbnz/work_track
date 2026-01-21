import { test, expect } from '../fixtures/auth';
import { generateClient } from '../fixtures/testData';

test.describe('Clients', () => {
  test('should display clients list page', async ({ authenticatedPage: page }) => {
    await page.goto('/clients.php');

    await expect(page.locator('.page-title')).toContainText('Clients');
    await expect(page.locator('table')).toBeVisible();
  });

  test('should show empty state when no clients exist', async ({ authenticatedPage: page }) => {
    await page.goto('/clients.php');

    const tableBody = page.locator('tbody');
    const rows = await tableBody.locator('tr').count();

    if (rows === 0) {
      await expect(page.locator('text=No clients')).toBeVisible();
    }
  });

  test('should create a new client', async ({ authenticatedPage: page }) => {
    const client = generateClient();

    await page.goto('/clients.php?action=new');

    // Fill in form - use actual field IDs
    await page.fill('#name', client.name);
    await page.fill('#email', client.email);
    await page.fill('#phone', client.phone);
    await page.fill('#address', client.address);
    await page.fill('#remarks', client.remarks);

    // Submit form
    await page.click('button:has-text("Save Client")');

    // Verify success
    await expect(page.locator('.alert-success')).toBeVisible();

    // Verify client appears in list
    await page.goto('/clients.php');
    await expect(page.locator('table')).toContainText(client.name);
  });

  test('should edit an existing client', async ({ authenticatedPage: page }) => {
    // First create a client
    const client = generateClient();

    await page.goto('/clients.php?action=new');

    await page.fill('#name', client.name);
    await page.fill('#email', client.email);
    await page.click('button:has-text("Save Client")');

    // Now edit it
    await page.goto('/clients.php');
    const editLink = page.locator(`tr:has-text("${client.name}") a:has-text("Edit")`);
    await editLink.click();

    // Update the name
    const updatedName = client.name + ' Updated';
    await page.fill('#name', updatedName);
    await page.click('button:has-text("Save Client")');

    // Verify update
    await expect(page.locator('.alert-success')).toBeVisible();

    await page.goto('/clients.php');
    await expect(page.locator('table')).toContainText(updatedName);
  });

  test('should delete a client without projects', async ({ authenticatedPage: page }) => {
    // Create a client to delete
    const client = generateClient({ name: 'To Be Deleted Client ' + Date.now() });

    await page.goto('/clients.php?action=new');

    await page.fill('#name', client.name);
    await page.fill('#email', client.email);
    await page.click('button:has-text("Save Client")');

    // Verify it was created
    await expect(page.locator('.alert-success')).toBeVisible();

    // Go back to list and delete
    await page.goto('/clients.php');

    // Handle confirmation dialog
    page.on('dialog', dialog => dialog.accept());

    const deleteButton = page.locator(`tr:has-text("${client.name}") button:has-text("Delete")`);
    await expect(deleteButton).toBeVisible();
    await deleteButton.click();

    // Verify deleted
    await expect(page.locator('.alert-success, .alert-warning')).toBeVisible();

    // Verify not in list
    await page.goto('/clients.php');
    await expect(page.locator('table')).not.toContainText(client.name);
  });

  test('should warn when deleting client with projects', async ({ authenticatedPage: page }) => {
    // Create a client
    const client = generateClient({ name: 'Client With Project ' + Date.now() });

    await page.goto('/clients.php?action=new');
    await page.fill('#name', client.name);
    await page.fill('#email', client.email);
    await page.click('button:has-text("Save Client")');

    // Create a project for this client
    await page.goto('/projects.php?action=new');

    await page.fill('#title', 'Test Project For Client');
    const clientSelect = page.locator('select[name="client_id"]');
    await clientSelect.selectOption({ label: client.name });

    // Set a start date
    const today = new Date().toISOString().split('T')[0];
    await page.fill('#start_date', today);

    await page.click('button:has-text("Save Project"), button:has-text("Save")');

    // Try to delete the client
    await page.goto('/clients.php');

    page.on('dialog', dialog => dialog.accept());

    const deleteButton = page.locator(`tr:has-text("${client.name}") button:has-text("Delete")`);
    await deleteButton.click();

    // Should show warning about associated projects
    await expect(page.locator('.alert-warning, .alert-danger')).toBeVisible();
  });

  test('should search clients', async ({ authenticatedPage: page }) => {
    // Create clients with unique names
    const uniqueId = Date.now();
    const client1 = generateClient({ name: `SearchTest Client Alpha ${uniqueId}` });
    const client2 = generateClient({ name: `SearchTest Client Beta ${uniqueId}` });

    // Create first client
    await page.goto('/clients.php?action=new');
    await page.fill('#name', client1.name);
    await page.fill('#email', client1.email);
    await page.click('button:has-text("Save Client")');

    // Create second client
    await page.goto('/clients.php?action=new');
    await page.fill('#name', client2.name);
    await page.fill('#email', client2.email);
    await page.click('button:has-text("Save Client")');

    // Search for "Alpha"
    await page.goto('/clients.php');
    await page.fill('input[name="search"]', `Alpha ${uniqueId}`);
    await page.click('button:has-text("Search")');

    // Should show Alpha client
    await expect(page.locator('table')).toContainText(client1.name);
  });

  test('should display project count for clients', async ({ authenticatedPage: page }) => {
    await page.goto('/clients.php');

    // Table should have a projects column
    const tableHeader = page.locator('thead');
    await expect(tableHeader).toContainText(/Projects/i);
  });
});
