<?php
/**
 * Material Importer - Imports materials from CSV files
 */

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/models/Supplier.php';
require_once dirname(__DIR__) . '/models/Material.php';

class MaterialImporter {
    private $db;
    private $supplierModel;
    private $materialModel;

    // Column mapping from CSV headers to database fields
    // Supports multiple variations of header names for flexibility
    private $columnMap = [
        'supplier' => 'supplier_name',
        'supplier name' => 'supplier_name',
        'manufacturers code' => 'manufacturers_code',
        'manufacturer' => 'manufacturers_code',
        'manufacture' => 'manufacturers_code',
        'mfr code' => 'manufacturers_code',
        'sku' => 'manufacturers_code',
        'code' => 'manufacturers_code',
        'item' => 'item_name',
        'item name' => 'item_name',
        'product' => 'item_name',
        'product name' => 'item_name',
        'name' => 'item_name',
        'description' => 'item_name',
        'cost excl' => 'cost_excl',
        'cost ex' => 'cost_excl',
        'cost' => 'cost_excl',
        'gst' => 'gst',
        'tax' => 'gst',
        'cost inc' => 'cost_incl',
        'cost incl' => 'cost_incl',
        'sell' => 'sell_price',
        'sell price' => 'sell_price',
        'price' => 'sell_price',
        'comments' => 'comments',
        'notes' => 'comments',
        'stock on hand' => 'stock_on_hand',
        'stock on han' => 'stock_on_hand',
        'stock' => 'stock_on_hand',
        'qty' => 'stock_on_hand',
        'quantity' => 'stock_on_hand',
        'reorder quantity' => 'reorder_quantity',
        'reorder qty' => 'reorder_quantity',
        'reorder qua' => 'reorder_quantity',
        'min order' => 'reorder_quantity',
        'unit of measure' => 'unit_of_measure',
        'unit of meas' => 'unit_of_measure',
        'unit' => 'unit_of_measure',
        'uom' => 'unit_of_measure'
    ];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->supplierModel = new Supplier();
        $this->materialModel = new Material();
    }

    /**
     * CSV import is always available (no external dependencies)
     */
    public static function isAvailable(): bool {
        return true;
    }

    /**
     * Import materials from a CSV file
     *
     * @param string $filePath Path to the CSV file
     * @param bool $updateExisting Whether to update existing materials
     * @return array Result with counts and any errors
     */
    public function import(string $filePath, bool $updateExisting = false): array {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'File not found: ' . $filePath,
                'inserted' => 0,
                'updated' => 0,
                'errors' => []
            ];
        }

        try {
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                return [
                    'success' => false,
                    'message' => 'Could not open file',
                    'inserted' => 0,
                    'updated' => 0,
                    'errors' => []
                ];
            }

            // Read headers from first row
            $headers = fgetcsv($handle, 0, ',', '"', '\\');
            if ($headers === false || count($headers) < 1) {
                fclose($handle);
                return [
                    'success' => false,
                    'message' => 'File is empty or has no headers',
                    'inserted' => 0,
                    'updated' => 0,
                    'errors' => []
                ];
            }

            // Trim whitespace and remove BOM if present (common in Excel exports)
            $headers = array_map(function($h) {
                $h = trim($h);
                // Remove UTF-8 BOM if present
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
                return $h;
            }, $headers);
            $headerMap = $this->mapHeaders($headers);

            $inserted = 0;
            $updated = 0;
            $errors = [];
            $supplierCache = []; // Cache supplier name -> id mapping
            $rowNum = 1;

            // Process data rows
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $rowNum++;

                try {
                    $data = $this->parseRow($row, $headerMap);

                    if (empty($data['item_name'])) {
                        continue; // Skip rows without item name
                    }

                    // Get or create supplier
                    $supplierId = null;
                    if (!empty($data['supplier_name'])) {
                        $supplierName = $data['supplier_name'];
                        if (!isset($supplierCache[$supplierName])) {
                            $supplierCache[$supplierName] = $this->supplierModel->findOrCreateByName($supplierName);
                        }
                        $supplierId = $supplierCache[$supplierName];
                    }

                    // Prepare material data
                    $materialData = [
                        'supplier_id' => $supplierId,
                        'manufacturers_code' => $data['manufacturers_code'] ?? '',
                        'item_name' => $data['item_name'],
                        'cost_excl' => (float)($data['cost_excl'] ?? 0),
                        'gst' => (float)($data['gst'] ?? 0),
                        'cost_incl' => (float)($data['cost_incl'] ?? 0),
                        'sell_price' => (float)($data['sell_price'] ?? 0),
                        'comments' => $data['comments'] ?? '',
                        'stock_on_hand' => (float)($data['stock_on_hand'] ?? 0),
                        'reorder_quantity' => (float)($data['reorder_quantity'] ?? 0),
                        'unit_of_measure' => $data['unit_of_measure'] ?? 'each'
                    ];

                    // Check for existing material (by manufacturers_code + supplier)
                    $existing = $this->findExistingMaterial(
                        $materialData['manufacturers_code'],
                        $supplierId
                    );

                    if ($existing) {
                        if ($updateExisting) {
                            // Track stock change for audit
                            $stockChange = $materialData['stock_on_hand'] - $existing['stock_on_hand'];

                            $this->materialModel->update($existing['id'], $materialData);

                            // Log stock movement if stock changed
                            if ($stockChange != 0) {
                                $this->logStockMovement(
                                    $existing['id'],
                                    $stockChange,
                                    $existing['stock_on_hand'],
                                    $materialData['stock_on_hand']
                                );
                            }

                            $updated++;
                        }
                    } else {
                        $newId = $this->materialModel->create($materialData);
                        if ($newId && $materialData['stock_on_hand'] > 0) {
                            // Log initial stock as import
                            $this->logStockMovement(
                                $newId,
                                $materialData['stock_on_hand'],
                                0,
                                $materialData['stock_on_hand']
                            );
                        }
                        $inserted++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row $rowNum: " . $e->getMessage();
                }
            }

            fclose($handle);

            return [
                'success' => true,
                'message' => "Import completed: $inserted inserted, $updated updated",
                'inserted' => $inserted,
                'updated' => $updated,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'inserted' => 0,
                'updated' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Preview first few rows of a CSV file
     */
    public function preview(string $filePath, int $limit = 10): array {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'File not found',
                'headers' => [],
                'rows' => []
            ];
        }

        try {
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                return [
                    'success' => false,
                    'message' => 'Could not open file',
                    'headers' => [],
                    'rows' => []
                ];
            }

            $headers = fgetcsv($handle, 0, ',', '"', '\\');
            if ($headers === false) {
                fclose($handle);
                return [
                    'success' => false,
                    'message' => 'File is empty',
                    'headers' => [],
                    'rows' => []
                ];
            }

            $headers = array_map('trim', $headers);
            $dataRows = [];
            $totalRows = 0;

            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $totalRows++;
                if (count($dataRows) < $limit) {
                    $dataRows[] = $row;
                }
            }

            fclose($handle);

            return [
                'success' => true,
                'headers' => $headers,
                'rows' => $dataRows,
                'totalRows' => $totalRows
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Preview failed: ' . $e->getMessage(),
                'headers' => [],
                'rows' => []
            ];
        }
    }

    /**
     * Map CSV headers to our field names (case-insensitive)
     */
    private function mapHeaders(array $headers): array {
        $map = [];
        foreach ($headers as $index => $header) {
            $header = strtolower(trim($header));
            if (isset($this->columnMap[$header])) {
                $map[$this->columnMap[$header]] = $index;
            }
        }
        return $map;
    }

    /**
     * Parse a row using the header map
     */
    private function parseRow(array $row, array $headerMap): array {
        $data = [];
        foreach ($headerMap as $field => $index) {
            $data[$field] = isset($row[$index]) ? trim($row[$index]) : null;
        }
        return $data;
    }

    /**
     * Find existing material by code and supplier
     */
    private function findExistingMaterial(?string $code, ?int $supplierId): ?array {
        if (empty($code)) {
            return null;
        }

        $sql = "SELECT * FROM materials WHERE manufacturers_code = :code";
        $params = ['code' => $code];

        if ($supplierId) {
            $sql .= " AND supplier_id = :supplier_id";
            $params['supplier_id'] = $supplierId;
        } else {
            $sql .= " AND supplier_id IS NULL";
        }

        return $this->db->fetchOne($sql, $params) ?: null;
    }

    /**
     * Log stock movement for import
     */
    private function logStockMovement(int $materialId, float $change, float $before, float $after): void {
        $this->db->insert('stock_movements', [
            'material_id' => $materialId,
            'movement_type' => 'import',
            'quantity_change' => $change,
            'stock_before' => $before,
            'stock_after' => $after,
            'reference_type' => 'import',
            'notes' => 'Imported from CSV',
            'created_by' => Auth::getCurrentUserId()
        ]);
    }
}
