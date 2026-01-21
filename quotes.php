<?php
$pageTitle = 'Quotes';
require_once 'includes/header.php';
require_once 'includes/models/Quote.php';
require_once 'includes/models/Client.php';

$quoteModel = new Quote();
$clientModel = new Client();
$db = Database::getInstance();

// Get company name for email subject
$companyName = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'company_name'");
$companyName = $companyName ? $companyName['setting_value'] : 'Our Company';
$message = '';
$messageType = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = $_POST['id'];
        $result = $quoteModel->delete($id);

        if ($result['success']) {
            $message = 'Quote deleted successfully!';
            $messageType = 'success';
        } else {
            $message = $result['message'] ?? 'Failed to delete quote.';
            $messageType = 'warning';
        }
    } elseif ($action === 'updateStatus') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $result = $quoteModel->updateStatus($id, $status);

        if ($result['success']) {
            $message = 'Quote status updated!';
            $messageType = 'success';
        } else {
            $message = $result['message'] ?? 'Failed to update status.';
            $messageType = 'danger';
        }
    } elseif ($action === 'createRevision') {
        $id = $_POST['id'];
        $result = $quoteModel->createRevision($id);

        if ($result['success']) {
            header('Location: quoteBuilder.php?id=' . $result['id']);
            exit;
        } else {
            $message = $result['message'] ?? 'Failed to create revision.';
            $messageType = 'danger';
        }
    }
}

// Build filters
$filters = [];
if (!empty($_GET['client'])) {
    $filters['client_id'] = $_GET['client'];
}
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get quotes
$quotes = $quoteModel->getAll($filters);

// Get clients for filter dropdown
$clients = $clientModel->getAll();

// Status options
$statuses = [
    'draft' => ['label' => 'Draft', 'color' => '#6c757d'],
    'sent' => ['label' => 'Sent', 'color' => '#007bff'],
    'accepted' => ['label' => 'Accepted', 'color' => '#28a745'],
    'declined' => ['label' => 'Declined', 'color' => '#dc3545'],
    'expired' => ['label' => 'Expired', 'color' => '#fd7e14'],
    'invoiced' => ['label' => 'Invoiced', 'color' => '#17a2b8']
];
?>

