<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once __DIR__ . '/db.php';

session_start();

class Auth {
    private static $db;

    private static function getDb() {
        if (!self::$db) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    public static function login($username, $password) {
        $db = self::getDb();

        $user = $db->fetchOne(
            "SELECT * FROM users WHERE username = :username",
            ['username' => $username]
        );

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['login_time'] = time();

            // Update last login
            $db->update('users',
                ['last_login' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $user['id']]
            );

            return true;
        }

        return false;
    }

    public static function logout() {
        session_destroy();
        session_start();
    }

    public static function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Check session timeout
        if (isset($_SESSION['login_time'])) {
            if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
                self::logout();
                return false;
            }
            $_SESSION['login_time'] = time(); // Reset timeout
        }

        return true;
    }

    public static function requireAuth() {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_PATH . '/login.php');
            exit;
        }
    }

    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function getCurrentUsername() {
        return $_SESSION['username'] ?? null;
    }

    public static function createUser($username, $password, $email) {
        $db = self::getDb();

        // Check if username exists
        $existing = $db->fetchOne(
            "SELECT id FROM users WHERE username = :username",
            ['username' => $username]
        );

        if ($existing) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        $userId = $db->insert('users', [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'email' => $email
        ]);

        if ($userId) {
            self::logAudit('users', $userId, 'INSERT', [
                'username' => $username,
                'email' => $email
            ]);

            return ['success' => true, 'user_id' => $userId];
        }

        return ['success' => false, 'message' => 'Failed to create user'];
    }

    public static function logAudit($table, $recordId, $action, $changes = []) {
        $db = self::getDb();

        $db->insert('audit_log', [
            'table_name' => $table,
            'record_id' => $recordId,
            'action' => $action,
            'changes_json' => json_encode($changes),
            'user_id' => self::getCurrentUserId()
        ]);
    }
}
?>