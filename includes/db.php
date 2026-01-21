<?php
require_once dirname(__DIR__) . '/config/config.php';

class Database {
    private static $instance = null;
    private $conn;
    private static $initialized = false;

    private function __construct() {
        $isNewDatabase = !file_exists(DB_PATH);

        try {
            $this->conn = new PDO('sqlite:' . DB_PATH);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Enable foreign keys
            $this->conn->exec('PRAGMA foreign_keys = ON');

            // Auto-initialize database if new or needs migration
            if (!self::$initialized) {
                self::$initialized = true;
                $this->autoInitialize($isNewDatabase);
            }
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    /**
     * Auto-initialize database schema and run migrations
     */
    private function autoInitialize($isNewDatabase) {
        // If new database, create schema first
        if ($isNewDatabase) {
            $schemaFile = dirname(__DIR__) . '/database/schema.sql';
            if (file_exists($schemaFile)) {
                $schema = file_get_contents($schemaFile);
                $this->conn->exec($schema);
            }

            // Create default admin user
            $adminPassword = password_hash('admin', PASSWORD_DEFAULT);
            $this->conn->exec("INSERT OR IGNORE INTO users (username, password_hash, email) VALUES ('admin', '$adminPassword', 'admin@worktrack.local')");

            // Create default project statuses
            $statuses = [
                ['Quote Required', '#ffc107', 1],
                ['Pending', '#6c757d', 2],
                ['In Progress', '#007bff', 3],
                ['Completed', '#28a745', 4],
                ['On Hold', '#fd7e14', 5],
                ['Cancelled', '#dc3545', 6]
            ];
            foreach ($statuses as $status) {
                $this->conn->exec("INSERT OR IGNORE INTO project_statuses (name, color, sort_order) VALUES ('{$status[0]}', '{$status[1]}', {$status[2]})");
            }

            // Create default project template
            $this->conn->exec("INSERT OR IGNORE INTO project_templates (name, default_title, default_details, is_default) VALUES ('Default Template', 'New Project', 'Project details here...', 1)");
        }

        // Always run migrations (handles upgrades)
        $this->runMigrations();
    }

    /**
     * Run pending database migrations
     */
    private function runMigrations() {
        // Create migrations tracking table if not exists
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL UNIQUE,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Get list of executed migrations
        $stmt = $this->conn->query("SELECT migration FROM migrations");
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Get migration files
        $migrationPath = dirname(__DIR__) . '/database/migrations/';
        if (!is_dir($migrationPath)) {
            return;
        }

        $files = glob($migrationPath . '*.sql');
        if (empty($files)) {
            return;
        }

        sort($files);

        foreach ($files as $file) {
            $migrationName = basename($file);
            if (!in_array($migrationName, $executed)) {
                $sql = file_get_contents($file);
                try {
                    $this->conn->exec($sql);
                    $stmt = $this->conn->prepare("INSERT INTO migrations (migration) VALUES (?)");
                    $stmt->execute([$migrationName]);
                } catch (PDOException $e) {
                    error_log("Migration error ($migrationName): " . $e->getMessage());
                }
            }
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                throw $e;
            }
            error_log($e->getMessage());
            return false;
        }
    }

    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = array_map(function($col) { return ':' . $col; }, $columns);

        $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->query($sql, $data);
        return $stmt ? $this->conn->lastInsertId() : false;
    }

    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach ($data as $column => $value) {
            $setClause[] = "$column = :$column";
        }

        $sql = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE $where";
        $params = array_merge($data, $whereParams);

        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    }

    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : null;
    }

    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    public function commit() {
        return $this->conn->commit();
    }

    public function rollback() {
        return $this->conn->rollback();
    }
}
?>