<div class="page-header">
    <h1 class="page-title">Quotes</h1>
    <div class="page-actions">
        <form method="GET" style="display: inline-block; margin-right: 10px;">
            <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="form-control" style="width: 150px; display: inline-block;">
            <select name="client" class="form-control" style="width: 150px; display: inline-block;">
                <option value="">All Clients</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['id']; ?>" <?php echo ($_GET['client'] ?? '') == $client['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($client['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-control" style="width: 120px; display: inline-block;">
                <option value="">All Status</option>
                <?php foreach ($statuses as $key => $status): ?>
                    <option value="<?php echo $key; ?>" <?php echo ($_GET['status'] ?? '') == $key ? 'selected' : ''; ?>>
                        <?php echo $status['label']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
            <?php if (!empty($filters)): ?>
                <a href="quotes.php" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </form>
        <a href="quoteBuilder.php" class="btn btn-primary">+ New Quote</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Quote #</th>
                <th>Client</th>
                <th>Date</th>
                <th>Expiry</th>
                <th>Total</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($quotes) === 0): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                        <?php echo !empty($filters) ? 'No quotes found matching your filters.' : 'No quotes yet. Create your first quote!'; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($quotes as $quote): ?>
                    <?php
                    $statusInfo = $statuses[$quote['status']] ?? ['label' => $quote['status'], 'color' => '#6c757d'];
                    $isExpired = $quote['status'] === 'sent' && $quote['expiry_date'] && strtotime($quote['expiry_date']) < time();
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($quote['quote_number']); ?></strong>
                            <?php if ($quote['revision'] > 1): ?>
                                <span class="badge badge-info">Rev <?php echo $quote['revision']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($quote['client_name'] ?? 'No client'); ?>
                            <?php if ($quote['project_title']): ?>
                                <br><small style="color: #666;"><?php echo htmlspecialchars($quote['project_title']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($quote['quote_date'])); ?></td>
                        <td>
                            <?php if ($quote['expiry_date']): ?>
                                <span class="<?php echo $isExpired ? 'text-danger' : ''; ?>">
                                    <?php echo date('M j, Y', strtotime($quote['expiry_date'])); ?>
                                </span>
                                <?php if ($isExpired): ?>
                                    <span class="badge badge-warning">Expired</span>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong>$<?php echo number_format($quote['total_incl_gst'], 2); ?></strong>
                            <br><small style="color: #666;">excl: $<?php echo number_format($quote['total_excl_gst'], 2); ?></small>
                        </td>
                        <td>
                            <span class="badge" style="background-color: <?php echo $statusInfo['color']; ?>; color: white;">
                                <?php echo $statusInfo['label']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="quoteBuilder.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-primary">
                                    <?php echo $quote['status'] === 'draft' ? 'Edit' : 'View'; ?>
                                </a>

                                <a href="api/quotePdf.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-secondary" target="_blank" title="Download PDF">PDF</a>

                                <button type="button" class="btn btn-sm btn-info" onclick="openEmailModal(<?php echo $quote['id']; ?>, '<?php echo htmlspecialchars($quote['quote_number'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($quote['client_email'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($companyName, ENT_QUOTES); ?>')" title="Send Email">
                                    Email
                                </button>

                                <?php if ($quote['status'] === 'draft'): ?>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="action" value="updateStatus">
                                        <input type="hidden" name="id" value="<?php echo $quote['id']; ?>">
                                        <input type="hidden" name="status" value="sent">
                                        <button type="submit" class="btn btn-sm btn-info" onclick="return confirm('Mark this quote as sent?');">Send</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($quote['status'] === 'sent'): ?>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="action" value="updateStatus">
                                        <input type="hidden" name="id" value="<?php echo $quote['id']; ?>">
                                        <input type="hidden" name="status" value="accepted">
                                        <button type="submit" class="btn btn-sm btn-success">Accept</button>
                                    </form>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="action" value="updateStatus">
                                        <input type="hidden" name="id" value="<?php echo $quote['id']; ?>">
                                        <input type="hidden" name="status" value="declined">
                                        <button type="submit" class="btn btn-sm btn-danger">Decline</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array($quote['status'], ['sent', 'accepted', 'declined'])): ?>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="action" value="createRevision">
                                        <input type="hidden" name="id" value="<?php echo $quote['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">Revise</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($quote['status'] === 'accepted'): ?>
                                    <!-- TODO: Phase 5 - Convert to Invoice -->
                                    <button class="btn btn-sm btn-success" disabled title="Coming soon">Invoice</button>
                                <?php endif; ?>

                                <?php if ($quote['status'] === 'draft'): ?>
                                    <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this quote?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $quote['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
    margin: 1px;
    line-height: 1.5;
    display: inline-block;
    box-sizing: border-box;
    vertical-align: middle;
}
.action-buttons form {
    display: inline-block;
    margin: 0;
    padding: 0;
}
.action-buttons form .btn-sm {
    margin: 1px;
}
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}
.badge-info {
    background-color: #17a2b8;
    color: white;
}
.badge-warning {
    background-color: #ffc107;
    color: #212529;
}
.text-danger {
    color: #dc3545;
}
.btn-outline {
    background: transparent;
    border: 1px solid #6c757d;
    color: #6c757d;
}
.btn-outline:hover {
    background: #6c757d;
    color: white;
}
.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
}
</style>

