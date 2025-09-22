<?php
require_once dirname(__DIR__) . '/includes/db.php';

function initializeDatabase() {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $conn->exec($schema);

    // Insert default admin user (password: admin)
    $adminPassword = password_hash('admin', PASSWORD_DEFAULT);
    $db->insert('users', [
        'username' => 'admin',
        'password_hash' => $adminPassword,
        'email' => 'admin@worktrack.local'
    ]);

    // Insert default project statuses
    $statuses = [
        ['name' => 'Quote Required', 'color' => '#ffc107', 'sort_order' => 1],
        ['name' => 'Pending', 'color' => '#6c757d', 'sort_order' => 2],
        ['name' => 'In Progress', 'color' => '#007bff', 'sort_order' => 3],
        ['name' => 'Completed', 'color' => '#28a745', 'sort_order' => 4],
        ['name' => 'On Hold', 'color' => '#fd7e14', 'sort_order' => 5],
        ['name' => 'Cancelled', 'color' => '#dc3545', 'sort_order' => 6]
    ];

    foreach ($statuses as $status) {
        $db->insert('project_statuses', $status);
    }

    // Insert default project template
    $db->insert('project_templates', [
        'name' => 'Default Template',
        'default_title' => 'New Project',
        'default_details' => 'Project details here...',
        'default_fabric' => '',
        'is_default' => 1
    ]);

    // Insert sample data
    $clientId = $db->insert('clients', [
        'name' => 'Sample Client',
        'address' => '123 Main Street',
        'phone' => '555-0123',
        'email' => 'client@example.com',
        'remarks' => 'Sample client for testing',
        'created_by' => 1,
        'updated_by' => 1
    ]);

    $db->insert('projects', [
        'title' => 'Sample Project',
        'details' => 'This is a sample project for demonstration.',
        'client_id' => $clientId,
        'start_date' => date('Y-m-d'),
        'completion_date' => date('Y-m-d', strtotime('+7 days')),
        'status_id' => 3, // In Progress
        'fabric' => 'Cotton',
        'created_by' => 1,
        'updated_by' => 1,
        'template_id' => 1
    ]);

    echo "Database initialized successfully!\n";
    echo "Default admin user created:\n";
    echo "  Username: admin\n";
    echo "  Password: admin\n";
}

// Run initialization if called directly
if (php_sapi_name() === 'cli' && basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    initializeDatabase();
}
?>