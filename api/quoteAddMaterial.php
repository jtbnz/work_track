<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/models/Quote.php';

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
if (empty($input['quote_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Quote ID is required']);
    exit;
}

if (empty($input['item_description'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Item description is required']);
    exit;
}

$quoteModel = new Quote();

// Verify quote exists and is editable
$quote = $quoteModel->getById($input['quote_id']);
if (!$quote) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Quote not found']);
    exit;
}

if ($quote['status'] !== 'draft') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cannot modify a non-draft quote']);
    exit;
}

// Add material
$data = [
    'material_id' => $input['material_id'] ?? null,
    'item_description' => $input['item_description'],
    'quantity' => $input['quantity'] ?? 1,
    'unit_cost' => $input['unit_cost'] ?? 0
];

$lineId = $quoteModel->addMaterial($input['quote_id'], $data);

if ($lineId) {
    echo json_encode([
        'success' => true,
        'line_id' => $lineId,
        'message' => 'Material added successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to add material']);
}