<!-- Email Quote Modal -->
<div id="emailModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Send Quote via Email</h3>
            <button type="button" class="close-btn" onclick="closeEmailModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="emailForm">
                <input type="hidden" id="emailQuoteId" name="quote_id">

                <div class="form-group">
                    <label for="emailTo">Recipient Email *</label>
                    <input type="email" id="emailTo" name="to_email" class="form-control" required placeholder="client@example.com">
                </div>

                <div class="form-group">
                    <label for="emailSubject">Subject</label>
                    <input type="text" id="emailSubject" name="subject" class="form-control" placeholder="Leave blank for default subject">
                </div>

                <div class="form-group">
                    <label for="emailMessage">Custom Message (optional)</label>
                    <textarea id="emailMessage" name="message" class="form-control" rows="5" placeholder="Leave blank to use default email template"></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="emailUpdateStatus" name="update_status" checked>
                        Update quote status to "Sent" if currently "Draft"
                    </label>
                </div>

                <div id="emailError" class="alert alert-danger" style="display: none;"></div>
                <div id="emailSuccess" class="alert alert-success" style="display: none;"></div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeEmailModal()">Cancel</button>
            <button type="button" class="btn btn-primary" id="sendEmailBtn" onclick="sendQuoteEmail()">
                Send Email
            </button>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
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

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    line-height: 1;
}

.close-btn:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    border-color: #007bff;
    outline: none;
}

textarea.form-control {
    resize: vertical;
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
// Store references to modal elements
let emailModalElements = null;

function getEmailModalElements() {
    if (!emailModalElements) {
        emailModalElements = {
            modal: document.getElementById('emailModal'),
            quoteId: document.getElementById('emailQuoteId'),
            to: document.getElementById('emailTo'),
            subject: document.getElementById('emailSubject'),
            message: document.getElementById('emailMessage'),
            updateStatus: document.getElementById('emailUpdateStatus'),
            error: document.getElementById('emailError'),
            success: document.getElementById('emailSuccess'),
            sendBtn: document.getElementById('sendEmailBtn')
        };
    }
    return emailModalElements;
}

function openEmailModal(quoteId, quoteNumber, clientEmail, companyName) {
    const els = getEmailModalElements();
    if (!els.modal) {
        console.error('Email modal not found');
        alert('Error: Email modal not found. Please refresh the page.');
        return;
    }

    els.quoteId.value = quoteId;
    els.to.value = clientEmail || '';
    els.subject.value = 'Quote ' + quoteNumber + ' from ' + companyName;
    els.message.value = '';
    els.updateStatus.checked = true;
    els.error.style.display = 'none';
    els.success.style.display = 'none';
    els.sendBtn.disabled = false;
    els.sendBtn.textContent = 'Send Email';
    els.modal.style.display = 'flex';
}

function closeEmailModal() {
    const els = getEmailModalElements();
    if (els.modal) {
        els.modal.style.display = 'none';
    }
}

async function sendQuoteEmail() {
    const els = getEmailModalElements();

    if (!els.sendBtn || !els.error || !els.success) {
        console.error('Required elements not found:', els);
        alert('Error: Required form elements not found. Please refresh the page.');
        return;
    }

    // Reset messages
    els.error.style.display = 'none';
    els.success.style.display = 'none';

    // Validate email
    const toEmail = els.to.value.trim();
    if (!toEmail) {
        els.error.textContent = 'Please enter a recipient email address';
        els.error.style.display = 'block';
        return;
    }

    // Disable button and show loading
    els.sendBtn.disabled = true;
    els.sendBtn.textContent = 'Sending...';

    try {
        const response = await fetch('api/quoteEmail.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                quote_id: els.quoteId.value,
                to_email: toEmail,
                subject: els.subject.value.trim(),
                message: els.message.value.trim(),
                update_status: els.updateStatus.checked
            })
        });

        const data = await response.json();

        if (data.success) {
            els.success.textContent = data.message;
            els.success.style.display = 'block';
            els.sendBtn.textContent = 'Sent!';

            // Close modal and refresh page after delay
            setTimeout(() => {
                closeEmailModal();
                window.location.reload();
            }, 1500);
        } else {
            els.error.textContent = data.message || 'Failed to send email';
            els.error.style.display = 'block';
            els.sendBtn.disabled = false;
            els.sendBtn.textContent = 'Send Email';
        }
    } catch (error) {
        console.error('Email send error:', error);
        els.error.textContent = 'An error occurred while sending the email';
        els.error.style.display = 'block';
        els.sendBtn.disabled = false;
        els.sendBtn.textContent = 'Send Email';
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEmailModal();
    }
});

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    // Reset element cache on DOM ready
    emailModalElements = null;

    const modal = document.getElementById('emailModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEmailModal();
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
