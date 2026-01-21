<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/models/Material.php';

// Check authentication
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Material ID required']);
    exit;
}

$materialModel = new Material();
$material = $materialModel->getById($_GET['id']);

if ($material) {
    echo json_encode(['success' => true, 'material' => $material]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Material not found']);
}
