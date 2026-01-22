<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

class FoamGrade {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll($activeOnly = false) {
        $whereClause = $activeOnly ? "WHERE fg.is_active = 1" : "";

        return $this->db->fetchAll("
            SELECT fg.*,
                   COUNT(DISTINCT fp.id) as product_count
            FROM foam_grades fg
            LEFT JOIN foam_products fp ON fg.id = fp.grade_id
            $whereClause
            GROUP BY fg.id
            ORDER BY fg.grade_code ASC
        ");
    }

    public function getActive() {
        return $this->getAll(true);
    }

    public function getById($id) {
        return $this->db->fetchOne("
            SELECT fg.*
            FROM foam_grades fg
            WHERE fg.id = :id
        ", ['id' => $id]);
    }

    public function getByCode($code) {
        return $this->db->fetchOne("
            SELECT fg.*
            FROM foam_grades fg
            WHERE fg.grade_code = :code
        ", ['code' => $code]);
    }

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }

        $id = $this->db->insert('foam_grades', $data);

        if ($id) {
            Auth::logAudit('foam_grades', $id, 'INSERT', $data);
        }

        return $id;
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');

        $oldData = $this->getById($id);

        $result = $this->db->update('foam_grades', $data, 'id = :id', ['id' => $id]);

        if ($result) {
            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = ['old' => $oldData[$key], 'new' => $value];
                }
            }
            Auth::logAudit('foam_grades', $id, 'UPDATE', $changes);
        }

        return $result;
    }

    public function delete($id) {
        // Check for associated products
        $productCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM foam_products WHERE grade_id = :id",
            ['id' => $id]
        )['count'];

        if ($productCount > 0) {
            return [
                'success' => false,
                'message' => "This foam grade has $productCount associated product(s). Delete them first.",
                'productCount' => $productCount
            ];
        }

        $result = $this->db->delete('foam_grades', 'id = :id', ['id' => $id]);

        if ($result) {
            Auth::logAudit('foam_grades', $id, 'DELETE', ['id' => $id]);
        }

        return ['success' => (bool)$result];
    }

    public function getWithProducts($activeOnly = false) {
        $grades = $this->getAll($activeOnly);

        foreach ($grades as &$grade) {
            $whereClause = $activeOnly ? "AND fp.is_active = 1" : "";
            $grade['products'] = $this->db->fetchAll("
                SELECT fp.*
                FROM foam_products fp
                WHERE fp.grade_id = :grade_id $whereClause
                ORDER BY
                    CAST(REPLACE(fp.thickness, 'mm', '') AS INTEGER) ASC
            ", ['grade_id' => $grade['id']]);
        }

        return $grades;
    }
}
