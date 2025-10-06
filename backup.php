<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Handle download action BEFORE any output
if (isset($_GET['download']) && $_GET['download']) {
    Auth::requireAuth();
    $filename = basename($_GET['download']);
    $filepath = dirname(__FILE__) . '/backups/' . $filename;

    if (file_exists($filepath) && strpos($filename, 'worktrack_backup_') === 0) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        $_SESSION['backup_message'] = 'Backup file not found';
        $_SESSION['backup_message_type'] = 'danger';
        header('Location: backup.php');
        exit;
    }
}

$pageTitle = 'Database Backup';
require_once 'includes/header.php';

$db = Database::getInstance();
$message = '';
$messageType = '';

// Check for session messages
if (isset($_SESSION['backup_message'])) {
    $message = $_SESSION['backup_message'];
    $messageType = $_SESSION['backup_message_type'];
    unset($_SESSION['backup_message']);
    unset($_SESSION['backup_message_type']);
}

// Handle backup action
if (isset($_POST['action']) && $_POST['action'] === 'backup') {
    $backupType = $_POST['backup_type'] ?? 'full';

    try {
        // Create backup directory if it doesn't exist
        $backupDir = dirname(__FILE__) . '/backups';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . '/worktrack_backup_' . $timestamp . '.sql';

        // Get database path
        $dbPath = dirname(__FILE__) . '/database/worktrack.db';

        if ($backupType === 'full') {
            // Check if exec is available and sqlite3 command exists
            $useExec = function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));

            if ($useExec) {
                // Try SQLite dump command
                $output = [];
                $returnCode = 0;
                @exec("sqlite3 '$dbPath' .dump", $output, $returnCode);

                if ($returnCode === 0 && !empty($output)) {
                    file_put_contents($backupFile, implode("\n", $output));

                    Auth::logAudit('backup', 0, 'CREATE', [
                        'type' => 'full',
                        'file' => basename($backupFile),
                        'size' => filesize($backupFile)
                    ]);

                    $message = 'Database backup created successfully: ' . basename($backupFile);
                    $messageType = 'success';
                } else {
                    $useExec = false; // Fall through to PHP method
                }
            }

            if (!$useExec) {
                // PHP-based backup
                $sql = generateSQLDump($db);
                file_put_contents($backupFile, $sql);

                Auth::logAudit('backup', 0, 'CREATE', [
                    'type' => 'full_php',
                    'file' => basename($backupFile),
                    'size' => filesize($backupFile)
                ]);

                $message = 'Database backup created successfully: ' . basename($backupFile);
                $messageType = 'success';
            }
        } else {
            // Data-only backup (no schema)
            $sql = generateDataOnlyDump($db);
            file_put_contents($backupFile, $sql);

            Auth::logAudit('backup', 0, 'CREATE', [
                'type' => 'data',
                'file' => basename($backupFile),
                'size' => filesize($backupFile)
            ]);

            $message = 'Data backup created successfully: ' . basename($backupFile);
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = 'Backup failed: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Handle delete action
if (isset($_POST['delete']) && $_POST['delete']) {
    $filename = basename($_POST['delete']);
    $filepath = dirname(__FILE__) . '/backups/' . $filename;

    if (file_exists($filepath) && strpos($filename, 'worktrack_backup_') === 0) {
        unlink($filepath);

        Auth::logAudit('backup', 0, 'DELETE', ['file' => $filename]);

        $message = 'Backup deleted successfully';
        $messageType = 'success';
    } else {
        $message = 'Backup file not found';
        $messageType = 'danger';
    }
}

// Get existing backups
$backupDir = dirname(__FILE__) . '/backups';
$backups = [];
if (file_exists($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (strpos($file, 'worktrack_backup_') === 0) {
            $filepath = $backupDir . '/' . $file;
            $backups[] = [
                'filename' => $file,
                'size' => filesize($filepath),
                'created' => filemtime($filepath),
                'type' => strpos($file, '.sql') !== false ? 'SQL' : 'Unknown'
            ];
        }
    }
    // Sort by creation date descending
    usort($backups, function($a, $b) {
        return $b['created'] - $a['created'];
    });
}

// Get database statistics
$stats = getDatabaseStats($db);

function getDatabaseStats($db) {
    $stats = [];
    $tables = ['projects', 'clients', 'project_statuses', 'project_templates', 'users', 'project_attachments', 'audit_log'];

    foreach ($tables as $table) {
        $count = $db->fetchOne("SELECT COUNT(*) as count FROM $table")['count'];
        $stats[$table] = $count;
    }

    // Get database file size
    $dbPath = dirname(__FILE__) . '/database/worktrack.db';
    $stats['db_size'] = file_exists($dbPath) ? filesize($dbPath) : 0;

    return $stats;
}

function generateSQLDump($db) {
    $sql = "-- WorkTrack Database Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

    // Get schema
    $tables = $db->fetchAll("SELECT sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    foreach ($tables as $table) {
        $sql .= $table['sql'] . ";\n\n";
    }

    // Get data
    $tableNames = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    foreach ($tableNames as $table) {
        $tableName = $table['name'];
        $sql .= "-- Data for table: $tableName\n";

        $rows = $db->fetchAll("SELECT * FROM $tableName");
        foreach ($rows as $row) {
            $columns = array_keys($row);
            $values = array_map(function($val) {
                if ($val === null) return 'NULL';
                if (is_numeric($val)) return $val;
                return "'" . str_replace("'", "''", $val) . "'";
            }, array_values($row));

            $sql .= "INSERT INTO $tableName (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
        }
        $sql .= "\n";
    }

    return $sql;
}

function generateDataOnlyDump($db) {
    $sql = "-- WorkTrack Data-Only Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

    $tableNames = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    foreach ($tableNames as $table) {
        $tableName = $table['name'];
        $sql .= "-- Data for table: $tableName\n";
        $sql .= "DELETE FROM $tableName;\n";

        $rows = $db->fetchAll("SELECT * FROM $tableName");
        foreach ($rows as $row) {
            $columns = array_keys($row);
            $values = array_map(function($val) {
                if ($val === null) return 'NULL';
                if (is_numeric($val)) return $val;
                return "'" . str_replace("'", "''", $val) . "'";
            }, array_values($row));

            $sql .= "INSERT INTO $tableName (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
        }
        $sql .= "\n";
    }

    return $sql;
}
?>

<div class="page-header">
    <h1 class="page-title">Database Backup & Export</h1>
    <div class="page-actions">
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="backup">
            <input type="hidden" name="backup_type" value="full">
            <button type="submit" class="btn btn-primary">📥 Create Full Backup</button>
        </form>
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="backup">
            <input type="hidden" name="backup_type" value="data">
            <button type="submit" class="btn btn-secondary">📊 Data-Only Backup</button>
        </form>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- Database Statistics -->
    <div class="dashboard-card">
        <h3>Database Statistics</h3>
        <div style="display: grid; gap: 10px; margin-top: 15px;">
            <div><strong>Database Size:</strong> <?php echo number_format($stats['db_size'] / 1024, 1); ?> KB</div>
            <div><strong>Projects:</strong> <?php echo $stats['projects']; ?></div>
            <div><strong>Clients:</strong> <?php echo $stats['clients']; ?></div>
            <div><strong>Templates:</strong> <?php echo $stats['project_templates']; ?></div>
            <div><strong>Attachments:</strong> <?php echo $stats['project_attachments']; ?></div>
            <div><strong>Users:</strong> <?php echo $stats['users']; ?></div>
            <div><strong>Audit Log Entries:</strong> <?php echo $stats['audit_log']; ?></div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="dashboard-card">
        <h3>Export Options</h3>
        <div style="margin-top: 15px;">
            <p style="margin-bottom: 15px;">Choose your backup type:</p>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px;">
                    <strong>Full Backup:</strong> Complete database with schema and data
                </li>
                <li>
                    <strong>Data-Only:</strong> Just the data without table structure
                </li>
            </ul>
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <small>
                    <strong>Note:</strong> Backups are stored in the /backups directory.
                    Download important backups to your local machine for safekeeping.
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Existing Backups -->
<div class="form-container" style="margin-top: 30px;">
    <h2>Existing Backups</h2>

    <?php if (count($backups) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Created</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($backup['filename']); ?></strong>
                        </td>
                        <td><?php echo number_format($backup['size'] / 1024, 1); ?> KB</td>
                        <td><?php echo date('M j, Y g:i A', $backup['created']); ?></td>
                        <td>
                            <span class="status-badge" style="background: #28a745; color: white;">
                                <?php echo $backup['type']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="?download=<?php echo urlencode($backup['filename']); ?>"
                               class="btn btn-sm btn-primary">Download</a>

                            <form method="POST" style="display: inline;"
                                  onsubmit="return confirm('Are you sure you want to delete this backup?');">
                                <input type="hidden" name="delete" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: #666; text-align: center; padding: 40px;">
            No backups found. Create your first backup using the buttons above.
        </p>
    <?php endif; ?>
</div>

<style>
.btn-sm {
    padding: 5px 10px;
    font-size: 13px;
}
</style>

<?php require_once 'includes/footer.php'; ?>