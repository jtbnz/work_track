<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

class Supplier {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll($activeOnly = false) {
        $whereClause = $activeOnly ? "WHERE s.is_active = 1" : "";

        return $this->db->fetchAll("
            SELECT s.*,
                   COUNT(DISTINCT m.id) as material_count,
                   u1.username as created_by_name,
                   u2.username as updated_by_name
            FROM suppliers s
            LEFT JOIN materials m ON s.id = m.supplier_id
            LEFT JOIN users u1 ON s.created_by = u1.id
            LEFT JOIN users u2 ON s.updated_by = u2.id
            $whereClause
            GROUP BY s.id
            ORDER BY s.name ASC
        ");
    }

    public function getActive() {
        return $this->getAll(true);
    }

    public function getById($id) {
        return $this->db->fetchOne("
            SELECT s.*,
                   u1.username as created_by_name,
                   u2.username as updated_by_name
            FROM suppliers s
            LEFT JOIN users u1 ON s.created_by = u1.id
            LEFT JOIN users u2 ON s.updated_by = u2.id
            WHERE s.id = :id
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

        $id = $this->db->insert('suppliers', $data);

        if ($id) {
            Auth::logAudit('suppliers', $id, 'INSERT', $data);
        }

        return $id;
    }

    public function update($id, $data) {
        $data['updated_by'] = Auth::getCurrentUserId();
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Get old data for audit
        $oldData = $this->getById($id);

        $result = $this->db->update('suppliers', $data, 'id = :id', ['id' => $id]);

        if ($result) {
            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = ['old' => $oldData[$key], 'new' => $value];
                }
            }
            Auth::logAudit('suppliers', $id, 'UPDATE', $changes);
        }

        return $result;
    }

    public function delete($id) {
        // Check for associated materials
        $materialCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM materials WHERE supplier_id = :id",
            ['id' => $id]
        )['count'];

        if ($materialCount > 0) {
            return [
                'success' => false,
                'message' => "This supplier has $materialCount associated material(s). Please reassign or delete them first.",
                'materialCount' => $materialCount
            ];
        }

        $result = $this->db->delete('suppliers', 'id = :id', ['id' => $id]);

        if ($result) {
            Auth::logAudit('suppliers', $id, 'DELETE', ['id' => $id]);
        }

        return ['success' => (bool)$result];
    }

    public function search($query) {
        return $this->db->fetchAll("
            SELECT s.*, COUNT(DISTINCT m.id) as material_count
            FROM suppliers s
            LEFT JOIN materials m ON s.id = m.supplier_id
            WHERE s.name LIKE :query
               OR s.contact_name LIKE :query
               OR s.email LIKE :query
               OR s.phone LIKE :query
            GROUP BY s.id
            ORDER BY s.name ASC
        ", ['query' => "%$query%"]);
    }

    public function getMaterials($supplierId) {
        return $this->db->fetchAll("
            SELECT m.*
            FROM materials m
            WHERE m.supplier_id = :supplier_id
            ORDER BY m.item_name ASC
        ", ['supplier_id' => $supplierId]);
    }

    public function findOrCreateByName($name) {
        // Check if supplier exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM suppliers WHERE name = :name",
            ['name' => $name]
        );

        if ($existing) {
            return $existing['id'];
        }

        // Create new supplier
        return $this->create(['name' => $name]);
    }
}
