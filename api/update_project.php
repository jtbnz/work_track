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

$projectId = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;
if (!$projectId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit;
}

$data = [
    'title' => $_POST['title'] ?? '',
    'start_date' => $_POST['start_date'] ?: null,
    'completion_date' => $_POST['completion_date'] ?: null
];

// Validate required fields
if (!$data['title']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

$projectModel = new Project();
$result = $projectModel->update($projectId, $data);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update project']);
}
?>