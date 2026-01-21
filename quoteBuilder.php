<?php
// Process form BEFORE any output (required for redirects to work)
require_once 'includes/auth.php';
require_once 'includes/models/Quote.php';
require_once 'includes/models/Client.php';
require_once 'includes/models/Material.php';
require_once 'includes/models/MiscMaterial.php';

// Check authentication (redirect to login if not authenticated)
Auth::requireAuth();

$quoteModel = new Quote();
$clientModel = new Client();
$materialModel = new Material();
$miscMaterialModel = new MiscMaterial();

$message = '';
$messageType = '';
$isNew = true;
$isEditable = true;
$quote = null;

// Load existing quote if ID provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $quote = $quoteModel->getById((int)$_GET['id']);
    if ($quote) {
        $isNew = false;
        $isEditable = ($quote['status'] === 'draft');
    } else {
        $message = 'Quote not found (ID: ' . htmlspecialchars($_GET['id']) . ')';
        $messageType = 'danger';
    }
}

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isEditable) {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save' || $action === 'saveAndExit') {
        $data = [
            'client_id' => !empty($_POST['client_id']) ? $_POST['client_id'] : null,
            'project_id' => !empty($_POST['project_id']) ? $_POST['project_id'] : null,
            'quote_date' => $_POST['quote_date'] ?? date('Y-m-d'),
            'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
            'special_instructions' => $_POST['special_instructions'] ?? '',
            'labour_stripping' => (int)($_POST['labour_stripping'] ?? 0),
            'labour_patterns' => (int)($_POST['labour_patterns'] ?? 0),
            'labour_cutting' => (int)($_POST['labour_cutting'] ?? 0),
            'labour_sewing' => (int)($_POST['labour_sewing'] ?? 0),
            'labour_upholstery' => (int)($_POST['labour_upholstery'] ?? 0),
            'labour_assembly' => (int)($_POST['labour_assembly'] ?? 0),
            'labour_handling' => (int)($_POST['labour_handling'] ?? 0),
            'labour_rate_type' => $_POST['labour_rate_type'] ?? 'standard',
        ];

        // Get materials from form
        $materials = $_POST['materials'] ?? [];

        // Get misc items from form
        $miscItems = $_POST['misc'] ?? [];

        if ($isNew) {
            $quoteId = $quoteModel->create($data);
            if ($quoteId) {
                // Save materials
                foreach ($materials as $mat) {
                    $quoteModel->addMaterial($quoteId, [
                        'material_id' => $mat['material_id'] ?? null,
                        'item_description' => $mat['item_description'] ?? '',
                        'quantity' => (float)($mat['quantity'] ?? 1),
                        'unit_cost' => (float)($mat['unit_cost'] ?? 0)
                    ]);
                }

                // Save misc items (update the initialized ones)
                foreach ($miscItems as $misc) {
                    $quoteModel->updateMiscItemByMaterialId(
                        $quoteId,
                        $misc['misc_material_id'],
                        isset($misc['included']) ? 1 : 0,
                        (int)($misc['quantity'] ?? 1),
                        (float)($misc['price'] ?? 0)
                    );
                }

                // Recalculate totals
                $quoteModel->calculateTotals($quoteId);

                if ($action === 'saveAndExit') {
                    header('Location: quotes.php?saved=1');
                    exit;
                }
                header('Location: quoteBuilder.php?id=' . $quoteId . '&saved=1');
                exit;
            } else {
                $message = 'Failed to create quote.';
                $messageType = 'danger';
            }
        } else {
            $result = $quoteModel->update($quote['id'], $data);
            if ($result) {
                // Update materials - remove old ones and add new ones
                $quoteModel->clearMaterials($quote['id']);
                foreach ($materials as $mat) {
                    $quoteModel->addMaterial($quote['id'], [
                        'material_id' => $mat['material_id'] ?? null,
                        'item_description' => $mat['item_description'] ?? '',
                        'quantity' => (float)($mat['quantity'] ?? 1),
                        'unit_cost' => (float)($mat['unit_cost'] ?? 0)
                    ]);
                }

                // Update misc items
                foreach ($miscItems as $misc) {
                    $quoteModel->updateMiscItemByMaterialId(
                        $quote['id'],
                        $misc['misc_material_id'],
                        isset($misc['included']) ? 1 : 0,
                        (int)($misc['quantity'] ?? 1),
                        (float)($misc['price'] ?? 0)
                    );
                }

                // Recalculate totals
                $quoteModel->calculateTotals($quote['id']);

                if ($action === 'saveAndExit') {
                    header('Location: quotes.php?saved=1');
                    exit;
                }
                // Use Post-Redirect-Get pattern to prevent form resubmission on refresh
                header('Location: quoteBuilder.php?id=' . $quote['id'] . '&saved=1');
                exit;
            } else {
                $message = 'Failed to save quote.';
                $messageType = 'danger';
            }
        }
    }
}

// NOW include header (after all redirects are done)
$pageTitle = $isNew ? 'Quote Builder' : ($isEditable ? 'Edit Quote' : 'View Quote');
require_once 'includes/header.php';


