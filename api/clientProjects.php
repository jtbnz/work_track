<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/models/Client.php';

// Check authentication
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate required fields
if (empty($_GET['client_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Client ID is required']);
    exit;
}

$clientModel = new Client();

// Verify client exists
$client = $clientModel->getById($_GET['client_id']);
if (!$client) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Client not found']);
    exit;
}

$projects = $clientModel->getProjects($_GET['client_id']);

echo json_encode([
    'success' => true,
    'data' => $projects
]);
