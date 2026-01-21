<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

class Material {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = [], $sort = 'item_name', $sortDir = 'ASC') {
        $whereConditions = ["1=1"];
        $params = [];

        if (!empty($filters['supplier_id'])) {
            $whereConditions[] = "m.supplier_id = :supplier_id";
            $params['supplier_id'] = $filters['supplier_id'];
        }

        if (!empty($filters['search'])) {
            $whereConditions[] = "(m.item_name LIKE :search OR m.manufacturers_code LIKE :search OR s.name LIKE :search)";
            $params['search'] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['low_stock'])) {
            $whereConditions[] = "m.stock_on_hand <= m.reorder_level";
        }

        if (!empty($filters['active_only'])) {
            $whereConditions[] = "m.is_active = 1";
        }

        $where = implode(' AND ', $whereConditions);

        // Validate sort column to prevent SQL injection
        $allowedSorts = [
            'item_name' => 'm.item_name',
            'supplier_name' => 's.name',
            'manufacturers_code' => 'm.manufacturers_code',
            'cost_excl' => 'm.cost_excl',
            'sell_price' => 'm.sell_price',
            'stock_on_hand' => 'm.stock_on_hand'
        ];
        $sortColumn = $allowedSorts[$sort] ?? 'm.item_name';
        $sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

        return $this->db->fetchAll("
            SELECT m.*,
                   s.name as supplier_name,
                   u1.username as created_by_name,
                   u2.username as updated_by_name
            FROM materials m
            LEFT JOIN suppliers s ON m.supplier_id = s.id
            LEFT JOIN users u1 ON m.created_by = u1.id
            LEFT JOIN users u2 ON m.updated_by = u2.id
            WHERE $where
            ORDER BY $sortColumn $sortDir
        ", $params);
    }

    public function getById($id) {
        return $this->db->fetchOne("
            SELECT m.*,
                   s.name as supplier_name,
                   u1.username as created_by_name,
                   u2.username as updated_by_name
            FROM materials m
            LEFT JOIN suppliers s ON m.supplier_id = s.id
            LEFT JOIN users u1 ON m.created_by = u1.id
            LEFT JOIN users u2 ON m.updated_by = u2.id
            WHERE m.id = :id
        ", ['id' => $id]);
    }

    public function create($data) {
        $data['created_by'] = Auth::getCurrentUserId();
        $data['updated_by'] = Auth::getCurrentUserId();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }

        // Calculate GST and cost_incl if not provided
        if (isset($data['cost_excl']) && !isset($data['gst'])) {
            $gstRate = $this->getGstRate();
            $data['gst'] = round($data['cost_excl'] * ($gstRate / 100), 2);
            $data['cost_incl'] = round($data['cost_excl'] + $data['gst'], 2);
        }

        $id = $this->db->insert('materials', $data);

        if ($id) {
            Auth::logAudit('materials', $id, 'INSERT', $data);
        }

        return $id;
    }

    public function update($id, $data) {
        $data['updated_by'] = Auth::getCurrentUserId();
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Recalculate GST if cost_excl changed
        if (isset($data['cost_excl'])) {
            $gstRate = $this->getGstRate();
            $data['gst'] = round($data['cost_excl'] * ($gstRate / 100), 2);
            $data['cost_incl'] = round($data['cost_excl'] + $data['gst'], 2);
        }

        // Get old data for audit
        $oldData = $this->getById($id);

        $result = $this->db->update('materials', $data, 'id = :id', ['id' => $id]);

        if ($result) {
            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = ['old' => $oldData[$key], 'new' => $value];
                }
            }
            Auth::logAudit('materials', $id, 'UPDATE', $changes);
        }

        return $result;
    }

    public function delete($id) {
        // Check for usage in quotes
        $quoteCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM quote_materials WHERE material_id = :id",
            ['id' => $id]
        )['count'];

        if ($quoteCount > 0) {
            return [
                'success' => false,
                'message' => "This material is used in $quoteCount quote(s). Cannot delete.",
                'quoteCount' => $quoteCount
            ];
        }

        // Check for usage in invoices
        $invoiceCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM invoice_materials WHERE material_id = :id",
            ['id' => $id]
        )['count'];

        if ($invoiceCount > 0) {
            return [
                'success' => false,
                'message' => "This material is used in $invoiceCount invoice(s). Cannot delete.",
                'invoiceCount' => $invoiceCount
            ];
        }

        $result = $this->db->delete('materials', 'id = :id', ['id' => $id]);

        if ($result) {
            Auth::logAudit('materials', $id, 'DELETE', ['id' => $id]);
        }

        return ['success' => (bool)$result];
    }

    public function search($query, $limit = 20) {
        return $this->db->fetchAll("
            SELECT m.*, s.name as supplier_name
            FROM materials m
            LEFT JOIN suppliers s ON m.supplier_id = s.id
            WHERE m.is_active = 1
              AND (m.item_name LIKE :query
                   OR m.manufacturers_code LIKE :query
                   OR s.name LIKE :query)
            ORDER BY m.item_name ASC
            LIMIT :limit
        ", ['query' => "%$query%", 'limit' => $limit]);
    }

    public function getLowStock() {
        return $this->db->fetchAll("
            SELECT m.*, s.name as supplier_name
            FROM materials m
            LEFT JOIN suppliers s ON m.supplier_id = s.id
            WHERE m.is_active = 1
              AND m.stock_on_hand <= m.reorder_level
            ORDER BY (m.reorder_level - m.stock_on_hand) DESC, m.item_name ASC
        ");
    }

    public function getNeedingReorder() {
        return $this->db->fetchAll("
            SELECT m.*, s.name as supplier_name,
                   (m.reorder_level - m.stock_on_hand) as shortfall
            FROM materials m
            LEFT JOIN suppliers s ON m.supplier_id = s.id
            WHERE m.is_active = 1
              AND m.stock_on_hand < m.reorder_level
            ORDER BY shortfall DESC, m.item_name ASC
        ");
    }

    public function adjustStock($id, $quantityChange, $movementType, $referenceType = null, $referenceId = null, $notes = null) {
        $material = $this->getById($id);
        if (!$material) {
            return false;
        }

        $stockBefore = $material['stock_on_hand'];
        $stockAfter = $stockBefore + $quantityChange;

        // Update material stock
        $this->db->update(
            'materials',
            [
                'stock_on_hand' => $stockAfter,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => Auth::getCurrentUserId()
            ],
            'id = :id',
            ['id' => $id]
        );

        // Record stock movement
        $this->db->insert('stock_movements', [
            'material_id' => $id,
            'movement_type' => $movementType,
            'quantity_change' => $quantityChange,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => Auth::getCurrentUserId()
        ]);

        Auth::logAudit('materials', $id, 'STOCK_ADJUST', [
            'quantity_change' => $quantityChange,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'movement_type' => $movementType
        ]);

        return $stockAfter;
    }

    public function getBySupplier($supplierId) {
        return $this->db->fetchAll("
            SELECT m.*
            FROM materials m
            WHERE m.supplier_id = :supplier_id
            ORDER BY m.item_name ASC
        ", ['supplier_id' => $supplierId]);
    }

    public function getStockMovements($materialId, $limit = 50) {
        return $this->db->fetchAll("
            SELECT sm.*, u.username as created_by_name
            FROM stock_movements sm
            LEFT JOIN users u ON sm.created_by = u.id
            WHERE sm.material_id = :material_id
            ORDER BY sm.created_at DESC
            LIMIT :limit
        ", ['material_id' => $materialId, 'limit' => $limit]);
    }

    private function getGstRate() {
        $result = $this->db->fetchOne(
            "SELECT setting_value FROM settings WHERE setting_key = 'gst_rate'"
        );
        return $result ? (float)$result['setting_value'] : 15;
    }
}
