<?php
$pageTitle = 'Project Details';
require_once 'includes/header.php';
require_once 'includes/models/Project.php';

if (!isset($_GET['id'])) {
    header('Location: projects.php');
    exit;
}

$projectModel = new Project();
$project = $projectModel->getById($_GET['id']);

if (!$project) {
    header('Location: projects.php');
    exit;
}

$attachments = $projectModel->getAttachments($project['id']);
?>

<div class="page-header">
    <h1 class="page-title">Project: <?php echo htmlspecialchars($project['title']); ?></h1>
    <div class="page-actions">
        <a href="projects.php?edit=<?php echo $project['id']; ?>" class="btn btn-warning">Edit Project</a>
        <a href="projects.php" class="btn btn-secondary">← Back to Projects</a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <h3>Project Information</h3>
        <div style="display: grid; gap: 10px; margin-top: 15px;">
            <div><strong>Client:</strong> <?php echo htmlspecialchars($project['client_name'] ?? 'No client'); ?></div>
            <div><strong>Status:</strong> <span class="status-badge" style="background: <?php echo $project['status_color']; ?>; color: white;"><?php echo htmlspecialchars($project['status_name']); ?></span></div>
            <div><strong>Start Date:</strong> <?php echo $project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : 'Not set'; ?></div>
            <div><strong>Completion Date:</strong> <?php echo $project['completion_date'] ? date('M j, Y', strtotime($project['completion_date'])) : 'Not set'; ?></div>
            <div><strong>Fabric:</strong> <?php echo htmlspecialchars($project['fabric'] ?: 'Not specified'); ?></div>
        </div>
    </div>

    <div class="dashboard-card">
        <h3>Project Details</h3>
        <div style="margin-top: 15px;">
            <?php echo nl2br(htmlspecialchars($project['details'] ?: 'No details provided')); ?>
        </div>
    </div>
</div>

<!-- File Attachments Section -->
<div class="form-container" style="margin-top: 30px;">
    <h2>File Attachments</h2>

    <div style="margin: 20px 0;">
        <form id="upload-form" enctype="multipart/form-data">
            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="file" name="file" id="file-input" class="form-control" style="max-width: 400px;">
                <button type="submit" class="btn btn-primary">Upload File</button>
            </div>
            <small style="color: #666; margin-top: 5px; display: block;">
                Allowed types: <?php echo implode(', ', ALLOWED_FILE_TYPES); ?> | Max size: <?php echo (MAX_UPLOAD_SIZE / 1048576); ?>MB
            </small>
        </form>
    </div>

    <div id="attachments-list">
        <?php if (count($attachments) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Uploaded By</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attachments as $attachment): ?>
                        <tr data-attachment-id="<?php echo $attachment['id']; ?>">
                            <td>
                                <a href="api/download_file.php?id=<?php echo $attachment['id']; ?>" style="color: #667eea;">
                                    📎 <?php echo htmlspecialchars($attachment['filename']); ?>
                                </a>
                            </td>
                            <td><?php echo number_format($attachment['file_size'] / 1024, 1); ?> KB</td>
                            <td><?php echo htmlspecialchars($attachment['uploaded_by_name']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($attachment['uploaded_at'])); ?></td>
                            <td>
                                <button onclick="deleteAttachment(<?php echo $attachment['id']; ?>)" class="btn btn-sm btn-danger">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #666; text-align: center; padding: 20px;">No attachments uploaded yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('upload-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const fileInput = document.getElementById('file-input');

    if (!fileInput.files[0]) {
        WorkTrack.showNotification('Please select a file to upload', 'warning');
        return;
    }

    fetch('api/upload_file.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            WorkTrack.showNotification('File uploaded successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            WorkTrack.showNotification(data.message || 'Failed to upload file', 'danger');
        }
    })
    .catch(error => {
        WorkTrack.showNotification('Error uploading file', 'danger');
    });
});

function deleteAttachment(attachmentId) {
    if (!confirm('Are you sure you want to delete this attachment?')) {
        return;
    }

    fetch('api/delete_file.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ attachment_id: attachmentId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            WorkTrack.showNotification('Attachment deleted!', 'success');
            document.querySelector(`tr[data-attachment-id="${attachmentId}"]`).remove();

            // Check if no attachments left
            if (document.querySelectorAll('#attachments-list tbody tr').length === 0) {
                document.getElementById('attachments-list').innerHTML =
                    '<p style="color: #666; text-align: center; padding: 20px;">No attachments uploaded yet.</p>';
            }
        } else {
            WorkTrack.showNotification('Failed to delete attachment', 'danger');
        }
    })
    .catch(error => {
        WorkTrack.showNotification('Error deleting attachment', 'danger');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>