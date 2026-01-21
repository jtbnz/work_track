<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/auth.php';

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

require_once dirname(__DIR__) . '/includes/import/MaterialImporter.php';

// Check file upload
if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    $error = $_FILES['csvFile']['error'] ?? UPLOAD_ERR_NO_FILE;
    echo json_encode([
        'success' => false,
        'message' => $errorMessages[$error] ?? 'Unknown upload error'
    ]);
    exit;
}

// Validate file extension
$fileExtension = strtolower(pathinfo($_FILES['csvFile']['name'], PATHINFO_EXTENSION));
if ($fileExtension !== 'csv') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file type. Please upload a CSV file (.csv)'
    ]);
    exit;
}

$updateExisting = isset($_POST['updateExisting']) && $_POST['updateExisting'] === '1';
$filePath = $_FILES['csvFile']['tmp_name'];

try {
    $importer = new MaterialImporter();
    $result = $importer->import($filePath, $updateExisting);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Import failed: ' . $e->getMessage()
    ]);
}
