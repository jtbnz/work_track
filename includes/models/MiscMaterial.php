<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

class MiscMaterial {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll($activeOnly = false) {
        $whereClause = $activeOnly ? "WHERE is_active = 1" : "";

        return $this->db->fetchAll("
            SELECT *
            FROM misc_materials
            $whereClause
            ORDER BY name ASC
        ");
    }

    public function getActive() {
        return $this->getAll(true);
    }

    public function getById($id) {
        return $this->db->fetchOne("
            SELECT *
            FROM misc_materials
            WHERE id = :id
        ", ['id' => $id]);
    }

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }

        $id = $this->db->insert('misc_materials', $data);

        if ($id) {
            Auth::logAudit('misc_materials', $id, 'INSERT', $data);
        }

        return $id;
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Get old data for audit
        $oldData = $this->getById($id);

        $result = $this->db->update('misc_materials', $data, 'id = :id', ['id' => $id]);

        if ($result) {
            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = ['old' => $oldData[$key], 'new' => $value];
                }
            }
            Auth::logAudit('misc_materials', $id, 'UPDATE', $changes);
        }

        return $result;
    }

    public function delete($id) {
        // Check for usage in quotes
        $quoteCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM quote_misc WHERE misc_material_id = :id",
            ['id' => $id]
        )['count'];

        if ($quoteCount > 0) {
            return [
                'success' => false,
                'message' => "This misc material is used in $quoteCount quote(s). Cannot delete.",
                'quoteCount' => $quoteCount
            ];
        }

        $result = $this->db->delete('misc_materials', 'id = :id', ['id' => $id]);

        if ($result) {
            Auth::logAudit('misc_materials', $id, 'DELETE', ['id' => $id]);
        }

        return ['success' => (bool)$result];
    }
}
