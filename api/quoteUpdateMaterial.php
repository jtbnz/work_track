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
if (empty($input['line_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Line ID is required']);
    exit;
}

$quoteModel = new Quote();
$db = Database::getInstance();

// Get the line item to verify it exists and quote is editable
$line = $db->fetchOne("SELECT qm.*, q.status FROM quote_materials qm JOIN quotes q ON qm.quote_id = q.id WHERE qm.id = :id", ['id' => $input['line_id']]);

if (!$line) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Line item not found']);
    exit;
}

if ($line['status'] !== 'draft') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cannot modify a non-draft quote']);
    exit;
}

// Prepare update data
$updateData = [];
if (isset($input['quantity'])) {
    $updateData['quantity'] = (float)$input['quantity'];
}
if (isset($input['unit_cost'])) {
    $updateData['unit_cost'] = (float)$input['unit_cost'];
}
if (isset($input['item_description'])) {
    $updateData['item_description'] = $input['item_description'];
}

if (empty($updateData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No update data provided']);
    exit;
}

$result = $quoteModel->updateMaterial($input['line_id'], $updateData);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Material updated successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update material']);
}
