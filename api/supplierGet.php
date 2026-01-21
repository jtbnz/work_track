<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/models/Supplier.php';

// Check authentication
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Supplier ID required']);
    exit;
}

$supplierModel = new Supplier();
$supplier = $supplierModel->getById($_GET['id']);

if ($supplier) {
    echo json_encode(['success' => true, 'supplier' => $supplier]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Supplier not found']);
}
