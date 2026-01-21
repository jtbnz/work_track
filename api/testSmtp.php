<?php
/**
 * Test SMTP Connection API
 */

require_once __DIR__ . '/../includes/auth.php';
Auth::requireAuth();

require_once __DIR__ . '/../includes/EmailService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $emailService = new EmailService();
    $result = $emailService->testConnection();
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Test failed: ' . $e->getMessage()
    ]);
}
