<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

class ProjectTemplate {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll() {
        return $this->db->fetchAll("
            SELECT * FROM project_templates
            ORDER BY is_default DESC, name ASC
        ");
    }

    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM project_templates WHERE id = :id",
            ['id' => $id]
        );
    }

    public function getDefault() {
        return $this->db->fetchOne(
            "SELECT * FROM project_templates WHERE is_default = 1 LIMIT 1"
        );
    }

    public function create($data) {
        // If this is being set as default, unset others
        if ($data['is_default']) {
            $this->db->query("UPDATE project_templates SET is_default = 0");
        }

        $id = $this->db->insert('project_templates', $data);

        if ($id) {
            Auth::logAudit('project_templates', $id, 'INSERT', $data);
        }

        return $id;
    }

    public function update($id, $data) {
        // If this is being set as default, unset others
        if (isset($data['is_default']) && $data['is_default']) {
            $this->db->query("UPDATE project_templates SET is_default = 0");
        }

        $oldData = $this->getById($id);
        $result = $this->db->update('project_templates', $data, 'id = :id', ['id' => $id]);

        if ($result) {
            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = ['old' => $oldData[$key], 'new' => $value];
                }
            }
            Auth::logAudit('project_templates', $id, 'UPDATE', $changes);
        }

        return $result;
    }

    public function delete($id) {
        // Check if this is the default template
        $template = $this->getById($id);
        if ($template && $template['is_default']) {
            return ['success' => false, 'message' => 'Cannot delete the default template. Please set another template as default first.'];
        }

        // Check usage
        $usageCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM projects WHERE template_id = :id",
            ['id' => $id]
        )['count'];

        $result = $this->db->delete('project_templates', 'id = :id', ['id' => $id]);

        if ($result) {
            Auth::logAudit('project_templates', $id, 'DELETE', [
                'id' => $id,
                'usage_count' => $usageCount
            ]);
        }

        return ['success' => $result];
    }

    public function getUsageStats() {
        return $this->db->fetchAll("
            SELECT pt.*, COUNT(p.id) as usage_count
            FROM project_templates pt
            LEFT JOIN projects p ON pt.id = p.template_id
            GROUP BY pt.id
            ORDER BY pt.is_default DESC, pt.name ASC
        ");
    }

    public function duplicate($id, $newName) {
        $template = $this->getById($id);
        if (!$template) {
            return false;
        }

        $newTemplate = [
            'name' => $newName,
            'default_title' => $template['default_title'],
            'default_details' => $template['default_details'],
            'default_fabric' => $template['default_fabric'],
            'is_default' => 0
        ];

        return $this->create($newTemplate);
    }
}