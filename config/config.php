<?php
// Configuration settings
define('DB_PATH', dirname(__DIR__) . '/database/work_track.db');
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('SITE_NAME', 'WorkTrack');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Base URL configuration - automatically detect if in subdirectory
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = ($scriptPath === '/' || $scriptPath === '\\') ? '' : $scriptPath;
define('BASE_PATH', $basePath);
define('MAX_UPLOAD_SIZE', 10485760); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'txt']);

// Environment
define('DEBUG_MODE', true);

// Timezone
date_default_timezone_set('UTC');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>