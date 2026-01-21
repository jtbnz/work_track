<?php
/**
 * Debug endpoint for email sending issues
 * DELETE THIS FILE AFTER DEBUGGING
 */

header('Content-Type: application/json');

$debug = [
    'php_version' => PHP_VERSION,
    'errors' => [],
    'checks' => []
];

// Check includes exist
$files = [
    'auth' => __DIR__ . '/../includes/auth.php',
    'db' => __DIR__ . '/../includes/db.php',
    'quote_model' => __DIR__ . '/../includes/models/Quote.php',
    'email_service' => __DIR__ . '/../includes/EmailService.php',
    'quote_pdf' => __DIR__ . '/../includes/pdf/QuotePdf.php',
    'vendor_autoload' => __DIR__ . '/../vendor/autoload.php',
];

foreach ($files as $name => $path) {
    $debug['checks'][$name] = file_exists($path) ? 'OK' : 'MISSING';
}

// Check directories
$dirs = [
    'uploads_logos' => __DIR__ . '/../uploads/logos',
    'uploads_pdfs_quotes' => __DIR__ . '/../uploads/pdfs/quotes',
];

foreach ($dirs as $name => $path) {
    if (is_dir($path)) {
        $debug['checks'][$name] = is_writable($path) ? 'OK (writable)' : 'EXISTS (not writable)';
    } else {
        $debug['checks'][$name] = 'MISSING';
    }
}

// Try to load classes
try {
    require_once __DIR__ . '/../includes/auth.php';
    $debug['checks']['auth_load'] = 'OK';
} catch (Throwable $e) {
    $debug['checks']['auth_load'] = 'ERROR: ' . $e->getMessage();
}

try {
    require_once __DIR__ . '/../includes/db.php';
    $db = Database::getInstance();
    $debug['checks']['db_load'] = 'OK';
} catch (Throwable $e) {
    $debug['checks']['db_load'] = 'ERROR: ' . $e->getMessage();
}

try {
    require_once __DIR__ . '/../includes/models/Quote.php';
    $debug['checks']['quote_model_load'] = 'OK';
} catch (Throwable $e) {
    $debug['checks']['quote_model_load'] = 'ERROR: ' . $e->getMessage();
}

try {
    require_once __DIR__ . '/../includes/pdf/QuotePdf.php';
    $debug['checks']['quote_pdf_load'] = 'OK';
} catch (Throwable $e) {
    $debug['checks']['quote_pdf_load'] = 'ERROR: ' . $e->getMessage();
}

try {
    require_once __DIR__ . '/../includes/EmailService.php';
    $debug['checks']['email_service_load'] = 'OK';
} catch (Throwable $e) {
    $debug['checks']['email_service_load'] = 'ERROR: ' . $e->getMessage();
}

// Try to create QuotePdf instance
try {
    $pdf = new QuotePdf();
    $debug['checks']['quote_pdf_instance'] = 'OK';
} catch (Throwable $e) {
    $debug['checks']['quote_pdf_instance'] = 'ERROR: ' . $e->getMessage();
}

// Try to get a quote
try {
    $quoteModel = new Quote();
    $quotes = $quoteModel->getAll();
    $debug['checks']['quote_fetch'] = 'OK - found ' . count($quotes) . ' quotes';

    if (count($quotes) > 0) {
        $quote = $quoteModel->getById($quotes[0]['id']);
        $debug['checks']['quote_by_id'] = $quote ? 'OK' : 'FAILED';

        // Try PDF generation
        if ($quote) {
            try {
                $pdf = new QuotePdf();
                $content = $pdf->generate($quote);
                $debug['checks']['pdf_generate'] = 'OK - ' . strlen($content) . ' bytes';
            } catch (Throwable $e) {
                $debug['checks']['pdf_generate'] = 'ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            }
        }
    }
} catch (Throwable $e) {
    $debug['checks']['quote_fetch'] = 'ERROR: ' . $e->getMessage();
}

// Try EmailService
try {
    $emailService = new EmailService();
    $debug['checks']['email_service_instance'] = 'OK';
    $debug['checks']['email_configured'] = $emailService->isConfigured() ? 'Yes' : 'No';

    // Test SMTP connection
    $testResult = $emailService->testConnection();
    $debug['checks']['smtp_connection'] = $testResult['success'] ? 'OK' : 'FAILED: ' . $testResult['message'];
} catch (Throwable $e) {
    $debug['checks']['email_service_instance'] = 'ERROR: ' . $e->getMessage();
}

// If quote_id is provided, try to actually send to a test email
if (isset($_GET['test_send']) && isset($_GET['quote_id']) && isset($_GET['email'])) {
    $quoteId = (int)$_GET['quote_id'];
    $testEmail = $_GET['email'];

    try {
        $result = $emailService->sendQuote($quoteId, $testEmail, 'Test Quote Email', '', false);
        $debug['checks']['test_send'] = $result ? 'OK - Email sent!' : 'FAILED: ' . $emailService->getLastError();
    } catch (Throwable $e) {
        $debug['checks']['test_send'] = 'ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }
}

echo json_encode($debug, JSON_PRETTY_PRINT);
