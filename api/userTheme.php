<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/models/Settings.php';

// Check authentication
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$theme = $input['theme'] ?? '';
if (!in_array($theme, ['light', 'dark'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Theme must be light or dark']);
    exit;
}

$settings = new Settings();
$settings->set('user_theme_' . Auth::getCurrentUserId(), $theme);

echo json_encode(['success' => true, 'theme' => $theme]);
