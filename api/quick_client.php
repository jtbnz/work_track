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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate input
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (!$name) {
    echo json_encode(['success' => false, 'message' => 'Client name is required']);
    exit;
}

// Create client
$clientModel = new Client();
$data = [
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'address' => '',
    'created_by' => Auth::getCurrentUserId()
];

$clientId = $clientModel->create($data);

if ($clientId) {
    echo json_encode([
        'success' => true,
        'client_id' => $clientId,
        'client_name' => $name,
        'message' => 'Client created successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create client'
    ]);
}
?>