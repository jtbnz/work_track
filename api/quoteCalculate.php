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

$quoteModel = new Quote();

// Verify quote exists
$quote = $quoteModel->getById($input['quote_id']);
if (!$quote) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Quote not found']);
    exit;
}

// Recalculate totals
$result = $quoteModel->calculateTotals($input['quote_id']);

if ($result) {
    // Get updated quote data
    $updatedQuote = $quoteModel->getById($input['quote_id']);

    echo json_encode([
        'success' => true,
        'data' => [
            'subtotal_materials' => (float)$updatedQuote['subtotal_materials'],
            'subtotal_misc' => (float)$updatedQuote['subtotal_misc'],
            'subtotal_labour' => (float)$updatedQuote['subtotal_labour'],
            'total_excl_gst' => (float)$updatedQuote['total_excl_gst'],
            'gst_amount' => (float)$updatedQuote['gst_amount'],
            'total_incl_gst' => (float)$updatedQuote['total_incl_gst']
        ],
        'message' => 'Totals calculated successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to calculate totals']);
}
