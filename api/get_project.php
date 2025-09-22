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

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit;
}

$projectModel = new Project();
$project = $projectModel->getById($_GET['id']);

if ($project) {
    echo json_encode(['success' => true, 'project' => $project]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Project not found']);
}
?>