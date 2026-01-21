<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

class Quote {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all quotes with optional filters
     */
    public function getAll($filters = []) {
        $whereConditions = ["1=1"];
        $params = [];

        if (!empty($filters['client_id'])) {
            $whereConditions[] = "q.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }

        if (!empty($filters['project_id'])) {
            $whereConditions[] = "q.project_id = :project_id";
            $params['project_id'] = $filters['project_id'];
        }

        if (!empty($filters['status'])) {
            $whereConditions[] = "q.status = :status";
            $params['status'] = $filters['status'];
        } elseif (empty($filters['include_archived'])) {
            // By default, exclude archived quotes unless explicitly requested
            $whereConditions[] = "q.status != 'archived'";
        }

        if (!empty($filters['date_from'])) {
            $whereConditions[] = "q.quote_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = "q.quote_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $whereConditions[] = "(q.quote_number LIKE :search OR c.name LIKE :search)";
            $params['search'] = "%" . $filters['search'] . "%";
        }

        $where = implode(' AND ', $whereConditions);

        return $this->db->fetchAll("
            SELECT q.*,
                   c.name as client_name,
                   c.email as client_email,
                   p.title as project_title,
                   u1.username as created_by_name,
                   u2.username as updated_by_name
            FROM quotes q
            LEFT JOIN clients c ON q.client_id = c.id
            LEFT JOIN projects p ON q.project_id = p.id
            LEFT JOIN users u1 ON q.created_by = u1.id
            LEFT JOIN users u2 ON q.updated_by = u2.id
            WHERE $where
            ORDER BY q.quote_number DESC, q.revision DESC
        ", $params);
    }

    /**
     * Get a single quote by ID with all details
     */
    public function getById($id) {
        $quote = $this->db->fetchOne("
            SELECT q.*,
                   c.name as client_name,
                   c.email as client_email,
                   c.phone as client_phone,
                   c.address as client_address,
                   p.title as project_title,
                   u1.username as created_by_name,
                   u2.username as updated_by_name
            FROM quotes q
            LEFT JOIN clients c ON q.client_id = c.id
            LEFT JOIN projects p ON q.project_id = p.id
            LEFT JOIN users u1 ON q.created_by = u1.id
            LEFT JOIN users u2 ON q.updated_by = u2.id
            WHERE q.id = :id
        ", ['id' => $id]);

        if ($quote) {
            $quote['materials'] = $this->getMaterials($id);
            $quote['misc_items'] = $this->getMiscItems($id);
        }

        return $quote;
    }

    /**
     * Generate the next quote number in format Q{YEAR}-{NNNN}
     */
    public function generateQuoteNumber() {
        $year = date('Y');

        // Get or create sequence for this year
        $sequence = $this->db->fetchOne(
            "SELECT * FROM document_sequences WHERE document_type = 'quote' AND year = :year",
            ['year' => $year]
        );

        if ($sequence) {
            $nextNumber = $sequence['last_number'] + 1;
            $this->db->update(
                'document_sequences',
                ['last_number' => $nextNumber],
                'id = :id',
                ['id' => $sequence['id']]
            );
        } else {
            $nextNumber = 1;
            $this->db->insert('document_sequences', [
                'document_type' => 'quote',
                'year' => $year,
                'last_number' => $nextNumber
            ]);
        }

        return sprintf('Q%d-%04d', $year, $nextNumber);
    }

    /**
     * Create a new quote
     */
    public function create($data) {
        // Generate quote number if not provided
        if (empty($data['quote_number'])) {
            $data['quote_number'] = $this->generateQuoteNumber();
        }

        // Set defaults
        $data['revision'] = 1;
        $data['status'] = $data['status'] ?? 'draft';
        $data['created_by'] = Auth::getCurrentUserId();
        $data['updated_by'] = Auth::getCurrentUserId();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Get labour rate from settings if not provided
        if (empty($data['labour_rate'])) {
            $data['labour_rate'] = $this->getLabourRate($data['labour_rate_type'] ?? 'standard');
        }

        // Set expiry date if not provided
        if (empty($data['expiry_date']) && !empty($data['quote_date'])) {
            $validityDays = $this->getSetting('quote_validity_days', 30);
            $data['expiry_date'] = date('Y-m-d', strtotime($data['quote_date'] . " + $validityDays days"));
        }

        $id = $this->db->insert('quotes', $data);

        if ($id) {
            // Initialize misc items from defaults
            $this->initializeMiscItems($id);

            // Calculate totals (labour and misc)
            $this->calculateTotals($id);

            Auth::logAudit('quotes', $id, 'INSERT', $data);
        }

        return $id;
    }

    /**
     * Update an existing quote
     */
    public function update($id, $data) {
        $data['updated_by'] = Auth::getCurrentUserId();
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Update labour rate if type changed
        if (isset($data['labour_rate_type'])) {
            $data['labour_rate'] = $this->getLabourRate($data['labour_rate_type']);
        }

        // Get old data for audit
        $oldData = $this->getById($id);

        $result = $this->db->update('quotes', $data, 'id = :id', ['id' => $id]);

        if ($result) {
            // Recalculate totals if labour values changed
            $labourFields = ['labour_stripping', 'labour_patterns', 'labour_cutting',
                           'labour_sewing', 'labour_upholstery', 'labour_assembly',
                           'labour_handling', 'labour_rate_type'];
            $labourChanged = false;
            foreach ($labourFields as $field) {
                if (isset($data[$field])) {
                    $labourChanged = true;
                    break;
                }
            }
            if ($labourChanged) {
                $this->calculateTotals($id);
            }

            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = ['old' => $oldData[$key], 'new' => $value];
                }
            }
            Auth::logAudit('quotes', $id, 'UPDATE', $changes);
        }

        return $result;
    }

    /**
     * Delete a quote (only drafts can be deleted)
     */
    public function delete($id) {
        $quote = $this->getById($id);

        if (!$quote) {
            return ['success' => false, 'message' => 'Quote not found'];
        }

        if ($quote['status'] !== 'draft') {
            return ['success' => false, 'message' => 'Only draft quotes can be deleted'];
        }

        // Materials and misc items will be deleted via CASCADE
        $result = $this->db->delete('quotes', 'id = :id', ['id' => $id]);

        if ($result) {
            Auth::logAudit('quotes', $id, 'DELETE', ['quote_number' => $quote['quote_number']]);
        }

        return ['success' => (bool)$result];
    }

    /**
     * Create a new revision of an existing quote
     */
    public function createRevision($quoteId) {
        $original = $this->getById($quoteId);

        if (!$original) {
            return ['success' => false, 'message' => 'Original quote not found'];
        }

        // Prepare new quote data
        $newData = [
            'quote_number' => $original['quote_number'],
            'revision' => $original['revision'] + 1,
            'client_id' => $original['client_id'],
            'project_id' => $original['project_id'],
            'quote_date' => date('Y-m-d'),
            'special_instructions' => $original['special_instructions'],
            'status' => 'draft',
            'labour_stripping' => $original['labour_stripping'],
            'labour_patterns' => $original['labour_patterns'],
            'labour_cutting' => $original['labour_cutting'],
            'labour_sewing' => $original['labour_sewing'],
            'labour_upholstery' => $original['labour_upholstery'],
            'labour_assembly' => $original['labour_assembly'],
            'labour_handling' => $original['labour_handling'],
            'labour_rate_type' => $original['labour_rate_type'],
            'labour_rate' => $this->getLabourRate($original['labour_rate_type']),
        ];

        // Set expiry date
        $validityDays = $this->getSetting('quote_validity_days', 30);
        $newData['expiry_date'] = date('Y-m-d', strtotime("+ $validityDays days"));

        $newData['created_by'] = Auth::getCurrentUserId();
        $newData['updated_by'] = Auth::getCurrentUserId();
        $newData['created_at'] = date('Y-m-d H:i:s');
        $newData['updated_at'] = date('Y-m-d H:i:s');

        $newId = $this->db->insert('quotes', $newData);

        if ($newId) {
            // Copy materials
            foreach ($original['materials'] as $material) {
                $this->db->insert('quote_materials', [
                    'quote_id' => $newId,
                    'material_id' => $material['material_id'],
                    'item_description' => $material['item_description'],
                    'quantity' => $material['quantity'],
                    'unit_cost' => $material['unit_cost'],
                    'line_total' => $material['line_total'],
                    'sort_order' => $material['sort_order']
                ]);
            }

            // Copy misc items
            foreach ($original['misc_items'] as $misc) {
                $this->db->insert('quote_misc', [
                    'quote_id' => $newId,
                    'misc_material_id' => $misc['misc_material_id'],
                    'name' => $misc['name'],
                    'price' => $misc['price'],
                    'included' => $misc['included']
                ]);
            }

            // Calculate totals
            $this->calculateTotals($newId);

            Auth::logAudit('quotes', $newId, 'INSERT', ['revision_of' => $quoteId]);

            return ['success' => true, 'id' => $newId, 'revision' => $newData['revision']];
        }

        return ['success' => false, 'message' => 'Failed to create revision'];
    }

    /**
     * Get materials for a quote
     */
    public function getMaterials($quoteId) {
        return $this->db->fetchAll("
            SELECT qm.*, m.manufacturers_code, m.unit_of_measure, s.name as supplier_name
            FROM quote_materials qm
            LEFT JOIN materials m ON qm.material_id = m.id
            LEFT JOIN suppliers s ON m.supplier_id = s.id
            WHERE qm.quote_id = :quote_id
            ORDER BY qm.sort_order ASC
        ", ['quote_id' => $quoteId]);
    }

    /**
     * Add a material to a quote
     */
    public function addMaterial($quoteId, $data) {
        // Get current max sort order
        $maxOrder = $this->db->fetchOne(
            "SELECT MAX(sort_order) as max_order FROM quote_materials WHERE quote_id = :quote_id",
            ['quote_id' => $quoteId]
        );
        $sortOrder = ($maxOrder['max_order'] ?? 0) + 1;

        $lineTotal = ($data['quantity'] ?? 1) * ($data['unit_cost'] ?? 0);

        $id = $this->db->insert('quote_materials', [
            'quote_id' => $quoteId,
            'material_id' => $data['material_id'] ?? null,
            'item_description' => $data['item_description'],
            'quantity' => $data['quantity'] ?? 1,
            'unit_cost' => $data['unit_cost'] ?? 0,
            'line_total' => $lineTotal,
            'sort_order' => $sortOrder
        ]);

        if ($id) {
            $this->calculateTotals($quoteId);
        }

        return $id;
    }

    /**
     * Update a material line item
     */
    public function updateMaterial($lineId, $data) {
        if (isset($data['quantity']) && isset($data['unit_cost'])) {
            $data['line_total'] = $data['quantity'] * $data['unit_cost'];
        }

        $result = $this->db->update('quote_materials', $data, 'id = :id', ['id' => $lineId]);

        if ($result) {
            // Get quote_id for recalculation
            $line = $this->db->fetchOne("SELECT quote_id FROM quote_materials WHERE id = :id", ['id' => $lineId]);
            if ($line) {
                $this->calculateTotals($line['quote_id']);
            }
        }

        return $result;
    }

    /**
     * Remove a material from a quote
     */
    public function removeMaterial($lineId) {
        $line = $this->db->fetchOne("SELECT quote_id FROM quote_materials WHERE id = :id", ['id' => $lineId]);

        $result = $this->db->delete('quote_materials', 'id = :id', ['id' => $lineId]);

        if ($result && $line) {
            $this->calculateTotals($line['quote_id']);
        }

        return $result;
    }

    /**
     * Clear all materials from a quote
     */
    public function clearMaterials($quoteId) {
        return $this->db->delete('quote_materials', 'quote_id = :quote_id', ['quote_id' => $quoteId]);
    }

    /**
     * Get misc items for a quote
     */
    public function getMiscItems($quoteId) {
        return $this->db->fetchAll("
            SELECT *
            FROM quote_misc
            WHERE quote_id = :quote_id
            ORDER BY name ASC
        ", ['quote_id' => $quoteId]);
    }

    /**
     * Initialize misc items from default misc materials
     */
    private function initializeMiscItems($quoteId) {
        $defaults = $this->db->fetchAll("SELECT * FROM misc_materials WHERE is_active = 1");

        foreach ($defaults as $misc) {
            $this->db->insert('quote_misc', [
                'quote_id' => $quoteId,
                'misc_material_id' => $misc['id'],
                'name' => $misc['name'],
                'price' => $misc['fixed_price'],
                'quantity' => 1,
                'included' => 0  // Default to unchecked
            ]);
        }
    }

    /**
     * Update misc item by misc_material_id
     */
    public function updateMiscItemByMaterialId($quoteId, $miscMaterialId, $included, $quantity = 1, $price = null) {
        $data = [
            'included' => $included ? 1 : 0,
            'quantity' => $quantity
        ];

        if ($price !== null) {
            $data['price'] = $price;
        }

        return $this->db->update(
            'quote_misc',
            $data,
            'quote_id = :quote_id AND misc_material_id = :misc_material_id',
            ['quote_id' => $quoteId, 'misc_material_id' => $miscMaterialId]
        );
    }

    /**
     * Update misc item inclusion status
     */
    public function updateMiscItem($quoteId, $miscId, $included) {
        $result = $this->db->update(
            'quote_misc',
            ['included' => $included ? 1 : 0],
            'quote_id = :quote_id AND id = :id',
            ['quote_id' => $quoteId, 'id' => $miscId]
        );

        if ($result) {
            $this->calculateTotals($quoteId);
        }

        return $result;
    }

    /**
     * Calculate and save quote totals
     */
    public function calculateTotals($quoteId) {
        $quote = $this->db->fetchOne("SELECT * FROM quotes WHERE id = :id", ['id' => $quoteId]);

        if (!$quote) {
            return false;
        }

        // Calculate materials total
        $materialsResult = $this->db->fetchOne(
            "SELECT COALESCE(SUM(line_total), 0) as total FROM quote_materials WHERE quote_id = :id",
            ['id' => $quoteId]
        );
        $subtotalMaterials = (float)$materialsResult['total'];

        // Calculate misc total (only included items, with quantity)
        $miscResult = $this->db->fetchOne(
            "SELECT COALESCE(SUM(price * COALESCE(quantity, 1)), 0) as total FROM quote_misc WHERE quote_id = :id AND included = 1",
            ['id' => $quoteId]
        );
        $subtotalMisc = (float)$miscResult['total'];

        // Calculate labour total
        $totalMinutes = (int)$quote['labour_stripping'] +
                        (int)$quote['labour_patterns'] +
                        (int)$quote['labour_cutting'] +
                        (int)$quote['labour_sewing'] +
                        (int)$quote['labour_upholstery'] +
                        (int)$quote['labour_assembly'] +
                        (int)$quote['labour_handling'];

        $totalHours = $totalMinutes / 60;
        $labourRate = (float)$quote['labour_rate'];
        $subtotalLabour = round($totalHours * $labourRate, 2);

        // Calculate totals
        $totalExclGst = $subtotalMaterials + $subtotalMisc + $subtotalLabour;
        $gstRate = (float)$this->getSetting('gst_rate', 15);
        $gstAmount = round($totalExclGst * ($gstRate / 100), 2);
        $totalInclGst = $totalExclGst + $gstAmount;

        // Update quote
        return $this->db->update('quotes', [
            'subtotal_materials' => $subtotalMaterials,
            'subtotal_misc' => $subtotalMisc,
            'subtotal_labour' => $subtotalLabour,
            'total_excl_gst' => $totalExclGst,
            'gst_amount' => $gstAmount,
            'total_incl_gst' => $totalInclGst,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $quoteId]);
    }

    /**
     * Update quote status
     */
    public function updateStatus($id, $status) {
        $validStatuses = ['draft', 'sent', 'accepted', 'declined', 'expired', 'invoiced', 'archived'];

        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $result = $this->update($id, ['status' => $status]);

        return ['success' => (bool)$result];
    }

    /**
     * Archive a quote
     */
    public function archive($id) {
        $quote = $this->getById($id);
        if (!$quote) {
            return ['success' => false, 'message' => 'Quote not found'];
        }

        // Store original status before archiving
        $result = $this->db->update('quotes', [
            'status' => 'archived',
            'previous_status' => $quote['status'],
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => Auth::getCurrentUserId()
        ], 'id = :id', ['id' => $id]);

        if ($result) {
            Auth::logAudit('quotes', $id, 'UPDATE', [
                'status' => ['old' => $quote['status'], 'new' => 'archived']
            ]);
        }

        return ['success' => (bool)$result];
    }

    /**
     * Unarchive a quote (restore to previous status)
     */
    public function unarchive($id) {
        $quote = $this->db->fetchOne("SELECT * FROM quotes WHERE id = :id", ['id' => $id]);
        if (!$quote) {
            return ['success' => false, 'message' => 'Quote not found'];
        }

        if ($quote['status'] !== 'archived') {
            return ['success' => false, 'message' => 'Quote is not archived'];
        }

        // Restore to previous status, or 'draft' if none stored
        $restoreStatus = $quote['previous_status'] ?? 'draft';

        $result = $this->db->update('quotes', [
            'status' => $restoreStatus,
            'previous_status' => null,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => Auth::getCurrentUserId()
        ], 'id = :id', ['id' => $id]);

        if ($result) {
            Auth::logAudit('quotes', $id, 'UPDATE', [
                'status' => ['old' => 'archived', 'new' => $restoreStatus]
            ]);
        }

        return ['success' => (bool)$result];
    }

    /**
     * Link quote to a project
     */
    public function linkToProject($quoteId, $projectId) {
        return $this->update($quoteId, ['project_id' => $projectId]);
    }

    /**
     * Get quotes by client
     */
    public function getByClient($clientId) {
        return $this->getAll(['client_id' => $clientId]);
    }

    /**
     * Get quotes by project
     */
    public function getByProject($projectId) {
        return $this->getAll(['project_id' => $projectId]);
    }

    /**
     * Get quotes expiring soon
     */
    public function getExpiringSoon($days = 7) {
        $futureDate = date('Y-m-d', strtotime("+ $days days"));

        return $this->db->fetchAll("
            SELECT q.*, c.name as client_name
            FROM quotes q
            LEFT JOIN clients c ON q.client_id = c.id
            WHERE q.status = 'sent'
              AND q.expiry_date <= :future_date
              AND q.expiry_date >= :today
            ORDER BY q.expiry_date ASC
        ", ['future_date' => $futureDate, 'today' => date('Y-m-d')]);
    }

    /**
     * Get labour rate from settings
     */
    private function getLabourRate($type = 'standard') {
        $key = $type === 'premium' ? 'labour_rate_premium' : 'labour_rate_standard';
        return (float)$this->getSetting($key, $type === 'premium' ? 95 : 75);
    }

    /**
     * Get setting value
     */
    private function getSetting($key, $default = '') {
        $result = $this->db->fetchOne(
            "SELECT setting_value FROM settings WHERE setting_key = :key",
            ['key' => $key]
        );
        return $result ? $result['setting_value'] : $default;
    }

    /**
     * Get quote status counts for dashboard
     */
    public function getStatusCounts() {
        return $this->db->fetchAll("
            SELECT status, COUNT(*) as count
            FROM quotes
            GROUP BY status
        ");
    }
}
