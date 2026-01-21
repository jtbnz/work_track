<?php
/**
 * Database reset script for Playwright E2E tests
 *
 * This script resets the database to a clean state with:
 * - Default admin user
 * - Default project statuses
 * - Default misc materials
 * - Quoting settings
 *
 * Usage: php tests/fixtures/resetDb.php
 * Or via HTTP: GET /tests/fixtures/resetDb.php (requires test mode)
 */

// Only allow in test environment
if (php_sapi_name() !== 'cli' && !isset($_GET['test_key'])) {
    http_response_code(403);
    die('Access denied. This endpoint is only for testing.');
}

// Test key verification for HTTP access
if (isset($_GET['test_key']) && $_GET['test_key'] !== 'playwright_test_key_2026') {
    http_response_code(403);
    die('Invalid test key.');
}

require_once dirname(dirname(__DIR__)) . '/includes/db.php';

$db = Database::getInstance();

echo "Resetting database for tests...\n";

// Disable foreign key checks temporarily
$db->query("PRAGMA foreign_keys = OFF");

// Clear all test data (preserve defaults)
$tablesToClear = [
    'audit_log',
    'stock_movements',
    'quote_misc',
    'quote_materials',
    'quotes',
    'invoice_misc',
    'invoice_materials',
    'invoices',
    'materials',
    'suppliers',
    'projects',
    'clients',
    'document_sequences'
];

foreach ($tablesToClear as $table) {
    try {
        $db->query("DELETE FROM $table");
        echo "Cleared table: $table\n";
    } catch (Exception $e) {
        echo "Warning: Could not clear table $table: " . $e->getMessage() . "\n";
    }
}

// Reset sequences
$db->query("DELETE FROM sqlite_sequence WHERE name IN ('" . implode("','", $tablesToClear) . "')");

// Re-enable foreign key checks
$db->query("PRAGMA foreign_keys = ON");

// Ensure default admin user exists
$adminExists = $db->fetchOne("SELECT id FROM users WHERE username = 'admin'");
if (!$adminExists) {
    $db->insert('users', [
        'username' => 'admin',
        'password' => password_hash('admin', PASSWORD_DEFAULT),
        'email' => 'admin@worktrack.local',
        'role' => 'admin',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    echo "Created default admin user\n";
}

// Ensure default project statuses exist
$defaultStatuses = [
    ['name' => 'Quote Required', 'color' => '#6c757d', 'sort_order' => 1],
    ['name' => 'Pending', 'color' => '#ffc107', 'sort_order' => 2],
    ['name' => 'In Progress', 'color' => '#007bff', 'sort_order' => 3],
    ['name' => 'Completed', 'color' => '#28a745', 'sort_order' => 4],
    ['name' => 'On Hold', 'color' => '#fd7e14', 'sort_order' => 5],
    ['name' => 'Cancelled', 'color' => '#dc3545', 'sort_order' => 6]
];

foreach ($defaultStatuses as $status) {
    $exists = $db->fetchOne("SELECT id FROM project_statuses WHERE name = :name", ['name' => $status['name']]);
    if (!$exists) {
        $db->insert('project_statuses', $status);
        echo "Created status: {$status['name']}\n";
    }
}

// Ensure default misc materials exist
$defaultMiscMaterials = [
    ['name' => 'Travel (Local)', 'fixed_price' => 25.00, 'description' => 'Local travel charge', 'is_active' => 1],
    ['name' => 'Travel (Regional)', 'fixed_price' => 75.00, 'description' => 'Regional travel charge', 'is_active' => 1],
    ['name' => 'Disposal Fee', 'fixed_price' => 35.00, 'description' => 'Waste disposal fee', 'is_active' => 1],
    ['name' => 'Rush Job Premium', 'fixed_price' => 100.00, 'description' => 'Premium for expedited work', 'is_active' => 1],
    ['name' => 'After Hours Service', 'fixed_price' => 50.00, 'description' => 'After hours service charge', 'is_active' => 1]
];

// Clear and re-insert misc materials
$db->query("DELETE FROM misc_materials");
foreach ($defaultMiscMaterials as $misc) {
    $misc['created_at'] = date('Y-m-d H:i:s');
    $misc['updated_at'] = date('Y-m-d H:i:s');
    $db->insert('misc_materials', $misc);
    echo "Created misc material: {$misc['name']}\n";
}

// Ensure quoting settings exist
$defaultSettings = [
    ['setting_key' => 'labour_rate_standard', 'setting_value' => '75', 'setting_type' => 'number', 'description' => 'Standard hourly labour rate'],
    ['setting_key' => 'labour_rate_premium', 'setting_value' => '95', 'setting_type' => 'number', 'description' => 'Premium hourly labour rate'],
    ['setting_key' => 'gst_rate', 'setting_value' => '15', 'setting_type' => 'number', 'description' => 'GST percentage'],
    ['setting_key' => 'quote_validity_days', 'setting_value' => '30', 'setting_type' => 'number', 'description' => 'Default quote validity period'],
    ['setting_key' => 'invoice_payment_terms', 'setting_value' => '14', 'setting_type' => 'number', 'description' => 'Default invoice payment terms in days'],
    ['setting_key' => 'company_name', 'setting_value' => 'WorkTrack Test Company', 'setting_type' => 'text', 'description' => 'Company name for documents'],
    ['setting_key' => 'company_address', 'setting_value' => '123 Test Street, TestCity 1234', 'setting_type' => 'text', 'description' => 'Company address'],
    ['setting_key' => 'company_phone', 'setting_value' => '0800 TEST', 'setting_type' => 'text', 'description' => 'Company phone number'],
    ['setting_key' => 'company_email', 'setting_value' => 'test@worktrack.local', 'setting_type' => 'text', 'description' => 'Company email address']
];

foreach ($defaultSettings as $setting) {
    $exists = $db->fetchOne("SELECT id FROM settings WHERE setting_key = :key", ['key' => $setting['setting_key']]);
    if (!$exists) {
        $db->insert('settings', $setting);
        echo "Created setting: {$setting['setting_key']}\n";
    }
}

echo "\nDatabase reset complete!\n";

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Database reset complete']);
}
