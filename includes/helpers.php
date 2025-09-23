<?php
/**
 * Helper functions for the application
 */

/**
 * Generate a URL with the correct base path
 *
 * @param string $path The path relative to the application root
 * @return string The full URL with base path
 */
function url($path = '') {
    if (strpos($path, '/') === 0) {
        $path = substr($path, 1);
    }
    return BASE_PATH . '/' . $path;
}

/**
 * Redirect to a URL with the correct base path
 *
 * @param string $path The path relative to the application root
 * @param bool $exit Whether to exit after redirecting (default: true)
 */
function redirect($path = '', $exit = true) {
    header('Location: ' . url($path));
    if ($exit) {
        exit;
    }
}

/**
 * Get the current URL path without the base path
 *
 * @return string The current path
 */
function getCurrentPath() {
    $path = $_SERVER['REQUEST_URI'];
    if (BASE_PATH && strpos($path, BASE_PATH) === 0) {
        $path = substr($path, strlen(BASE_PATH));
    }
    return $path;
}
?>