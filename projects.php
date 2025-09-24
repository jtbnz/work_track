<?php
$pageTitle = 'Projects';
require_once 'includes/header.php';
require_once 'includes/models/Project.php';
require_once 'includes/models/Client.php';
require_once 'includes/models/ProjectStatus.php';
require_once 'includes/models/ProjectTemplate.php';

$projectModel = new Project();
$clientModel = new Client();
$statusModel = new ProjectStatus();
$templateModel = new ProjectTemplate();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $data = [
            'title' => $_POST['title'],
            'details' => $_POST['details'] ?? '',
            'client_id' => $_POST['client_id'] ?: null,
            'start_date' => $_POST['start_date'] ?: null,
            'completion_date' => $_POST['completion_date'] ?: null,
            'status_id' => $_POST['status_id'],
            'fabric' => $_POST['fabric'] ?? '',
            'template_id' => $_POST['template_id'] ?: null
        ];

        if ($projectModel->create($data)) {
            $message = 'Project created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to create project.';
            $messageType = 'danger';
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $data = [
            'title' => $_POST['title'],
            'details' => $_POST['details'] ?? '',
            'client_id' => $_POST['client_id'] ?: null,
            'start_date' => $_POST['start_date'] ?: null,
            'completion_date' => $_POST['completion_date'] ?: null,
            'status_id' => $_POST['status_id'],
            'fabric' => $_POST['fabric'] ?? ''
        ];

        if ($projectModel->update($id, $data)) {
            $message = 'Project updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update project.';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        if ($projectModel->delete($id)) {
            $message = 'Project deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete project.';
            $messageType = 'danger';
        }
    }
}

