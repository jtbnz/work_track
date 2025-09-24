<?php
$pageTitle = 'Kanban Board';
require_once 'includes/header.php';
require_once 'includes/models/Project.php';
require_once 'includes/models/ProjectStatus.php';

$projectModel = new Project();
$statusModel = new ProjectStatus();

// Get all active statuses
$statuses = $statusModel->getAll();

// Get projects grouped by status
$projectsByStatus = [];
foreach ($statuses as $status) {
    $projectsByStatus[$status['id']] = $projectModel->getByStatus($status['id']);
}

$message = '';
if (isset($_SESSION['kanban_message'])) {
    $message = $_SESSION['kanban_message'];
    unset($_SESSION['kanban_message']);
}
?>

<div class="page-header">
    <h1 class="page-title">Kanban Board</h1>
    <div class="page-actions">
        <a href="projects.php?action=new" class="btn btn-primary">➕ New Project</a>
        <a href="status.php" class="btn btn-secondary">Manage Statuses</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="kanban-container">
    <div class="kanban-board">
        <?php foreach ($statuses as $status): ?>
            <div class="kanban-column" data-status-id="<?php echo $status['id']; ?>">
                <div class="column-header" style="background: <?php echo $status['color']; ?>;">
                    <h3 class="column-title"><?php echo htmlspecialchars($status['name']); ?></h3>
                    <div class="column-count"><?php echo count($projectsByStatus[$status['id']]); ?></div>
                </div>
                <div class="column-body" data-status-id="<?php echo $status['id']; ?>">
                    <?php foreach ($projectsByStatus[$status['id']] as $project): ?>
                        <div class="project-card"
                             data-project-id="<?php echo $project['id']; ?>"
                             data-status-id="<?php echo $project['status_id']; ?>"
                             draggable="true">
                            <div class="card-header">
                                <h4 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h4>
                                <div class="card-actions">
                                    <button onclick="editProject(<?php echo $project['id']; ?>)" class="btn-icon">✏️</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($project['client_name']): ?>
                                    <div class="card-client">
                                        <span class="client-icon">👤</span>
                                        <?php echo htmlspecialchars($project['client_name']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($project['start_date'] || $project['completion_date']): ?>
                                    <div class="card-dates">
                                        <?php if ($project['start_date']): ?>
                                            <span class="date-start">📅 <?php echo date('M j', strtotime($project['start_date'])); ?></span>
                                        <?php endif; ?>
                                        <?php if ($project['completion_date']): ?>
                                            <span class="date-end">🏁 <?php echo date('M j', strtotime($project['completion_date'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($project['fabric']): ?>
                                    <div class="card-fabric">
                                        <span class="fabric-icon">🧵</span>
                                        <?php echo htmlspecialchars($project['fabric']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($project['details']): ?>
                                    <div class="card-details">
                                        <?php echo htmlspecialchars(substr($project['details'], 0, 80) . (strlen($project['details']) > 80 ? '...' : '')); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted">
                                    Updated <?php echo date('M j', strtotime($project['updated_at'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Project Edit Modal -->
<div id="project-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Project</h3>
            <span class="modal-close">&times;</span>
        </div>
        <div id="modal-body">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<style>
.kanban-container {
    padding: 20px 0;
}

.kanban-board {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    padding-bottom: 20px;
}

.kanban-column {
    background: #f8f9fa;
    border-radius: 10px;
    min-width: 300px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.column-header {
    padding: 15px 20px;
    border-radius: 10px 10px 0 0;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.column-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
}

.column-count {
    background: rgba(255,255,255,0.2);
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.column-body {
    padding: 15px;
    min-height: 500px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.column-body.drag-over {
    background: #e3f2fd;
    border: 2px dashed #1976d2;
    border-radius: 8px;
}

.project-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.3s;
    border-left: 4px solid transparent;
}

.project-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.project-card.dragging {
    opacity: 0.7;
    transform: rotate(3deg);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.card-title {
    font-size: 14px;
    font-weight: 600;
    margin: 0;
    color: #333;
    line-height: 1.3;
}

.card-actions {
    display: flex;
    gap: 5px;
}

.btn-icon {
    background: none;
    border: none;
    padding: 2px;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.3s;
}

.btn-icon:hover {
    opacity: 1;
}

.card-body {
    margin-bottom: 10px;
}

.card-client {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
}

.card-dates {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 11px;
    color: #666;
    margin-bottom: 8px;
}

.card-fabric {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
}

.card-details {
    font-size: 12px;
    color: #666;
    line-height: 1.4;
    margin-bottom: 8px;
}

.card-footer {
    border-top: 1px solid #eee;
    padding-top: 8px;
}

.text-muted {
    color: #999;
    font-size: 11px;
}

/* Responsive */
@media (max-width: 768px) {
    .kanban-board {
        flex-direction: column;
    }

    .kanban-column {
        min-width: auto;
    }

    .column-body {
        min-height: 300px;
    }
}
</style>

<script>
let draggedCard = null;
let draggedCardData = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeKanban();
});

function initializeKanban() {
    // Make project cards draggable
    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });

    // Make column bodies drop targets
    const columnBodies = document.querySelectorAll('.column-body');
    columnBodies.forEach(column => {
        column.addEventListener('dragover', handleDragOver);
        column.addEventListener('drop', handleDrop);
        column.addEventListener('dragenter', handleDragEnter);
        column.addEventListener('dragleave', handleDragLeave);
    });
}

function handleDragStart(e) {
    draggedCard = this;
    draggedCardData = {
        id: this.dataset.projectId,
        originalStatusId: this.dataset.statusId
    };
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    draggedCard = null;
    draggedCardData = null;

    // Remove drag-over class from all columns
    document.querySelectorAll('.column-body').forEach(column => {
        column.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDragEnter(e) {
    e.preventDefault();
    this.classList.add('drag-over');
}

function handleDragLeave(e) {
    if (!this.contains(e.relatedTarget)) {
        this.classList.remove('drag-over');
    }
}

function handleDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');

    if (draggedCardData) {
        const newStatusId = this.dataset.statusId;
        if (newStatusId !== draggedCardData.originalStatusId) {
            updateProjectStatus(draggedCardData.id, newStatusId);
        }
    }
}

function updateProjectStatus(projectId, statusId) {
    // Store reference to card before async operation (before it gets cleared)
    const cardToMove = document.querySelector(`.project-card[data-project-id="${projectId}"]`);

    fetch('api/update_project_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            project_id: projectId,
            status_id: statusId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            WorkTrack.showNotification('Project status updated!', 'success');
            // Move the card to the new column using stored reference
            if (cardToMove) {
                moveCardToColumn(cardToMove, statusId);
            }
            updateColumnCounts();
        } else {
            WorkTrack.showNotification('Failed to update project status', 'danger');
        }
    })
    .catch(error => {
        console.error('Error updating project status:', error);
        WorkTrack.showNotification('Error updating project status', 'danger');
    });
}

function moveCardToColumn(card, statusId) {
    const targetColumn = document.querySelector(`.column-body[data-status-id="${statusId}"]`);
    if (targetColumn) {
        card.dataset.statusId = statusId;
        targetColumn.appendChild(card);
    }
}

function updateColumnCounts() {
    document.querySelectorAll('.kanban-column').forEach(column => {
        const statusId = column.dataset.statusId;
        const count = column.querySelectorAll('.project-card').length;
        const countElement = column.querySelector('.column-count');
        if (countElement) {
            countElement.textContent = count;
        }
    });
}

function editProject(projectId) {
    const modal = document.getElementById('project-modal');
    const modalBody = document.getElementById('modal-body');

    modalBody.innerHTML = '<div style="text-align: center; padding: 20px;">Loading...</div>';
    modal.style.display = 'block';

    fetch(`/api/get_project.php?id=${projectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalBody.innerHTML = generateProjectForm(data.project);
            } else {
                modalBody.innerHTML = '<div class="alert alert-danger">Failed to load project</div>';
            }
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading project</div>';
        });
}

function generateProjectForm(project) {
    return `
        <form onsubmit="saveProject(event, ${project.id})">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control" value="${escapeHtml(project.title)}" required>
            </div>
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" value="${project.start_date || ''}">
            </div>
            <div class="form-group">
                <label>Completion Date</label>
                <input type="date" name="completion_date" class="form-control" value="${project.completion_date || ''}">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    `;
}

function saveProject(event, projectId) {
    event.preventDefault();
    const formData = new FormData(event.target);

    fetch('api/update_project.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Project-ID': projectId
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            WorkTrack.showNotification('Project updated!', 'success');
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            WorkTrack.showNotification('Failed to update project', 'danger');
        }
    })
    .catch(error => {
        WorkTrack.showNotification('Error updating project', 'danger');
    });
}

function closeModal() {
    document.getElementById('project-modal').style.display = 'none';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once 'includes/footer.php'; ?>