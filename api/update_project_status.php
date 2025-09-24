<?php
header('Content-Type: application/json');

try {
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

    if (!isset($input['project_id']) || !isset($input['status_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $projectModel = new Project();
    $result = $projectModel->updateStatus($input['project_id'], $input['status_id']);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update project status']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>