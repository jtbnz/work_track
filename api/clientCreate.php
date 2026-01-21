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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
if (empty($input['name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Client name is required']);
    exit;
}

$clientModel = new Client();

$data = [
    'name' => trim($input['name']),
    'email' => trim($input['email'] ?? ''),
    'phone' => trim($input['phone'] ?? ''),
    'address' => trim($input['address'] ?? ''),
    'remarks' => trim($input['remarks'] ?? '')
];

$clientId = $clientModel->create($data);

if ($clientId) {
    $client = $clientModel->getById($clientId);
    echo json_encode([
        'success' => true,
        'message' => 'Client created successfully',
        'data' => $client
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create client']);
}
