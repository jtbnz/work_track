<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

class Project {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = []) {
        $sql = "
            SELECT p.*,
                   c.name as client_name,
                   ps.name as status_name,
                   ps.color as status_color,
                   pt.name as template_name,
                   u1.username as created_by_name,
                   u2.username as updated_by_name
            FROM projects p
            LEFT JOIN clients c ON p.client_id = c.id
            LEFT JOIN project_statuses ps ON p.status_id = ps.id
            LEFT JOIN project_templates pt ON p.template_id = pt.id
            LEFT JOIN users u1 ON p.created_by = u1.id
            LEFT JOIN users u2 ON p.updated_by = u2.id
        ";

        $params = [];
        $conditions = [];

        if (!empty($filters['client_id'])) {
            $conditions[] = "p.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }

        if (!empty($filters['status_id'])) {
            $conditions[] = "p.status_id = :status_id";
            $params['status_id'] = $filters['status_id'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(p.title LIKE :search OR p.details LIKE :search OR p.fabric LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "p.start_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "p.completion_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        if ($conditions) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY p.created_date DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getById($id) {
        return $this->db->fetchOne("
            SELECT p.*,
                   c.name as client_name,
                   ps.name as status_name,
                   ps.color as status_color,
                   pt.name as template_name,
                   u1.username as created_by_name,
                   u2.username as updated_by_name
            FROM projects p
            LEFT JOIN clients c ON p.client_id = c.id
            LEFT JOIN project_statuses ps ON p.status_id = ps.id
            LEFT JOIN project_templates pt ON p.template_id = pt.id
            LEFT JOIN users u1 ON p.created_by = u1.id
            LEFT JOIN users u2 ON p.updated_by = u2.id
            WHERE p.id = :id
        ", ['id' => $id]);
    }

    public function create($data) {
        $data['created_by'] = Auth::getCurrentUserId();
        $data['updated_by'] = Auth::getCurrentUserId();
        $data['created_date'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $id = $this->db->insert('projects', $data);

        if ($id) {
            Auth::logAudit('projects', $id, 'INSERT', $data);
        }

        return $id;
    }

    public function update($id, $data) {
        $data['updated_by'] = Auth::getCurrentUserId();
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Get old data for audit
        $oldData = $this->getById($id);

        $result = $this->db->update('projects', $data, 'id = :id', ['id' => $id]);

        if ($result) {
            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = ['old' => $oldData[$key], 'new' => $value];
                }
            }
            Auth::logAudit('projects', $id, 'UPDATE', $changes);
        }

        return $result;
    }

    public function delete($id) {
        $result = $this->db->delete('projects', 'id = :id', ['id' => $id]);

        if ($result) {
            Auth::logAudit('projects', $id, 'DELETE', ['id' => $id]);
        }

        return $result;
    }

    public function getAttachments($projectId) {
        return $this->db->fetchAll("
            SELECT pa.*, u.username as uploaded_by_name
            FROM project_attachments pa
            LEFT JOIN users u ON pa.uploaded_by = u.id
            WHERE pa.project_id = :project_id
            ORDER BY pa.uploaded_at DESC
        ", ['project_id' => $projectId]);
    }

    public function addAttachment($projectId, $filename, $filePath, $fileSize, $mimeType) {
        $data = [
            'project_id' => $projectId,
            'filename' => $filename,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'uploaded_by' => Auth::getCurrentUserId(),
            'uploaded_at' => date('Y-m-d H:i:s')
        ];

        $id = $this->db->insert('project_attachments', $data);

        if ($id) {
            Auth::logAudit('project_attachments', $id, 'INSERT', [
                'project_id' => $projectId,
                'filename' => $filename
            ]);
        }

        return $id;
    }

    public function deleteAttachment($attachmentId) {
        $attachment = $this->db->fetchOne(
            "SELECT * FROM project_attachments WHERE id = :id",
            ['id' => $attachmentId]
        );

        if ($attachment) {
            // Delete file from filesystem
            if (file_exists($attachment['file_path'])) {
                unlink($attachment['file_path']);
            }

            $result = $this->db->delete('project_attachments', 'id = :id', ['id' => $attachmentId]);

            if ($result) {
                Auth::logAudit('project_attachments', $attachmentId, 'DELETE', [
                    'filename' => $attachment['filename'],
                    'project_id' => $attachment['project_id']
                ]);
            }

            return $result;
        }

        return false;
    }

    public function createFromTemplate($templateId, $overrides = []) {
        $template = $this->db->fetchOne(
            "SELECT * FROM project_templates WHERE id = :id",
            ['id' => $templateId]
        );

        if (!$template) {
            return false;
        }

        $projectData = [
            'title' => $template['default_title'] ?: 'New Project',
            'details' => $template['default_details'] ?: '',
            'fabric' => $template['default_fabric'] ?: '',
            'template_id' => $templateId,
            'status_id' => 1 // Default to first status
        ];

        // Apply overrides
        $projectData = array_merge($projectData, $overrides);

        return $this->create($projectData);
    }

    public function getCalendarData($startDate, $endDate) {
        return $this->db->fetchAll("
            SELECT p.id, p.title, p.start_date, p.completion_date,
                   c.name as client_name,
                   ps.name as status_name, ps.color as status_color
            FROM projects p
            LEFT JOIN clients c ON p.client_id = c.id
            LEFT JOIN project_statuses ps ON p.status_id = ps.id
            WHERE (p.start_date BETWEEN :start_date AND :end_date)
               OR (p.completion_date BETWEEN :start_date AND :end_date)
               OR (p.start_date <= :start_date AND p.completion_date >= :end_date)
            ORDER BY p.start_date ASC
        ", [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    public function getByStatus($statusId) {
        return $this->db->fetchAll("
            SELECT p.*, c.name as client_name, ps.color as status_color
            FROM projects p
            LEFT JOIN clients c ON p.client_id = c.id
            LEFT JOIN project_statuses ps ON p.status_id = ps.id
            WHERE p.status_id = :status_id
            ORDER BY p.start_date ASC
        ", ['status_id' => $statusId]);
    }

    public function updateDates($id, $startDate, $completionDate) {
        return $this->update($id, [
            'start_date' => $startDate,
            'completion_date' => $completionDate
        ]);
    }

    public function updateStatus($id, $statusId) {
        return $this->update($id, ['status_id' => $statusId]);
    }
}