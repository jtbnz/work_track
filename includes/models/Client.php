<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

class Client {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll() {
        return $this->db->fetchAll("
            SELECT c.*,
                   COUNT(DISTINCT p.id) as project_count,
                   u1.username as created_by_name,
                   u2.username as updated_by_name
            FROM clients c
            LEFT JOIN projects p ON c.id = p.client_id
            LEFT JOIN users u1 ON c.created_by = u1.id
            LEFT JOIN users u2 ON c.updated_by = u2.id
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
    }

    public function getById($id) {
        return $this->db->fetchOne("
            SELECT c.*,
                   u1.username as created_by_name,
                   u2.username as updated_by_name
            FROM clients c
            LEFT JOIN users u1 ON c.created_by = u1.id
            LEFT JOIN users u2 ON c.updated_by = u2.id
            WHERE c.id = :id
        ", ['id' => $id]);
    }

    public function create($data) {
        $data['created_by'] = Auth::getCurrentUserId();
        $data['updated_by'] = Auth::getCurrentUserId();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $id = $this->db->insert('clients', $data);

        if ($id) {
            Auth::logAudit('clients', $id, 'INSERT', $data);
        }

        return $id;
    }

    public function update($id, $data) {
        $data['updated_by'] = Auth::getCurrentUserId();
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Get old data for audit
        $oldData = $this->getById($id);

        $result = $this->db->update('clients', $data, 'id = :id', ['id' => $id]);

        if ($result) {
            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = ['old' => $oldData[$key], 'new' => $value];
                }
            }
            Auth::logAudit('clients', $id, 'UPDATE', $changes);
        }

        return $result;
    }

    public function delete($id) {
        // Check for associated projects
        $projectCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM projects WHERE client_id = :id",
            ['id' => $id]
        )['count'];

        if ($projectCount > 0) {
            return ['success' => false, 'message' => "This client has $projectCount associated project(s). Are you sure you want to delete?", 'confirm_required' => true];
        }

        $result = $this->db->delete('clients', 'id = :id', ['id' => $id]);

        if ($result) {
            Auth::logAudit('clients', $id, 'DELETE', ['id' => $id]);
        }

        return ['success' => $result];
    }

    public function getProjects($clientId) {
        return $this->db->fetchAll("
            SELECT p.*, ps.name as status_name, ps.color as status_color
            FROM projects p
            LEFT JOIN project_statuses ps ON p.status_id = ps.id
            WHERE p.client_id = :client_id
            ORDER BY p.created_date DESC
        ", ['client_id' => $clientId]);
    }

    public function search($query) {
        return $this->db->fetchAll("
            SELECT c.*, COUNT(DISTINCT p.id) as project_count
            FROM clients c
            LEFT JOIN projects p ON c.id = p.client_id
            WHERE c.name LIKE :query
               OR c.email LIKE :query
               OR c.phone LIKE :query
            GROUP BY c.id
            ORDER BY c.name ASC
        ", ['query' => "%$query%"]);
    }
}