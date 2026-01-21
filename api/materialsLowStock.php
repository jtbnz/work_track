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

$materialModel = new Material();
$materials = $materialModel->getLowStock();

echo json_encode([
    'success' => true,
    'materials' => $materials,
    'count' => count($materials)
]);
