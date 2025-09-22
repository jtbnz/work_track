<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';

// Check authentication
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    die('Not authenticated');
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    die('Attachment ID required');
}

$db = Database::getInstance();
$attachment = $db->fetchOne(
    "SELECT * FROM project_attachments WHERE id = :id",
    ['id' => $_GET['id']]
);

if (!$attachment) {
    http_response_code(404);
    die('Attachment not found');
}

if (!file_exists($attachment['file_path'])) {
    http_response_code(404);
    die('File not found');
}

// Set headers for file download
header('Content-Type: ' . ($attachment['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . $attachment['filename'] . '"');
header('Content-Length: ' . filesize($attachment['file_path']));
header('Cache-Control: no-cache, no-store, must-revalidate');

// Output file
readfile($attachment['file_path']);
exit;
?>