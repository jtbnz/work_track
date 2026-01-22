<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

class FoamProduct {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll($gradeId = null, $activeOnly = false) {
        $where = [];
        $params = [];

        if ($gradeId !== null) {
            $where[] = "fp.grade_id = :grade_id";
            $params['grade_id'] = $gradeId;
        }

        if ($activeOnly) {
            $where[] = "fp.is_active = 1";
            $where[] = "fg.is_active = 1";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        return $this->db->fetchAll("
            SELECT fp.*,
                   fg.grade_code,
                   fg.description as grade_description
            FROM foam_products fp
            JOIN foam_grades fg ON fp.grade_id = fg.id
            $whereClause
            ORDER BY fg.grade_code ASC,
                     CAST(REPLACE(fp.thickness, 'mm', '') AS INTEGER) ASC
        ", $params);
    }

    public function getActive() {
        return $this->getAll(null, true);
    }

    public function getById($id) {
        return $this->db->fetchOne("
            SELECT fp.*,
                   fg.grade_code,
                   fg.description as grade_description
            FROM foam_products fp
            JOIN foam_grades fg ON fp.grade_id = fg.id
            WHERE fp.id = :id
        ", ['id' => $id]);
    }

    public function getByGradeAndThickness($gradeId, $thickness) {
        return $this->db->fetchOne("
            SELECT fp.*,
                   fg.grade_code
            FROM foam_products fp
            JOIN foam_grades fg ON fp.grade_id = fg.id
            WHERE fp.grade_id = :grade_id AND fp.thickness = :thickness
        ", ['grade_id' => $gradeId, 'thickness' => $thickness]);
    }

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }

        // Set default sheet area if not provided
        if (!isset($data['sheet_area'])) {
            $defaultArea = $this->db->fetchOne(
                "SELECT setting_value FROM settings WHERE setting_key = 'foam_default_sheet_area'"
            );
            $data['sheet_area'] = $defaultArea ? (float)$defaultArea['setting_value'] : 3.91;
        }

        $id = $this->db->insert('foam_products', $data);

        if ($id) {
            Auth::logAudit('foam_products', $id, 'INSERT', $data);
        }

        return $id;
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');

        $oldData = $this->getById($id);

        $result = $this->db->update('foam_products', $data, 'id = :id', ['id' => $id]);

        if ($result) {
            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = ['old' => $oldData[$key], 'new' => $value];
                }
            }
            Auth::logAudit('foam_products', $id, 'UPDATE', $changes);
        }

        return $result;
    }

    public function delete($id) {
        // Check for usage in quotes
        $quoteCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM quote_foam WHERE foam_product_id = :id",
            ['id' => $id]
        )['count'];

        if ($quoteCount > 0) {
            return [
                'success' => false,
                'message' => "This foam product is used in $quoteCount quote(s). Cannot delete.",
                'quoteCount' => $quoteCount
            ];
        }

        $result = $this->db->delete('foam_products', 'id = :id', ['id' => $id]);

        if ($result) {
            Auth::logAudit('foam_products', $id, 'DELETE', ['id' => $id]);
        }

        return ['success' => (bool)$result];
    }

    public function calculateCost($sheetCost, $sheetArea, $squareMeters, $cuttingRequired = false) {
        // Get settings
        $markup = $this->db->fetchOne(
            "SELECT setting_value FROM settings WHERE setting_key = 'foam_markup_multiplier'"
        );
        $markup = $markup ? (float)$markup['setting_value'] : 2;

        $cuttingFee = $this->db->fetchOne(
            "SELECT setting_value FROM settings WHERE setting_key = 'foam_cutting_fee_percent'"
        );
        $cuttingFee = $cuttingFee ? (float)$cuttingFee['setting_value'] : 15;

        // Cost per square meter (base cost)
        $costPerSqM = $sheetCost / $sheetArea;

        // Apply markup
        $markedUpCostPerSqM = $costPerSqM * $markup;

        // Calculate line total
        $lineTotal = $markedUpCostPerSqM * $squareMeters;

        // Apply cutting fee if required
        if ($cuttingRequired) {
            $lineTotal *= (1 + $cuttingFee / 100);
        }

        return [
            'unit_cost' => round($markedUpCostPerSqM, 2),
            'line_total' => round($lineTotal, 2),
            'markup' => $markup,
            'cutting_fee_percent' => $cuttingFee
        ];
    }
}
