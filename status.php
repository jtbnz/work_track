<?php
$pageTitle = 'Project Status Management';
require_once 'includes/header.php';
require_once 'includes/models/ProjectStatus.php';

$statusModel = new ProjectStatus();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $data = [
            'name' => $_POST['name'],
            'color' => $_POST['color'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        if ($statusModel->create($data)) {
            $message = 'Status created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to create status.';
            $messageType = 'danger';
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $data = [
            'name' => $_POST['name'],
            'color' => $_POST['color'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        if ($statusModel->update($id, $data)) {
            $message = 'Status updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update status.';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $result = $statusModel->delete($id);

        if ($result['success']) {
            $message = 'Status deleted successfully!';
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    } elseif ($action === 'reorder') {
        $statusIds = json_decode($_POST['status_order'], true);
        if ($statusModel->reorder($statusIds)) {
            $message = 'Status order updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update status order.';
            $messageType = 'danger';
        }
    }
}

// Get all statuses with usage stats
$statuses = $statusModel->getUsageStats();

// Get specific status for editing
$editStatus = null;
if (isset($_GET['edit'])) {
    $editStatus = $statusModel->getById($_GET['edit']);
}

$showForm = isset($_GET['action']) && $_GET['action'] === 'new' || $editStatus;
?>

<div class="page-header">
    <h1 class="page-title">Project Status Management</h1>
    <div class="page-actions">
        <a href="?action=new" class="btn btn-primary">➕ New Status</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($showForm): ?>
<div class="form-container">
    <h2><?php echo $editStatus ? 'Edit Status' : 'New Status'; ?></h2>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $editStatus ? 'update' : 'create'; ?>">
        <?php if ($editStatus): ?>
            <input type="hidden" name="id" value="<?php echo $editStatus['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="name">Status Name *</label>
            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($editStatus['name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="color">Status Color *</label>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="color" id="color" name="color" class="form-control" value="<?php echo $editStatus['color'] ?? '#007bff'; ?>" style="width: 60px; height: 40px; padding: 0;">
                <input type="text" id="color-text" class="form-control" value="<?php echo $editStatus['color'] ?? '#007bff'; ?>" style="width: 100px;">
            </div>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo ($editStatus['is_active'] ?? 1) ? 'checked' : ''; ?>>
                Active Status
            </label>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Save Status</button>
            <a href="status.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
// Sync color picker and text input
document.getElementById('color').addEventListener('input', function() {
    document.getElementById('color-text').value = this.value;
});
document.getElementById('color-text').addEventListener('input', function() {
    document.getElementById('color').value = this.value;
});
</script>

<?php else: ?>
<div class="table-container">
    <div style="margin-bottom: 20px;">
        <p style="color: #666;">Drag and drop to reorder statuses. The order will affect how they appear in dropdowns and Kanban boards.</p>
    </div>

    <table id="status-table">
        <thead>
            <tr>
                <th style="width: 30px;">Order</th>
                <th>Status Name</th>
                <th>Color</th>
                <th>Projects Using</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="status-tbody">
            <?php foreach ($statuses as $status): ?>
                <tr data-status-id="<?php echo $status['id']; ?>" style="cursor: move;">
                    <td>
                        <span style="color: #666;">⋮⋮</span>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="status-badge" style="background: <?php echo $status['color']; ?>; color: white; padding: 4px 8px; border-radius: 3px;">
                                <?php echo htmlspecialchars($status['name']); ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 20px; height: 20px; background: <?php echo $status['color']; ?>; border-radius: 3px;"></div>
                            <span style="font-family: monospace; font-size: 12px;"><?php echo $status['color']; ?></span>
                        </div>
                    </td>
                    <td>
                        <?php if ($status['project_count'] > 0): ?>
                            <a href="projects.php?status=<?php echo $status['id']; ?>" style="color: #667eea;">
                                <?php echo $status['project_count']; ?> project(s)
                            </a>
                        <?php else: ?>
                            <span style="color: #999;">0 projects</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($status['is_active']): ?>
                            <span style="color: #28a745;">✓ Active</span>
                        <?php else: ?>
                            <span style="color: #dc3545;">✗ Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?edit=<?php echo $status['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <?php if ($status['project_count'] == 0): ?>
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this status?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $status['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Drag and Drop Script -->
<script>
let draggedElement = null;

document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('status-tbody');
    const rows = tbody.querySelectorAll('tr');

    rows.forEach(row => {
        row.draggable = true;

        row.addEventListener('dragstart', function(e) {
            draggedElement = this;
            this.style.opacity = '0.5';
        });

        row.addEventListener('dragend', function(e) {
            this.style.opacity = '';
            draggedElement = null;
        });

        row.addEventListener('dragover', function(e) {
            e.preventDefault();
        });

        row.addEventListener('drop', function(e) {
            e.preventDefault();
            if (draggedElement !== this) {
                const allRows = Array.from(tbody.querySelectorAll('tr'));
                const draggedIndex = allRows.indexOf(draggedElement);
                const targetIndex = allRows.indexOf(this);

                if (draggedIndex < targetIndex) {
                    this.parentNode.insertBefore(draggedElement, this.nextSibling);
                } else {
                    this.parentNode.insertBefore(draggedElement, this);
                }

                // Save new order
                saveStatusOrder();
            }
        });
    });
});

function saveStatusOrder() {
    const rows = document.querySelectorAll('#status-tbody tr');
    const statusOrder = Array.from(rows).map(row => row.dataset.statusId);

    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=reorder&status_order=' + encodeURIComponent(JSON.stringify(statusOrder))
    })
    .then(response => response.text())
    .then(() => {
        WorkTrack.showNotification('Status order updated!', 'success');
    })
    .catch(error => {
        WorkTrack.showNotification('Failed to update order', 'danger');
    });
}
</script>

<style>
.btn-sm {
    padding: 5px 10px;
    font-size: 13px;
}

tr:hover {
    background-color: #f8f9fa !important;
}

tr.dragging {
    opacity: 0.5;
}
</style>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>