<?php
require_once dirname(__DIR__) . '/includes/db.php';

/**
 * Run database migrations from the migrations directory.
 * Migrations are tracked in the migrations table to avoid re-running.
 *
 * @return bool True if all migrations succeeded, false otherwise
 */
function runMigrations() {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Create migrations tracking table if not exists
    $conn->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration TEXT NOT NULL UNIQUE,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Get list of executed migrations
    $executed = $db->fetchAll("SELECT migration FROM migrations");
    $executedList = array_column($executed, 'migration');

    // Get migration files
    $migrationPath = __DIR__ . '/migrations/';
    if (!is_dir($migrationPath)) {
        echo "No migrations directory found.\n";
        return true;
    }

    $files = glob($migrationPath . '*.sql');
    if (empty($files)) {
        echo "No migration files found.\n";
        return true;
    }

    sort($files); // Ensure migrations run in order

    $migrationsRun = 0;
    foreach ($files as $file) {
        $migrationName = basename($file);
        if (!in_array($migrationName, $executedList)) {
            $sql = file_get_contents($file);
            try {
                // Execute migration (may contain multiple statements)
                $conn->exec($sql);

                // Record migration as executed
                $db->insert('migrations', ['migration' => $migrationName]);
                echo "  ✓ Executed: $migrationName\n";
                $migrationsRun++;
            } catch (PDOException $e) {
                echo "  ✗ Error in $migrationName: " . $e->getMessage() . "\n";
                return false;
            }
        }
    }

    if ($migrationsRun === 0) {
        echo "All migrations already applied.\n";
    } else {
        echo "$migrationsRun migration(s) executed successfully.\n";
    }

    return true;
}

function initializeDatabase() {
    $dbPath = dirname(__DIR__) . '/database/work_track.db';
    $isNewDatabase = !file_exists($dbPath);

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Only create schema if database is new
    if ($isNewDatabase) {
        try {
            // Read and execute schema
            $schema = file_get_contents(__DIR__ . '/schema.sql');
            $conn->exec($schema);
            echo "Database created and schema initialized successfully.\n";
        } catch (PDOException $e) {
            echo "Error creating database schema: " . $e->getMessage() . "\n";
            return;
        }
    } else {
        echo "Database already exists - checking for updates.\n";
    }

    // Run migrations
    echo "\nRunning migrations...\n";
    if (!runMigrations()) {
        echo "Migration failed - stopping initialization.\n";
        return;
    }

    // Check if admin user already exists
    $existingAdmin = $db->fetchOne("SELECT id FROM users WHERE username = 'admin'");

    if (!$existingAdmin) {
        // Insert default admin user (password: admin)
        $adminPassword = password_hash('admin', PASSWORD_DEFAULT);
        $adminId = $db->insert('users', [
            'username' => 'admin',
            'password_hash' => $adminPassword,
            'email' => 'admin@worktrack.local'
        ]);
        echo "Default admin user created:\n";
        echo "  Username: admin\n";
        echo "  Password: admin\n";
    } else {
        $adminId = $existingAdmin['id'];
        echo "Admin user already exists (ID: $adminId).\n";
    }

    // Check and insert default project statuses
    $existingStatuses = $db->fetchAll("SELECT name FROM project_statuses");
    $existingStatusNames = array_column($existingStatuses, 'name');

    $statuses = [
        ['name' => 'Quote Required', 'color' => '#ffc107', 'sort_order' => 1],
        ['name' => 'Pending', 'color' => '#6c757d', 'sort_order' => 2],
        ['name' => 'In Progress', 'color' => '#007bff', 'sort_order' => 3],
        ['name' => 'Completed', 'color' => '#28a745', 'sort_order' => 4],
        ['name' => 'On Hold', 'color' => '#fd7e14', 'sort_order' => 5],
        ['name' => 'Cancelled', 'color' => '#dc3545', 'sort_order' => 6]
    ];

    $statusesAdded = 0;
    foreach ($statuses as $status) {
        if (!in_array($status['name'], $existingStatusNames)) {
            $db->insert('project_statuses', $status);
            $statusesAdded++;
        }
    }

    if ($statusesAdded > 0) {
        echo "$statusesAdded project status(es) added.\n";
    } else {
        echo "All project statuses already exist.\n";
    }

    // Check and insert default project template
    $existingTemplate = $db->fetchOne("SELECT id FROM project_templates WHERE name = 'Default Template'");

    if (!$existingTemplate) {
        $templateId = $db->insert('project_templates', [
            'name' => 'Default Template',
            'default_title' => 'New Project',
            'default_details' => 'Project details here...',
            'default_fabric' => '',
            'is_default' => 1
        ]);
        echo "Default project template created.\n";
    } else {
        $templateId = $existingTemplate['id'];
        echo "Default template already exists.\n";
    }

    // Check if sample data should be added
    $existingProjects = $db->fetchOne("SELECT COUNT(*) as count FROM projects")['count'];

    if ($existingProjects == 0) {
        // Insert sample data only if no projects exist
        $existingClient = $db->fetchOne("SELECT id FROM clients WHERE name = 'Sample Client'");

        if (!$existingClient) {
            $clientId = $db->insert('clients', [
                'name' => 'Sample Client',
                'address' => '123 Main Street',
                'phone' => '555-0123',
                'email' => 'client@example.com',
                'remarks' => 'Sample client for testing',
                'created_by' => $adminId,
                'updated_by' => $adminId
            ]);
            echo "Sample client created.\n";
        } else {
            $clientId = $existingClient['id'];
        }

        // Get status ID for "In Progress"
        $statusId = $db->fetchOne("SELECT id FROM project_statuses WHERE name = 'In Progress'")['id'] ?? 3;

        $db->insert('projects', [
            'title' => 'Sample Project',
            'details' => 'This is a sample project for demonstration.',
            'client_id' => $clientId,
            'start_date' => date('Y-m-d'),
            'completion_date' => date('Y-m-d', strtotime('+7 days')),
            'status_id' => $statusId,
            'fabric' => 'Cotton',
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'template_id' => $templateId
        ]);
        echo "Sample project created.\n";
    } else {
        echo "Projects already exist - skipping sample data.\n";
    }

    echo "\nDatabase initialization complete!\n";
}

// Run initialization if called directly
if (php_sapi_name() === 'cli' && basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    initializeDatabase();
}
?>