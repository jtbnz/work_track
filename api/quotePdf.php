<?php
/**
 * Quote PDF Generation API
 *
 * GET: Generate and download PDF for a quote
 * Parameters:
 *   - id: Quote ID (required)
 *   - action: 'download' (default) or 'save' (saves to server and returns path)
 */

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/models/Quote.php';
require_once dirname(__DIR__) . '/includes/pdf/QuotePdf.php';

Auth::requireAuth();

header('Content-Type: application/json');

try {
    $quoteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $action = $_GET['action'] ?? 'download';

    if (!$quoteId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Quote ID is required']);
        exit;
    }

    $quoteModel = new Quote();
    $quote = $quoteModel->getById($quoteId);

    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quote not found']);
        exit;
    }

    $pdf = new QuotePdf();

    if ($action === 'save') {
        // Save to file and update quote record
        $filepath = $pdf->saveToFile($quote);
        echo json_encode([
            'success' => true,
            'message' => 'PDF saved successfully',
            'path' => 'uploads/pdfs/quotes/' . basename($filepath),
            'filename' => basename($filepath)
        ]);
    } else {
        // Download directly
        $pdfContent = $pdf->generate($quote);

        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Quote_' . $quote['quote_number'] . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfContent;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error generating PDF: ' . $e->getMessage()
    ]);
}
