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

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['project_id']) || !isset($input['start_date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$projectModel = new Project();
$projectId = $input['project_id'];
$startDate = $input['start_date'];

// Get current project to calculate completion date
$project = $projectModel->getById($projectId);
if (!$project) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}

// Calculate new completion date based on duration
$originalStart = $project['start_date'];
$originalEnd = $project['completion_date'];
$newCompletionDate = null;

if ($originalStart && $originalEnd) {
    $duration = (strtotime($originalEnd) - strtotime($originalStart)) / 86400; // days
    $newCompletionDate = date('Y-m-d', strtotime($startDate . ' +' . $duration . ' days'));
}

$result = $projectModel->updateDates($projectId, $startDate, $newCompletionDate);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update project']);
}
?>