// Get filters
$filters = [
    'client_id' => $_GET['client'] ?? '',
    'status_id' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Get data for dropdowns
$clients = $clientModel->getAll();
$statuses = $statusModel->getAll();
$templates = $templateModel->getAll();

// Get projects with filters
$projects = $projectModel->getAll($filters);

// Get specific project for editing
$editProject = null;
if (isset($_GET['edit'])) {
    $editProject = $projectModel->getById($_GET['edit']);
}

$showForm = isset($_GET['action']) && $_GET['action'] === 'new' || $editProject;
?>

<div class="page-header">
    <h1 class="page-title">Projects</h1>
    <div class="page-actions">
        <a href="status.php" class="btn btn-secondary">Manage Statuses</a>
        <a href="templates.php" class="btn btn-secondary">Templates</a>
        <a href="?action=new" class="btn btn-primary">➕ New Project</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($showForm): ?>
<div class="form-container">
    <h2><?php echo $editProject ? 'Edit Project' : 'New Project'; ?></h2>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $editProject ? 'update' : 'create'; ?>">
        <?php if ($editProject): ?>
            <input type="hidden" name="id" value="<?php echo $editProject['id']; ?>">
        <?php endif; ?>

        <?php if (!$editProject): ?>
        <div class="form-group">
            <label for="template_id">Template (optional)</label>
            <select id="template_id" name="template_id" class="form-control" onchange="loadTemplate()">
                <option value="">-- Select Template --</option>
                <?php foreach ($templates as $template): ?>
                    <option value="<?php echo $template['id']; ?>" <?php echo $template['is_default'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($template['name']); ?>
                        <?php echo $template['is_default'] ? ' (Default)' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="title">Project Title *</label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($editProject['title'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="client_id">Client</label>
            <div style="display: flex; gap: 10px; align-items: center;">
                <select id="client_id" name="client_id" class="form-control" style="flex: 1;">
                    <option value="">-- No Client --</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo ($editProject['client_id'] ?? '') == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" onclick="showQuickClientModal()" class="btn btn-secondary">➕ New</button>
            </div>
        </div>

        <div class="form-group">
            <label for="status_id">Status *</label>
            <select id="status_id" name="status_id" class="form-control" required>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo $status['id']; ?>" <?php echo ($editProject['status_id'] ?? '') == $status['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($status['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $editProject['start_date'] ?? ''; ?>">
        </div>

        <div class="form-group">
            <label for="completion_date">Completion Date</label>
            <input type="date" id="completion_date" name="completion_date" class="form-control" value="<?php echo $editProject['completion_date'] ?? ''; ?>">
        </div>

        <div class="form-group">
            <label for="fabric">Fabric</label>
            <input type="text" id="fabric" name="fabric" class="form-control" value="<?php echo htmlspecialchars($editProject['fabric'] ?? ''); ?>" placeholder="e.g., Cotton, Polyester">
        </div>

        <div class="form-group">
            <label for="details">Project Details</label>
            <textarea id="details" name="details" class="form-control" rows="4"><?php echo htmlspecialchars($editProject['details'] ?? ''); ?></textarea>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Save Project</button>
            <a href="projects.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
// Template data for loading
const templates = <?php echo json_encode($templates); ?>;

function loadTemplate() {
    const templateId = document.getElementById('template_id').value;
    if (!templateId) return;

    const template = templates.find(t => t.id == templateId);
    if (template) {
        if (template.default_title) document.getElementById('title').value = template.default_title;
        if (template.default_details) document.getElementById('details').value = template.default_details;
        if (template.default_fabric) document.getElementById('fabric').value = template.default_fabric;
    }
}

// Load default template on page load
document.addEventListener('DOMContentLoaded', function() {
    const templateSelect = document.getElementById('template_id');
    if (templateSelect && templateSelect.value) {
        loadTemplate();
    }
});

// Quick Client Creation
function showQuickClientModal() {
    document.getElementById('quick-client-modal').style.display = 'block';
}

function hideQuickClientModal() {
    document.getElementById('quick-client-modal').style.display = 'none';
    document.getElementById('quick-client-form').reset();
}

function createQuickClient(event) {
    event.preventDefault();

    const form = document.getElementById('quick-client-form');
    const formData = new FormData(form);

    fetch('/work_track/api/quick_client.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add the new client to the dropdown
            const select = document.getElementById('client_id');
            const option = document.createElement('option');
            option.value = data.client_id;
            option.text = data.client_name;
            option.selected = true;
            select.appendChild(option);

            hideQuickClientModal();
            WorkTrack.showNotification('Client created successfully!', 'success');
        } else {
            WorkTrack.showNotification(data.message || 'Failed to create client', 'danger');
        }
    })
    .catch(error => {
        WorkTrack.showNotification('Error creating client', 'danger');
    });

    return false;
}
</script>

<!-- Quick Client Modal -->
<div id="quick-client-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Quick Client Creation</h3>
            <span class="modal-close" onclick="hideQuickClientModal()">&times;</span>
        </div>
        <form id="quick-client-form" onsubmit="return createQuickClient(event)">
            <div class="form-group">
                <label for="quick_client_name">Client Name *</label>
                <input type="text" id="quick_client_name" name="name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="quick_client_email">Email</label>
                <input type="email" id="quick_client_email" name="email" class="form-control">
            </div>

            <div class="form-group">
                <label for="quick_client_phone">Phone</label>
                <input type="tel" id="quick_client_phone" name="phone" class="form-control">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="hideQuickClientModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Client</button>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Filters -->
<div class="form-container" style="margin-bottom: 20px;">
    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label for="search">Search</label>
            <input type="text" id="search" name="search" class="form-control" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Title, details, fabric...">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label for="client">Client</label>
            <select id="client" name="client" class="form-control">
                <option value="">All Clients</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['id']; ?>" <?php echo $filters['client_id'] == $client['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($client['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo $status['id']; ?>" <?php echo $filters['status_id'] == $status['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($status['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="projects.php" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Projects Table -->
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Project</th>
                <th>Client</th>
                <th>Status</th>
                <th>Dates</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                        <?php if ($project['fabric']): ?>
                            <br><small style="color: #666;">Fabric: <?php echo htmlspecialchars($project['fabric']); ?></small>
                        <?php endif; ?>
                        <?php if ($project['details']): ?>
                            <br><small style="color: #666;"><?php echo htmlspecialchars(substr($project['details'], 0, 60) . (strlen($project['details']) > 60 ? '...' : '')); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($project['client_name']): ?>
                            <a href="clients.php?edit=<?php echo $project['client_id']; ?>" style="color: #667eea;">
                                <?php echo htmlspecialchars($project['client_name']); ?>
                            </a>
                        <?php else: ?>
                            <span style="color: #999;">No client</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge clickable-status"
                              data-project-id="<?php echo $project['id']; ?>"
                              data-current-status="<?php echo $project['status_id']; ?>"
                              style="background: <?php echo $project['status_color']; ?>; color: white; cursor: pointer;"
                              title="Click to change status">
                            <?php echo htmlspecialchars($project['status_name']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($project['start_date']): ?>
                            <small>Start: <?php echo date('M j, Y', strtotime($project['start_date'])); ?></small>
                        <?php endif; ?>
                        <?php if ($project['completion_date']): ?>
                            <br><small>End: <?php echo date('M j, Y', strtotime($project['completion_date'])); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo date('M j, Y', strtotime($project['created_date'])); ?>
                        <br><small style="color: #666;">by <?php echo htmlspecialchars($project['created_by_name']); ?></small>
                    </td>
                    <td>
                        <a href="?edit=<?php echo $project['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this project?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (count($projects) === 0): ?>
        <p style="text-align: center; padding: 40px; color: #666;">
            <?php if (array_filter($filters)): ?>
                No projects found matching your filters.
            <?php else: ?>
                No projects yet. Create your first project!
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<!-- Status Change Modal -->
<div id="status-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Change Project Status</h3>
            <span class="modal-close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Select a new status for this project:</p>
            <div id="status-options">
                <?php foreach ($statuses as $status): ?>
                    <label class="status-option">
                        <input type="radio" name="new_status" value="<?php echo $status['id']; ?>">
                        <span class="status-label" style="background: <?php echo $status['color']; ?>;">
                            <?php echo htmlspecialchars($status['name']); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <button class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                <button class="btn btn-primary" onclick="updateProjectStatus()">Update Status</button>
            </div>
        </div>
    </div>
</div>

<style>
.btn-sm {
    padding: 5px 10px;
    font-size: 13px;
}

.clickable-status {
    transition: transform 0.2s, box-shadow 0.2s;
}

.clickable-status:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    margin: 0;
    font-size: 20px;
}

.modal-close {
    font-size: 28px;
    cursor: pointer;
    color: #999;
}

.modal-close:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
}

#status-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 15px;
}

.status-option {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 5px;
    border-radius: 5px;
}

.status-option:hover {
    background: #f5f5f5;
}

.status-option input[type="radio"] {
    margin-right: 10px;
}

.status-label {
    padding: 8px 15px;
    border-radius: 15px;
    color: white;
    font-size: 14px;
    display: inline-block;
}
</style>

<script>
let currentProjectId = null;

// Add click handlers to status badges
document.addEventListener('DOMContentLoaded', function() {
    const statusBadges = document.querySelectorAll('.clickable-status');
    statusBadges.forEach(badge => {
        badge.addEventListener('click', function() {
            currentProjectId = this.dataset.projectId;
            const currentStatus = this.dataset.currentStatus;

            // Pre-select current status
            const radios = document.querySelectorAll('input[name="new_status"]');
            radios.forEach(radio => {
                radio.checked = radio.value === currentStatus;
            });

            // Show modal
            document.getElementById('status-modal').style.display = 'flex';
        });
    });

    // Close modal handlers
    document.querySelector('.modal-close').addEventListener('click', closeStatusModal);
    document.getElementById('status-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeStatusModal();
        }
    });
});

function closeStatusModal() {
    document.getElementById('status-modal').style.display = 'none';
}

function updateProjectStatus() {
    const selectedStatus = document.querySelector('input[name="new_status"]:checked');
    if (!selectedStatus) {
        alert('Please select a status');
        return;
    }

    const statusId = selectedStatus.value;

    // Call API to update status
    fetch('/work_track/api/update_project_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            project_id: currentProjectId,
            status_id: statusId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to show updated status
            location.reload();
        } else {
            alert('Error updating status: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error updating status: ' + error.message);
    });
}
</script>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>