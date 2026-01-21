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

$query = $_GET['q'] ?? '';
$limit = (int)($_GET['limit'] ?? 20);

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$materialModel = new Material();
$materials = $materialModel->search($query, $limit);

echo json_encode(['success' => true, 'data' => $materials]);
