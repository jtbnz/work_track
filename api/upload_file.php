<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/models/Project.php';

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

if (!isset($_POST['project_id']) || !isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$projectId = $_POST['project_id'];
$file = $_FILES['file'];

// Validate file
$allowedTypes = ALLOWED_FILE_TYPES;
$maxSize = MAX_UPLOAD_SIZE;

$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($fileExt, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes)]);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size: ' . ($maxSize / 1048576) . 'MB']);
    exit;
}

// Create upload directory for project if it doesn't exist
$uploadDir = UPLOAD_PATH . 'project_' . $projectId . '/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    exit;
}

// Save to database
$projectModel = new Project();
$attachmentId = $projectModel->addAttachment(
    $projectId,
    $file['name'],
    $filepath,
    $file['size'],
    $file['type']
);

if ($attachmentId) {
    echo json_encode([
        'success' => true,
        'attachment_id' => $attachmentId,
        'filename' => $file['name']
    ]);
} else {
    // Remove uploaded file if database insert failed
    unlink($filepath);
    echo json_encode(['success' => false, 'message' => 'Failed to save attachment']);
}
?>