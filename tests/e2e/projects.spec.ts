import { test, expect } from '../fixtures/auth';
import { generateProject, generateClient } from '../fixtures/testData';

test.describe('Projects', () => {
  test.beforeEach(async ({ authenticatedPage: page }) => {
    // Ensure a client exists for projects
    await page.goto('/clients.php');

    const hasClients = await page.locator('tbody tr:not(:has-text("No clients"))').count() > 0;

    if (!hasClients) {
      await page.goto('/clients.php?action=new');
      await page.fill('#name', 'Test Project Client');
      await page.fill('#email', 'project-test@test.com');
      await page.click('button:has-text("Save Client")');
    }
  });

  test('should display projects list page', async ({ authenticatedPage: page }) => {
    await page.goto('/projects.php');

    await expect(page.locator('.page-title')).toContainText('Projects');
    await expect(page.locator('table')).toBeVisible();
  });

  test('should show empty state when no projects exist', async ({ authenticatedPage: page }) => {
    await page.goto('/projects.php');

    const tableBody = page.locator('tbody');
    const rows = await tableBody.locator('tr').count();

    if (rows === 1) {
      await expect(tableBody).toContainText(/no projects|Create your first/i);
    }
  });

  test('should create a new project', async ({ authenticatedPage: page }) => {
    const project = generateProject();

    await page.goto('/projects.php?action=new');

    // Fill in form
    await page.fill('#title', project.title);

    const detailsField = page.locator('#details');
    if (await detailsField.isVisible()) {
      await detailsField.fill(project.details);
    }

    // Select client
    const clientSelect = page.locator('#client_id');
    if (await clientSelect.isVisible()) {
      await clientSelect.selectOption({ index: 1 }); // First available client
    }

    // Set dates
    await page.fill('#start_date', project.startDate);
    await page.fill('#completion_date', project.completionDate);

    // Set fabric if field exists
    const fabricField = page.locator('#fabric');
    if (await fabricField.isVisible()) {
      await fabricField.fill(project.fabric);
    }

    // Submit form
    await page.click('button:has-text("Save Project")');

    // Verify success
    await expect(page.locator('.alert-success')).toBeVisible();

    // Verify project appears in list
    await page.goto('/projects.php');
    await expect(page.locator('table')).toContainText(project.title);
  });

  test('should edit an existing project', async ({ authenticatedPage: page }) => {
    const project = generateProject();

    // Create project
    await page.goto('/projects.php?action=new');

    await page.fill('#title', project.title);
    const clientSelect = page.locator('#client_id');
    if (await clientSelect.isVisible()) {
      await clientSelect.selectOption({ index: 1 });
    }
    await page.fill('#start_date', project.startDate);
    await page.click('button:has-text("Save Project")');

    // Edit project
    await page.goto('/projects.php');
    const editButton = page.locator(`tr:has-text("${project.title}") a:has-text("Edit"), tr:has-text("${project.title}") button:has-text("Edit")`);
    await editButton.click();

    const updatedTitle = project.title + ' Updated';
    await page.fill('#title', updatedTitle);
    await page.click('button:has-text("Save Project")');

    // Verify update
    await expect(page.locator('.alert-success')).toBeVisible();

    await page.goto('/projects.php');
    await expect(page.locator('table')).toContainText(updatedTitle);
  });

  test('should delete a project', async ({ authenticatedPage: page }) => {
    const project = generateProject({ title: 'To Be Deleted Project' });

    // Create project
    await page.goto('/projects.php?action=new');

    await page.fill('#title', project.title);
    const clientSelect = page.locator('#client_id');
    if (await clientSelect.isVisible()) {
      await clientSelect.selectOption({ index: 1 });
    }
    await page.fill('#start_date', project.startDate);
    await page.click('button:has-text("Save Project")');

    // Delete project
    await page.goto('/projects.php');

    page.on('dialog', dialog => dialog.accept());

    const deleteButton = page.locator(`tr:has-text("${project.title}") button:has-text("Delete")`);
    await deleteButton.click();

    // Verify deleted
    await expect(page.locator('.alert-success, .alert-warning')).toBeVisible();

    await page.goto('/projects.php');
    await expect(page.locator('table')).not.toContainText(project.title);
  });

  test('should filter projects by status', async ({ authenticatedPage: page }) => {
    await page.goto('/projects.php');

    const statusFilter = page.locator('select[name="status"], select[name="status_id"]');

    if (await statusFilter.isVisible()) {
      // Get all option values except the first (which is usually "All" or empty)
      const options = await statusFilter.locator('option').all();

      if (options.length > 1) {
        // Select second option by index (first real status)
        await statusFilter.selectOption({ index: 1 });
        await page.click('button:has-text("Filter")');

        // URL should contain filter parameter
        await expect(page.url()).toMatch(/status/i);
      }
    }
  });

  test('should filter projects by client', async ({ authenticatedPage: page }) => {
    await page.goto('/projects.php');

    const clientFilter = page.locator('select[name="client"], select[name="client_id"]');

    if (await clientFilter.isVisible()) {
      const options = await clientFilter.locator('option').count();

      if (options > 1) {
        await clientFilter.selectOption({ index: 1 });
        await page.click('button:has-text("Filter")');

        await expect(page.url()).toMatch(/client/i);
      }
    }
  });

  test('should search projects', async ({ authenticatedPage: page }) => {
    const project = generateProject({ title: 'UniqueSearchProject Test' });

    // Create project
    await page.goto('/projects.php?action=new');

    await page.fill('#title', project.title);
    const clientSelect = page.locator('#client_id');
    if (await clientSelect.isVisible()) {
      await clientSelect.selectOption({ index: 1 });
    }
    await page.fill('#start_date', project.startDate);
    await page.click('button:has-text("Save Project")');

    // Search for project
    await page.goto('/projects.php');
    const searchInput = page.locator('input[name="search"], input[type="search"]');

    if (await searchInput.isVisible()) {
      await searchInput.fill('UniqueSearchProject');
      await page.click('button:has-text("Search"), button:has-text("Filter")');

      await expect(page.locator('table')).toContainText(project.title);
    }
  });

  test('should display status with color', async ({ authenticatedPage: page }) => {
    await page.goto('/projects.php');

    // Projects should show colored status badges
    const statusBadges = page.locator('.badge, .status-badge, [class*="status"]');
    const badgeCount = await statusBadges.count();

    if (badgeCount > 0) {
      // Verify badges have background color style
      const firstBadge = statusBadges.first();
      const style = await firstBadge.getAttribute('style');
      // Status badges typically have inline background-color
      expect(style).toMatch(/background|color/i);
    }
  });
});
