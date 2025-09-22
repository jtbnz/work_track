<?php
$pageTitle = 'Project Templates';
require_once 'includes/header.php';
require_once 'includes/models/ProjectTemplate.php';

$templateModel = new ProjectTemplate();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $data = [
            'name' => $_POST['name'],
            'default_title' => $_POST['default_title'] ?? '',
            'default_details' => $_POST['default_details'] ?? '',
            'default_fabric' => $_POST['default_fabric'] ?? '',
            'is_default' => isset($_POST['is_default']) ? 1 : 0
        ];

        if ($templateModel->create($data)) {
            $message = 'Template created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to create template.';
            $messageType = 'danger';
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $data = [
            'name' => $_POST['name'],
            'default_title' => $_POST['default_title'] ?? '',
            'default_details' => $_POST['default_details'] ?? '',
            'default_fabric' => $_POST['default_fabric'] ?? '',
            'is_default' => isset($_POST['is_default']) ? 1 : 0
        ];

        if ($templateModel->update($id, $data)) {
            $message = 'Template updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update template.';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $result = $templateModel->delete($id);

        if ($result['success']) {
            $message = 'Template deleted successfully!';
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    } elseif ($action === 'duplicate') {
        $id = $_POST['id'];
        $newName = $_POST['new_name'];

        if ($templateModel->duplicate($id, $newName)) {
            $message = 'Template duplicated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to duplicate template.';
            $messageType = 'danger';
        }
    }
}

// Get all templates with usage stats
$templates = $templateModel->getUsageStats();

// Get specific template for editing
$editTemplate = null;
if (isset($_GET['edit'])) {
    $editTemplate = $templateModel->getById($_GET['edit']);
}

$showForm = isset($_GET['action']) && $_GET['action'] === 'new' || $editTemplate;
?>

<div class="page-header">
    <h1 class="page-title">Project Templates</h1>
    <div class="page-actions">
        <a href="?action=new" class="btn btn-primary">➕ New Template</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($showForm): ?>
<div class="form-container">
    <h2><?php echo $editTemplate ? 'Edit Template' : 'New Template'; ?></h2>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $editTemplate ? 'update' : 'create'; ?>">
        <?php if ($editTemplate): ?>
            <input type="hidden" name="id" value="<?php echo $editTemplate['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="name">Template Name *</label>
            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($editTemplate['name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="default_title">Default Project Title</label>
            <input type="text" id="default_title" name="default_title" class="form-control" value="<?php echo htmlspecialchars($editTemplate['default_title'] ?? ''); ?>" placeholder="e.g., New Project">
        </div>

        <div class="form-group">
            <label for="default_details">Default Project Details</label>
            <textarea id="default_details" name="default_details" class="form-control" rows="4" placeholder="Default project description..."><?php echo htmlspecialchars($editTemplate['default_details'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="default_fabric">Default Fabric</label>
            <input type="text" id="default_fabric" name="default_fabric" class="form-control" value="<?php echo htmlspecialchars($editTemplate['default_fabric'] ?? ''); ?>" placeholder="e.g., Cotton, Polyester">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_default" <?php echo ($editTemplate['is_default'] ?? 0) ? 'checked' : ''; ?>>
                Set as Default Template
            </label>
            <small style="display: block; color: #666; margin-top: 5px;">
                The default template will be pre-selected when creating new projects.
            </small>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Save Template</button>
            <a href="templates.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Template Name</th>
                <th>Default Title</th>
                <th>Default Fabric</th>
                <th>Usage</th>
                <th>Default</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($templates as $template): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($template['name']); ?></strong>
                        <?php if ($template['is_default']): ?>
                            <span class="status-badge" style="background: #28a745; color: white; font-size: 11px; margin-left: 8px;">DEFAULT</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($template['default_title'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($template['default_fabric'] ?: '-'); ?></td>
                    <td>
                        <?php if ($template['usage_count'] > 0): ?>
                            <span style="color: #667eea;"><?php echo $template['usage_count']; ?> project(s)</span>
                        <?php else: ?>
                            <span style="color: #999;">0 projects</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($template['is_default']): ?>
                            <span style="color: #28a745;">✓ Yes</span>
                        <?php else: ?>
                            <span style="color: #999;">No</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <a href="?edit=<?php echo $template['id']; ?>" class="btn btn-sm btn-warning">Edit</a>

                            <button onclick="showDuplicateModal(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['name']); ?>')" class="btn btn-sm btn-secondary">Duplicate</button>

                            <?php if (!$template['is_default']): ?>
                                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $template['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php if ($template['default_details']): ?>
                    <tr style="background: #f8f9fa;">
                        <td colspan="6" style="padding: 10px 20px; font-size: 13px; color: #666;">
                            <strong>Default Details:</strong> <?php echo nl2br(htmlspecialchars($template['default_details'])); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (count($templates) === 0): ?>
        <p style="text-align: center; padding: 40px; color: #666;">
            No templates created yet. Create your first template!
        </p>
    <?php endif; ?>
</div>

<!-- Duplicate Modal -->
<div id="duplicate-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Duplicate Template</h3>
            <span class="modal-close">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="duplicate">
            <input type="hidden" name="id" id="duplicate-template-id">

            <div class="form-group">
                <label for="new_name">New Template Name *</label>
                <input type="text" id="new_name" name="new_name" class="form-control" required>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="hideDuplicateModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Duplicate</button>
            </div>
        </form>
    </div>
</div>

<script>
function showDuplicateModal(templateId, templateName) {
    document.getElementById('duplicate-template-id').value = templateId;
    document.getElementById('new_name').value = templateName + ' Copy';
    document.getElementById('duplicate-modal').style.display = 'block';
}

function hideDuplicateModal() {
    document.getElementById('duplicate-modal').style.display = 'none';
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('duplicate-modal');
    if (event.target === modal) {
        hideDuplicateModal();
    }
});
</script>

<style>
.btn-sm {
    padding: 5px 10px;
    font-size: 13px;
}
</style>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>