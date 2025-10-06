<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

class Settings {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT NOT NULL UNIQUE,
                setting_value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_by INTEGER REFERENCES users(id)
            )
        ");

        // Insert default settings if they don't exist
        $this->db->exec("
            INSERT OR IGNORE INTO settings (setting_key, setting_value)
            VALUES ('kanban_hide_completed', '0')
        ");
    }

    public function get(string $key, string $default = ''): string {
        $result = $this->db->fetchOne(
            "SELECT setting_value FROM settings WHERE setting_key = :key",
            ['key' => $key]
        );

        return $result ? $result['setting_value'] : $default;
    }

    public function set(string $key, string $value): bool {
        $userId = $_SESSION['user_id'] ?? null;

        // Try to update first
        $affected = $this->db->update(
            'settings',
            [
                'setting_value' => $value,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $userId
            ],
            'setting_key = :key',
            ['key' => $key]
        );

        // If no rows affected, insert new setting
        if (!$affected) {
            $affected = $this->db->insert('settings', [
                'setting_key' => $key,
                'setting_value' => $value,
                'updated_by' => $userId
            ]);
        }

        if ($affected) {
            Auth::logAudit('settings', 0, 'UPDATE', [
                'key' => $key,
                'value' => $value
            ]);
        }

        return (bool)$affected;
    }

    public function getAll(): array {
        return $this->db->fetchAll("SELECT * FROM settings ORDER BY setting_key");
    }
}