// Check for saved message from redirect
if (isset($_GET['saved'])) {
    $message = 'Quote saved successfully!';
    $messageType = 'success';
}

// Get clients for dropdown
$clients = $clientModel->getAll();

// Get projects for the selected client (for linking)
$projects = [];
if ($quote && $quote['client_id']) {
    $projects = $clientModel->getProjects($quote['client_id']);
}

// Get misc materials for checkboxes
$miscItems = $quote ? $quote['misc_items'] : $miscMaterialModel->getActive();

// Labour rate settings
$db = Database::getInstance();
$standardRate = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'labour_rate_standard'");
$premiumRate = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'labour_rate_premium'");
$standardRate = $standardRate ? (float)$standardRate['setting_value'] : 75;
$premiumRate = $premiumRate ? (float)$premiumRate['setting_value'] : 95;
$gstRate = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'gst_rate'");
$gstRate = $gstRate ? (float)$gstRate['setting_value'] : 15;

// Get company name for email subject
$companyNameRow = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'company_name'");
$companyName = $companyNameRow ? $companyNameRow['setting_value'] : 'Our Company';
?>

<div class="page-header">
    <h1 class="page-title">
        <?php echo $isNew ? 'New Quote' : htmlspecialchars($quote['quote_number']); ?>
        <?php if (!$isNew && $quote['revision'] > 1): ?>
            <span class="badge badge-info">Rev <?php echo $quote['revision']; ?></span>
        <?php endif; ?>
        <?php if (!$isEditable): ?>
            <span class="badge" style="background-color: <?php echo getStatusColor($quote['status']); ?>; color: white;">
                <?php echo ucfirst($quote['status']); ?>
            </span>
        <?php endif; ?>
    </h1>
    <div class="page-actions">
        <a href="quotes.php" class="btn btn-secondary">Back to Quotes</a>
        <?php if (!$isNew): ?>
            <a href="api/quotePdf.php?id=<?php echo $quote['id']; ?>" class="btn btn-info" target="_blank">Download PDF</a>
            <button type="button" class="btn btn-info" onclick="openEmailModal()">Email Quote</button>
        <?php endif; ?>
        <?php if (!$isNew && $isEditable): ?>
            <button type="submit" form="quoteForm" name="action" value="save" class="btn btn-primary">Save</button>
            <button type="submit" form="quoteForm" name="action" value="saveAndExit" class="btn btn-success">Save & Exit</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>


