<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

class ProjectStatus {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll($includeInactive = false) {
        $sql = "SELECT * FROM project_statuses";
        if (!$includeInactive) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC";

        return $this->db->fetchAll($sql);
    }

    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM project_statuses WHERE id = :id",
            ['id' => $id]
        );
    }

    public function create($data) {
        // Get next sort order
        $maxSort = $this->db->fetchOne(
            "SELECT MAX(sort_order) as max_sort FROM project_statuses"
        );
        $data['sort_order'] = ($maxSort['max_sort'] ?? 0) + 1;

        $id = $this->db->insert('project_statuses', $data);

        if ($id) {
            Auth::logAudit('project_statuses', $id, 'INSERT', $data);
        }

        return $id;
    }

    public function update($id, $data) {
        $oldData = $this->getById($id);
        $result = $this->db->update('project_statuses', $data, 'id = :id', ['id' => $id]);

        if ($result) {
            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = ['old' => $oldData[$key], 'new' => $value];
                }
            }
            Auth::logAudit('project_statuses', $id, 'UPDATE', $changes);
        }

        return $result;
    }

    public function delete($id) {
        // Check if any projects use this status
        $projectCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM projects WHERE status_id = :id",
            ['id' => $id]
        )['count'];

        if ($projectCount > 0) {
            return ['success' => false, 'message' => "This status is used by $projectCount project(s). Please reassign them first."];
        }

        $result = $this->db->delete('project_statuses', 'id = :id', ['id' => $id]);

        if ($result) {
            Auth::logAudit('project_statuses', $id, 'DELETE', ['id' => $id]);
        }

        return ['success' => $result];
    }

    public function reorder($statusIds) {
        $this->db->beginTransaction();

        try {
            foreach ($statusIds as $index => $statusId) {
                $this->db->update(
                    'project_statuses',
                    ['sort_order' => $index + 1],
                    'id = :id',
                    ['id' => $statusId]
                );
            }

            $this->db->commit();
            Auth::logAudit('project_statuses', 0, 'UPDATE', ['action' => 'reorder', 'order' => $statusIds]);
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function getUsageStats() {
        return $this->db->fetchAll("
            SELECT ps.*, COUNT(p.id) as project_count
            FROM project_statuses ps
            LEFT JOIN projects p ON ps.id = p.status_id
            GROUP BY ps.id
            ORDER BY ps.sort_order ASC
        ");
    }
}