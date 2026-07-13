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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Accept JSON body or form-encoded data
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$id = (int)($input['id'] ?? 0);
$notes = trim($input['notes'] ?? '');

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Material id is required']);
    exit;
}

$materialModel = new Material();
$material = $materialModel->getById($id);

if (!$material) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Material not found']);
    exit;
}

// Either an absolute new level ('new_stock') or a relative change ('adjustment')
if (isset($input['new_stock']) && $input['new_stock'] !== '') {
    $adjustment = (float)$input['new_stock'] - (float)$material['stock_on_hand'];
} elseif (isset($input['adjustment']) && $input['adjustment'] !== '') {
    $adjustment = (float)$input['adjustment'];
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Provide new_stock or adjustment']);
    exit;
}

if ($adjustment == 0.0) {
    echo json_encode(['success' => true, 'stock_on_hand' => (float)$material['stock_on_hand'], 'message' => 'No change']);
    exit;
}

$newStock = $materialModel->adjustStock($id, $adjustment, 'manual', null, null, $notes ?: 'Stock window update');

if ($newStock === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to adjust stock']);
    exit;
}

echo json_encode(['success' => true, 'stock_on_hand' => (float)$newStock]);