<form id="quoteForm" method="POST" action="<?php echo $isNew ? 'quoteBuilder.php' : 'quoteBuilder.php?id=' . $quote['id']; ?>" class="quote-builder">
    <input type="hidden" name="action" value="save">

    <div class="quote-builder-grid">
        <!-- Left Column: Quote Details -->
        <div class="quote-section">
            <h3>Quote Details</h3>

            <div class="form-group">
                <label for="client_id">Client *</label>
                <div class="input-with-button">
                    <select name="client_id" id="client_id" class="form-control" <?php echo !$isEditable ? 'disabled' : ''; ?> required>
                        <option value="">Select Client...</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>"
                                <?php echo ($quote && $quote['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isEditable): ?>
                        <button type="button" class="btn btn-secondary btn-sm" id="newClientBtn" title="Add New Client">+</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quote_date">Quote Date</label>
                    <input type="date" name="quote_date" id="quote_date" class="form-control"
                        value="<?php echo $quote ? $quote['quote_date'] : date('Y-m-d'); ?>"
                        <?php echo !$isEditable ? 'disabled' : ''; ?>>
                </div>
                <div class="form-group">
                    <label for="expiry_date">Expiry Date</label>
                    <input type="date" name="expiry_date" id="expiry_date" class="form-control"
                        value="<?php echo $quote ? $quote['expiry_date'] : date('Y-m-d', strtotime('+30 days')); ?>"
                        <?php echo !$isEditable ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-group">
                <label for="project_id">Link to Project (Optional)</label>
                <select name="project_id" id="project_id" class="form-control" <?php echo !$isEditable ? 'disabled' : ''; ?>>
                    <option value="">No linked project</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>"
                            <?php echo ($quote && $quote['project_id'] == $project['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($project['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="special_instructions">Special Instructions</label>
                <textarea name="special_instructions" id="special_instructions" class="form-control" rows="3"
                    <?php echo !$isEditable ? 'disabled' : ''; ?>><?php echo htmlspecialchars($quote['special_instructions'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Right Column: Labour -->
        <div class="quote-section">
            <h3>Labour Breakdown</h3>

            <div class="form-group">
                <label for="labour_rate_type">Labour Rate</label>
                <select name="labour_rate_type" id="labour_rate_type" class="form-control" <?php echo !$isEditable ? 'disabled' : ''; ?>>
                    <option value="standard" <?php echo (!$quote || $quote['labour_rate_type'] === 'standard') ? 'selected' : ''; ?>>
                        Standard ($<?php echo number_format($standardRate, 2); ?>/hr)
                    </option>
                    <option value="premium" <?php echo ($quote && $quote['labour_rate_type'] === 'premium') ? 'selected' : ''; ?>>
                        Premium ($<?php echo number_format($premiumRate, 2); ?>/hr)
                    </option>
                </select>
            </div>

            <p class="text-muted" style="font-size: 12px; margin-bottom: 10px;">Enter time in minutes</p>

            <?php
            $labourCategories = [
                'stripping' => 'Stripping',
                'patterns' => 'Patterns',
                'cutting' => 'Cutting',
                'sewing' => 'Sewing',
                'upholstery' => 'Upholstery',
                'assembly' => 'Assembly',
                'handling' => 'Handling & Delivery'
            ];
            ?>

            <?php foreach ($labourCategories as $key => $label): ?>
                <div class="form-group labour-input">
                    <label for="labour_<?php echo $key; ?>"><?php echo $label; ?></label>
                    <div class="input-with-suffix">
                        <input type="number" name="labour_<?php echo $key; ?>" id="labour_<?php echo $key; ?>"
                            class="form-control labour-minutes" min="0" step="1"
                            value="<?php echo $quote ? (int)$quote['labour_' . $key] : 0; ?>"
                            <?php echo !$isEditable ? 'disabled' : ''; ?>
                            data-category="<?php echo $key; ?>">
                        <span class="input-suffix">mins</span>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="labour-summary">
                <div class="summary-row">
                    <span>Total Time:</span>
                    <span id="totalLabourTime">0h 0m</span>
                </div>
                <div class="summary-row">
                    <span>Labour Total:</span>
                    <span id="labourTotal">$0.00</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Materials Section -->
    <div class="quote-section materials-section">
        <div class="section-header">
            <h3>Materials</h3>
        </div>

        <?php if ($isEditable): ?>
            <div class="material-search">
                <input type="text" id="materialSearch" class="form-control" placeholder="Search materials by name or code...">
                <div id="materialSearchResults" class="search-results"></div>
            </div>
        <?php endif; ?>

        <table class="materials-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 15%;">Supplier</th>
                    <th style="width: 10%;">Qty</th>
                    <th style="width: 12%;">Unit Cost</th>
                    <th style="width: 13%;">Line Total</th>
                    <?php if ($isEditable): ?>
                        <th style="width: 10%;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="materialsBody">
                <?php if ($quote && count($quote['materials']) > 0): ?>
                    <?php foreach ($quote['materials'] as $idx => $material): ?>
                        <tr data-line-id="<?php echo $material['id']; ?>" data-idx="<?php echo $idx; ?>">
                            <td>
                                <?php echo htmlspecialchars($material['item_description']); ?>
                                <?php if ($material['manufacturers_code']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($material['manufacturers_code']); ?></small>
                                <?php endif; ?>
                                <input type="hidden" name="materials[<?php echo $idx; ?>][id]" value="<?php echo $material['id']; ?>">
                                <input type="hidden" name="materials[<?php echo $idx; ?>][material_id]" value="<?php echo $material['material_id']; ?>">
                                <input type="hidden" name="materials[<?php echo $idx; ?>][item_description]" value="<?php echo htmlspecialchars($material['item_description']); ?>">
                                <input type="hidden" name="materials[<?php echo $idx; ?>][supplier_name]" value="<?php echo htmlspecialchars($material['supplier_name'] ?? ''); ?>">
                                <input type="hidden" name="materials[<?php echo $idx; ?>][manufacturers_code]" value="<?php echo htmlspecialchars($material['manufacturers_code'] ?? ''); ?>">
                            </td>
                            <td><?php echo htmlspecialchars($material['supplier_name'] ?? '-'); ?></td>
                            <td>
                                <?php if ($isEditable): ?>
                                    <input type="number" class="form-control form-control-sm material-qty"
                                        name="materials[<?php echo $idx; ?>][quantity]"
                                        value="<?php echo $material['quantity']; ?>" min="0.01" step="0.01"
                                        data-line-id="<?php echo $material['id']; ?>">
                                <?php else: ?>
                                    <?php echo number_format($material['quantity'], 2); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isEditable): ?>
                                    <input type="number" class="form-control form-control-sm material-cost"
                                        name="materials[<?php echo $idx; ?>][unit_cost]"
                                        value="<?php echo $material['unit_cost']; ?>" min="0" step="0.01"
                                        data-line-id="<?php echo $material['id']; ?>">
                                <?php else: ?>
                                    $<?php echo number_format($material['unit_cost'], 2); ?>
                                <?php endif; ?>
                            </td>
                            <td class="line-total">$<?php echo number_format($material['line_total'], 2); ?></td>
                            <?php if ($isEditable): ?>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-material"
                                        data-line-id="<?php echo $material['id']; ?>">Remove</button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-materials">
                        <td colspan="<?php echo $isEditable ? 6 : 5; ?>" style="text-align: center; color: #666;">
                            No materials added yet. Use the search above to add materials.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="<?php echo $isEditable ? 4 : 3; ?>" style="text-align: right;"><strong>Materials Subtotal:</strong></td>
                    <td colspan="<?php echo $isEditable ? 2 : 2; ?>"><strong id="materialsSubtotal">$<?php echo number_format($quote['subtotal_materials'] ?? 0, 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Misc Charges Section -->
    <div class="quote-section misc-section">
        <h3>Miscellaneous Charges</h3>

        <table class="misc-table">
            <thead>
                <tr>
                    <th style="width: 5%;"></th>
                    <th style="width: 45%;">Item</th>
                    <th style="width: 15%;">Qty</th>
                    <th style="width: 15%;">Unit Price</th>
                    <th style="width: 20%;">Line Total</th>
                </tr>
            </thead>
            <tbody id="miscBody">
                <?php foreach ($miscItems as $idx => $misc): ?>
                    <?php
                    // For new quotes, default to unchecked (0). For existing quotes, use saved value.
                    $included = $isNew ? 0 : (isset($misc['included']) ? $misc['included'] : 0);
                    $price = isset($misc['price']) ? $misc['price'] : $misc['fixed_price'];
                    $quantity = isset($misc['quantity']) ? $misc['quantity'] : 1;
                    $miscMaterialId = isset($misc['misc_material_id']) ? $misc['misc_material_id'] : $misc['id'];
                    ?>
                    <tr class="misc-row" data-misc-id="<?php echo $miscMaterialId; ?>">
                        <td>
                            <input type="checkbox" class="misc-checkbox"
                                name="misc[<?php echo $idx; ?>][included]" value="1"
                                data-price="<?php echo $price; ?>"
                                <?php echo $included ? 'checked' : ''; ?>
                                <?php echo !$isEditable ? 'disabled' : ''; ?>>
                            <input type="hidden" name="misc[<?php echo $idx; ?>][misc_material_id]" value="<?php echo $miscMaterialId; ?>">
                            <input type="hidden" name="misc[<?php echo $idx; ?>][name]" value="<?php echo htmlspecialchars($misc['name']); ?>">
                        </td>
                        <td><?php echo htmlspecialchars($misc['name']); ?></td>
                        <td>
                            <?php if ($isEditable): ?>
                                <input type="number" class="form-control form-control-sm misc-qty"
                                    name="misc[<?php echo $idx; ?>][quantity]"
                                    value="<?php echo $quantity; ?>" min="1" step="1">
                            <?php else: ?>
                                <?php echo $quantity; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isEditable): ?>
                                <input type="number" class="form-control form-control-sm misc-price-input"
                                    name="misc[<?php echo $idx; ?>][price]"
                                    value="<?php echo $price; ?>" min="0" step="0.01">
                            <?php else: ?>
                                $<?php echo number_format($price, 2); ?>
                            <?php endif; ?>
                        </td>
                        <td class="misc-line-total">$<?php echo $included ? number_format($price * $quantity, 2) : '0.00'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right;"><strong>Misc Subtotal:</strong></td>
                    <td><strong id="miscSubtotal">$<?php echo number_format($quote['subtotal_misc'] ?? 0, 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Totals Section -->
    <div class="quote-section totals-section">
        <h3>Quote Totals</h3>

        <div class="totals-grid">
            <div class="total-row">
                <span>Materials:</span>
                <span id="totalMaterials">$<?php echo number_format($quote['subtotal_materials'] ?? 0, 2); ?></span>
            </div>
            <div class="total-row">
                <span>Miscellaneous:</span>
                <span id="totalMisc">$<?php echo number_format($quote['subtotal_misc'] ?? 0, 2); ?></span>
            </div>
            <div class="total-row">
                <span>Labour:</span>
                <span id="totalLabour">$<?php echo number_format($quote['subtotal_labour'] ?? 0, 2); ?></span>
            </div>
            <div class="total-row subtotal">
                <span><strong>Subtotal (Excl. GST):</strong></span>
                <span id="subtotalExclGst"><strong>$<?php echo number_format($quote['total_excl_gst'] ?? 0, 2); ?></strong></span>
            </div>
            <div class="total-row">
                <span>GST (<?php echo $gstRate; ?>%):</span>
                <span id="gstAmount">$<?php echo number_format($quote['gst_amount'] ?? 0, 2); ?></span>
            </div>
            <div class="total-row grand-total">
                <span><strong>Total (Incl. GST):</strong></span>
                <span id="grandTotal"><strong>$<?php echo number_format($quote['total_incl_gst'] ?? 0, 2); ?></strong></span>
            </div>
        </div>
    </div>

    <?php if ($isNew): ?>
        <div class="form-actions">
            <button type="submit" name="action" value="save" class="btn btn-primary">Create Quote</button>
            <a href="quotes.php" class="btn btn-secondary">Cancel</a>
        </div>
    <?php endif; ?>
</form>

<!-- New Client Modal -->
<div id="newClientModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Client</h3>
            <button type="button" class="modal-close" id="closeClientModal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="newClientName">Name *</label>
                <input type="text" id="newClientName" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="newClientEmail">Email</label>
                <input type="email" id="newClientEmail" class="form-control">
            </div>
            <div class="form-group">
                <label for="newClientPhone">Phone</label>
                <input type="text" id="newClientPhone" class="form-control">
            </div>
            <div class="form-group">
                <label for="newClientAddress">Address</label>
                <textarea id="newClientAddress" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelClientModal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveNewClient">Save Client</button>
        </div>
    </div>
</div>

<?php if (!$isNew): ?>
<!-- Email Quote Modal -->
<div id="emailModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Send Quote via Email</h3>
            <button type="button" class="modal-close" onclick="closeEmailModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="emailTo">Recipient Email *</label>
                <input type="email" id="emailTo" class="form-control" required placeholder="client@example.com" value="<?php echo htmlspecialchars($quote['client_email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="emailSubject">Subject</label>
                <input type="text" id="emailSubject" class="form-control" value="Quote <?php echo htmlspecialchars($quote['quote_number']); ?> from <?php echo htmlspecialchars($companyName); ?>">
            </div>

            <div class="form-group">
                <label for="emailMessage">Custom Message (optional)</label>
                <textarea id="emailMessage" class="form-control" rows="5" placeholder="Leave blank to use default email template"></textarea>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" id="emailUpdateStatus" checked>
                    Update quote status to "Sent" if currently "Draft"
                </label>
            </div>

            <div id="emailError" class="alert alert-danger" style="display: none;"></div>
            <div id="emailSuccess" class="alert alert-success" style="display: none;"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeEmailModal()">Cancel</button>
            <button type="button" class="btn btn-primary" id="sendEmailBtn" onclick="sendQuoteEmail()">Send Email</button>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.quote-builder {
    max-width: 1400px;
}

.quote-builder-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.quote-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.quote-section h3 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
    font-size: 16px;
    color: #333;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    font-size: 13px;
    color: #555;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.1);
}

.form-control:disabled {
    background-color: #f5f5f5;
    cursor: not-allowed;
}

.form-control-sm {
    padding: 4px 8px;
    font-size: 13px;
}

.input-with-suffix {
    display: flex;
    align-items: center;
}

.input-with-suffix input {
    flex: 1;
    border-radius: 4px 0 0 4px;
}

.input-suffix {
    padding: 8px 12px;
    background: #f0f0f0;
    border: 1px solid #ddd;
    border-left: none;
    border-radius: 0 4px 4px 0;
    font-size: 13px;
    color: #666;
}

.labour-input {
    margin-bottom: 10px;
}

.labour-input label {
    display: inline-block;
    width: 140px;
    margin-bottom: 0;
}

.labour-input .input-with-suffix {
    display: inline-flex;
    width: calc(100% - 150px);
}

.labour-summary {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    font-size: 14px;
}

.summary-row:last-child {
    font-weight: bold;
    font-size: 15px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

.section-header h3 {
    margin: 0;
    padding: 0;
    border: none;
}

.material-search {
    position: relative;
    margin-bottom: 15px;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 0 0 4px 4px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.search-results.active {
    display: block;
}

.search-result-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.search-result-item:hover {
    background: #f5f5f5;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-item .item-name {
    font-weight: 500;
}

.search-result-item .item-details {
    font-size: 12px;
    color: #666;
    margin-top: 2px;
}

.materials-table {
    width: 100%;
    border-collapse: collapse;
}

.materials-table th,
.materials-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.materials-table th {
    background: #f8f9fa;
    font-weight: 500;
    font-size: 13px;
}

.materials-table tfoot td {
    border-top: 2px solid #ddd;
    border-bottom: none;
    padding-top: 15px;
}

.misc-table {
    width: 100%;
    border-collapse: collapse;
}

.misc-table th,
.misc-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.misc-table th {
    background: #f8f9fa;
    font-weight: 500;
    font-size: 13px;
}

.misc-table tfoot td {
    border-top: 2px solid #ddd;
    border-bottom: none;
    padding-top: 15px;
}

.misc-row input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

.totals-section {
    max-width: 400px;
    margin-left: auto;
}

.totals-grid {
    font-size: 14px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.total-row.subtotal {
    border-top: 1px solid #ddd;
    margin-top: 5px;
    padding-top: 15px;
}

.total-row.grand-total {
    background: #f8f9fa;
    margin: 10px -20px -20px -20px;
    padding: 15px 20px;
    border-radius: 0 0 8px 8px;
    font-size: 18px;
    border-bottom: none;
}

.form-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.badge-info {
    background-color: #17a2b8;
    color: white;
}

.text-muted {
    color: #6c757d;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

@media (max-width: 768px) {
    .quote-builder-grid {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .labour-input label {
        display: block;
        width: 100%;
        margin-bottom: 5px;
    }

    .labour-input .input-with-suffix {
        width: 100%;
    }
}

.input-with-button {
    display: flex;
    gap: 8px;
}

.input-with-button select {
    flex: 1;
}

.input-with-button .btn {
    flex-shrink: 0;
    padding: 8px 12px;
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    line-height: 1;
}

.modal-close:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    border-top: 1px solid #e9ecef;
}

.alert {
    padding: 10px 15px;
    border-radius: 4px;
    margin-top: 15px;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quoteId = <?php echo $quote ? $quote['id'] : 'null'; ?>;
    const isEditable = <?php echo $isEditable ? 'true' : 'false'; ?>;
    const standardRate = <?php echo $standardRate; ?>;
    const premiumRate = <?php echo $premiumRate; ?>;
    const gstRate = <?php echo $gstRate; ?>;


    // Labour calculation
    const labourInputs = document.querySelectorAll('.labour-minutes');
    const rateSelect = document.getElementById('labour_rate_type');

    function calculateLabour() {
        let totalMinutes = 0;
        labourInputs.forEach(input => {
            totalMinutes += parseInt(input.value) || 0;
        });

        const hours = Math.floor(totalMinutes / 60);
        const mins = totalMinutes % 60;
        document.getElementById('totalLabourTime').textContent = `${hours}h ${mins}m`;

        const rate = rateSelect.value === 'premium' ? premiumRate : standardRate;
        const labourTotal = (totalMinutes / 60) * rate;
        document.getElementById('labourTotal').textContent = `$${labourTotal.toFixed(2)}`;
        document.getElementById('totalLabour').textContent = `$${labourTotal.toFixed(2)}`;

        calculateTotals();
    }

    labourInputs.forEach(input => {
        input.addEventListener('input', calculateLabour);
    });

    if (rateSelect) {
        rateSelect.addEventListener('change', calculateLabour);
    }

    // Calculate totals
    function calculateTotals() {
        const materials = parseFloat(document.getElementById('totalMaterials').textContent.replace('$', '').replace(',', '')) || 0;
        const misc = parseFloat(document.getElementById('totalMisc').textContent.replace('$', '').replace(',', '')) || 0;
        const labour = parseFloat(document.getElementById('totalLabour').textContent.replace('$', '').replace(',', '')) || 0;

        const subtotal = materials + misc + labour;
        const gst = subtotal * (gstRate / 100);
        const total = subtotal + gst;

        document.getElementById('subtotalExclGst').innerHTML = `<strong>$${subtotal.toFixed(2)}</strong>`;
        document.getElementById('gstAmount').textContent = `$${gst.toFixed(2)}`;
        document.getElementById('grandTotal').innerHTML = `<strong>$${total.toFixed(2)}</strong>`;
    }

    // Initial calculation
    calculateLabour();

    // New Client Modal - must be before early return so it works for new quotes
    const newClientBtn = document.getElementById('newClientBtn');
    const newClientModal = document.getElementById('newClientModal');
    const closeClientModal = document.getElementById('closeClientModal');
    const cancelClientModal = document.getElementById('cancelClientModal');
    const saveNewClient = document.getElementById('saveNewClient');

    function openModal() {
        if (newClientModal) {
            newClientModal.style.display = 'flex';
            document.getElementById('newClientName').focus();
        }
    }

    function closeModal() {
        if (newClientModal) {
            newClientModal.style.display = 'none';
            // Clear form
            document.getElementById('newClientName').value = '';
            document.getElementById('newClientEmail').value = '';
            document.getElementById('newClientPhone').value = '';
            document.getElementById('newClientAddress').value = '';
        }
    }

    if (newClientBtn) {
        newClientBtn.addEventListener('click', openModal);
    }

    if (closeClientModal) {
        closeClientModal.addEventListener('click', closeModal);
    }

    if (cancelClientModal) {
        cancelClientModal.addEventListener('click', closeModal);
    }

    // Close modal on background click
    if (newClientModal) {
        newClientModal.addEventListener('click', function(e) {
            if (e.target === newClientModal) {
                closeModal();
            }
        });
    }

    // Save new client
    if (saveNewClient) {
        saveNewClient.addEventListener('click', function() {
            const name = document.getElementById('newClientName').value.trim();
            const email = document.getElementById('newClientEmail').value.trim();
            const phone = document.getElementById('newClientPhone').value.trim();
            const address = document.getElementById('newClientAddress').value.trim();

            if (!name) {
                alert('Client name is required');
                document.getElementById('newClientName').focus();
                return;
            }

            saveNewClient.disabled = true;
            saveNewClient.textContent = 'Saving...';

            fetch('<?php echo BASE_PATH; ?>/api/clientCreate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, phone, address })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add new client to dropdown and select it
                    const clientSelect = document.getElementById('client_id');
                    const option = document.createElement('option');
                    option.value = data.data.id;
                    option.textContent = data.data.name;
                    option.selected = true;
                    clientSelect.appendChild(option);

                    // Trigger change event to load projects
                    clientSelect.dispatchEvent(new Event('change'));

                    closeModal();
                } else {
                    alert(data.message || 'Failed to create client');
                }
            })
            .catch(error => {
                alert('Error creating client');
                console.error(error);
            })
            .finally(() => {
                saveNewClient.disabled = false;
                saveNewClient.textContent = 'Save Client';
            });
        });
    }

    // Handle Enter key in modal
    if (newClientModal) {
        newClientModal.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                saveNewClient.click();
            }
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    }

    if (!isEditable) return;

    // Track material index for form fields
    let materialIndex = document.querySelectorAll('#materialsBody tr[data-idx]').length;

    // Material search
    const searchInput = document.getElementById('materialSearch');
    const searchResults = document.getElementById('materialSearchResults');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.classList.remove('active');
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`<?php echo BASE_PATH; ?>/api/materialsSearch.php?q=${encodeURIComponent(query)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.data && data.data.length > 0) {
                            searchResults.innerHTML = data.data.map(material => `
                                <div class="search-result-item" data-material='${JSON.stringify(material).replace(/'/g, "&#39;")}'>
                                    <div class="item-name">${escapeHtml(material.item_name)}</div>
                                    <div class="item-details">
                                        ${material.manufacturers_code ? escapeHtml(material.manufacturers_code) + ' | ' : ''}
                                        ${material.supplier_name ? escapeHtml(material.supplier_name) + ' | ' : ''}
                                        $${parseFloat(material.sell_price || 0).toFixed(2)}
                                    </div>
                                </div>
                            `).join('');
                            searchResults.classList.add('active');
                        } else {
                            searchResults.innerHTML = '<div class="search-result-item">No materials found</div>';
                            searchResults.classList.add('active');
                        }
                    })
                    .catch(error => {
                        console.error('Material search error:', error);
                        searchResults.innerHTML = '<div class="search-result-item">Error searching materials</div>';
                        searchResults.classList.add('active');
                    });
            }, 300);
        });

        searchInput.addEventListener('blur', function() {
            setTimeout(() => searchResults.classList.remove('active'), 200);
        });

        searchResults.addEventListener('click', function(e) {
            const item = e.target.closest('.search-result-item');
            if (item && item.dataset.material) {
                const material = JSON.parse(item.dataset.material);
                addMaterialToTable(material);
                searchInput.value = '';
                searchResults.classList.remove('active');
            }
        });
    }

    // Add material to table (client-side)
    function addMaterialToTable(material) {
        const tbody = document.getElementById('materialsBody');

        // Remove "no materials" row if present
        const noMaterialsRow = tbody.querySelector('.no-materials');
        if (noMaterialsRow) {
            noMaterialsRow.remove();
        }

        const idx = materialIndex++;
        const price = parseFloat(material.sell_price || 0);

        const row = document.createElement('tr');
        row.dataset.idx = idx;
        row.innerHTML = `
            <td>
                ${escapeHtml(material.item_name)}
                ${material.manufacturers_code ? '<br><small class="text-muted">' + escapeHtml(material.manufacturers_code) + '</small>' : ''}
                <input type="hidden" name="materials[${idx}][material_id]" value="${material.id}">
                <input type="hidden" name="materials[${idx}][item_description]" value="${escapeHtml(material.item_name)}">
                <input type="hidden" name="materials[${idx}][supplier_name]" value="${escapeHtml(material.supplier_name || '')}">
                <input type="hidden" name="materials[${idx}][manufacturers_code]" value="${escapeHtml(material.manufacturers_code || '')}">
            </td>
            <td>${escapeHtml(material.supplier_name || '-')}</td>
            <td>
                <input type="number" class="form-control form-control-sm material-qty"
                    name="materials[${idx}][quantity]" value="1" min="0.01" step="0.01">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm material-cost"
                    name="materials[${idx}][unit_cost]" value="${price.toFixed(2)}" min="0" step="0.01">
            </td>
            <td class="line-total">$${price.toFixed(2)}</td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-material-btn">Remove</button>
            </td>
        `;

        tbody.appendChild(row);

        // Add event listeners to new row
        setupMaterialRowEvents(row);
        updateMaterialsTotal();
    }

    // Setup events for material row
    function setupMaterialRowEvents(row) {
        const qtyInput = row.querySelector('.material-qty');
        const costInput = row.querySelector('.material-cost');
        const removeBtn = row.querySelector('.remove-material-btn, .remove-material');

        if (qtyInput) {
            qtyInput.addEventListener('input', function() {
                updateRowTotal(row);
                updateMaterialsTotal();
            });
        }

        if (costInput) {
            costInput.addEventListener('input', function() {
                updateRowTotal(row);
                updateMaterialsTotal();
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                if (!confirm('Remove this material from the quote?')) return;
                row.remove();
                updateMaterialsTotal();

                // Add back "no materials" row if empty
                const tbody = document.getElementById('materialsBody');
                if (tbody.querySelectorAll('tr').length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.className = 'no-materials';
                    emptyRow.innerHTML = '<td colspan="6" style="text-align: center; color: #666;">No materials added yet. Use the search above to add materials.</td>';
                    tbody.appendChild(emptyRow);
                }
            });
        }
    }

    function updateRowTotal(row) {
        const qty = parseFloat(row.querySelector('.material-qty').value) || 0;
        const cost = parseFloat(row.querySelector('.material-cost').value) || 0;
        row.querySelector('.line-total').textContent = `$${(qty * cost).toFixed(2)}`;
    }

    // Setup events for existing material rows
    document.querySelectorAll('#materialsBody tr[data-idx], #materialsBody tr[data-line-id]').forEach(row => {
        setupMaterialRowEvents(row);
    });

    // Misc calculations (client-side)
    function setupMiscEvents() {
        document.querySelectorAll('.misc-row').forEach(row => {
            const checkbox = row.querySelector('.misc-checkbox');
            const qtyInput = row.querySelector('.misc-qty');
            const priceInput = row.querySelector('.misc-price-input');

            function updateMiscRowTotal() {
                const included = checkbox.checked;
                const qty = parseFloat(qtyInput?.value || 1);
                const price = parseFloat(priceInput?.value || 0);
                const total = included ? qty * price : 0;
                row.querySelector('.misc-line-total').textContent = `$${total.toFixed(2)}`;
                updateMiscTotal();
            }

            if (checkbox) checkbox.addEventListener('change', updateMiscRowTotal);
            if (qtyInput) qtyInput.addEventListener('input', updateMiscRowTotal);
            if (priceInput) priceInput.addEventListener('input', updateMiscRowTotal);
        });
    }

    setupMiscEvents();

    function updateMaterialsTotal() {
        let total = 0;
        document.querySelectorAll('#materialsBody .line-total').forEach(cell => {
            total += parseFloat(cell.textContent.replace('$', '').replace(',', '')) || 0;
        });
        document.getElementById('materialsSubtotal').textContent = `$${total.toFixed(2)}`;
        document.getElementById('totalMaterials').textContent = `$${total.toFixed(2)}`;
        calculateTotals();
    }

    function updateMiscTotal() {
        let total = 0;
        document.querySelectorAll('.misc-row').forEach(row => {
            const checkbox = row.querySelector('.misc-checkbox');
            if (checkbox && checkbox.checked) {
                const qty = parseFloat(row.querySelector('.misc-qty')?.value || 1);
                const price = parseFloat(row.querySelector('.misc-price-input')?.value || 0);
                total += qty * price;
            }
        });
        document.getElementById('miscSubtotal').textContent = `$${total.toFixed(2)}`;
        document.getElementById('totalMisc').textContent = `$${total.toFixed(2)}`;
        calculateTotals();
    }

    // Initial calculations
    updateMaterialsTotal();
    updateMiscTotal();

    // Client change - load projects
    const clientSelect = document.getElementById('client_id');
    const projectSelect = document.getElementById('project_id');

    if (clientSelect) {
        clientSelect.addEventListener('change', function() {
            const clientId = this.value;
            projectSelect.innerHTML = '<option value="">No linked project</option>';

            if (clientId) {
                fetch(`<?php echo BASE_PATH; ?>/api/clientProjects.php?client_id=${clientId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            data.data.forEach(project => {
                                const option = document.createElement('option');
                                option.value = project.id;
                                option.textContent = project.title;
                                projectSelect.appendChild(option);
                            });
                        }
                    });
            }
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

<?php if (!$isNew): ?>
// Email Modal Functions
function openEmailModal() {
    var modal = document.getElementById('emailModal');
    var errorDiv = document.getElementById('emailError');
    var successDiv = document.getElementById('emailSuccess');
    var sendBtn = document.getElementById('sendEmailBtn');

    if (modal) {
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send Email';
        modal.style.display = 'flex';
    }
}

function closeEmailModal() {
    var modal = document.getElementById('emailModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

async function sendQuoteEmail() {
    var sendBtn = document.getElementById('sendEmailBtn');
    var errorDiv = document.getElementById('emailError');
    var successDiv = document.getElementById('emailSuccess');
    var toField = document.getElementById('emailTo');
    var subjectField = document.getElementById('emailSubject');
    var messageField = document.getElementById('emailMessage');
    var updateStatusField = document.getElementById('emailUpdateStatus');

    // Reset messages
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';

    // Validate email
    var toEmail = toField.value.trim();
    if (!toEmail) {
        errorDiv.textContent = 'Please enter a recipient email address';
        errorDiv.style.display = 'block';
        return;
    }

    // Disable button and show loading
    sendBtn.disabled = true;
    sendBtn.textContent = 'Sending...';

    try {
        var response = await fetch('<?php echo BASE_PATH; ?>/api/quoteEmail.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                quote_id: <?php echo $quote['id']; ?>,
                to_email: toEmail,
                subject: subjectField.value.trim(),
                message: messageField.value.trim(),
                update_status: updateStatusField.checked
            })
        });

        var data = await response.json();

        if (data.success) {
            successDiv.textContent = data.message;
            successDiv.style.display = 'block';
            sendBtn.textContent = 'Sent!';

            // Close modal and refresh page after delay
            setTimeout(function() {
                closeEmailModal();
                window.location.reload();
            }, 1500);
        } else {
            errorDiv.textContent = data.message || 'Failed to send email';
            errorDiv.style.display = 'block';
            sendBtn.disabled = false;
            sendBtn.textContent = 'Send Email';
        }
    } catch (error) {
        console.error('Email send error:', error);
        errorDiv.textContent = 'An error occurred while sending the email';
        errorDiv.style.display = 'block';
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send Email';
    }
}

// Close email modal on escape key and background click
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEmailModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var emailModal = document.getElementById('emailModal');
    if (emailModal) {
        emailModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEmailModal();
            }
        });
    }
});
<?php endif; ?>
</script>

<?php
function getStatusColor($status) {
    $colors = [
        'draft' => '#6c757d',
        'sent' => '#007bff',
        'accepted' => '#28a745',
        'declined' => '#dc3545',
        'expired' => '#fd7e14',
        'invoiced' => '#17a2b8'
    ];
    return $colors[$status] ?? '#6c757d';
}

require_once 'includes/footer.php';
?>
