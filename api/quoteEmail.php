<?php
/**
 * Send Quote Email API
 * Sends a quote as PDF attachment to the client
 */

require_once __DIR__ . '/../includes/auth.php';
Auth::requireAuth();

require_once __DIR__ . '/../includes/EmailService.php';
require_once __DIR__ . '/../includes/models/Quote.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$quoteId = (int)($input['quote_id'] ?? 0);
$toEmail = trim($input['to_email'] ?? '');
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');
$updateStatus = (bool)($input['update_status'] ?? true);

if (!$quoteId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Quote ID is required']);
    exit;
}

try {
    // Get quote to validate it exists
    $quote = Quote::getById($quoteId);
    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quote not found']);
        exit;
    }

    // If no email provided, try to get client email
    if (empty($toEmail)) {
        $db = Database::getInstance();
        $client = $db->fetchOne(
            "SELECT email FROM clients WHERE id = ?",
            [$quote['client_id']]
        );
        $toEmail = $client['email'] ?? '';
    }

    if (empty($toEmail)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No email address provided and client has no email on file'
        ]);
        exit;
    }

    // Validate email format
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address format']);
        exit;
    }

    // Send the email
    $emailService = new EmailService();

    if (!$emailService->isConfigured()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email not configured. Please set up SMTP settings in Quoting Settings.'
        ]);
        exit;
    }

    $success = $emailService->sendQuote($quoteId, $toEmail, $subject, $message, $updateStatus);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => "Quote sent successfully to {$toEmail}"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $emailService->getLastError() ?: 'Failed to send email'
        ]);
    }

} catch (Exception $e) {
    error_log("Quote email error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while sending the email'
    ]);
}
