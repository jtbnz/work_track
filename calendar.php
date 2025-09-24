<?php
$pageTitle = 'Calendar';
require_once 'includes/header.php';
require_once 'includes/models/Project.php';
require_once 'includes/models/ProjectStatus.php';

$projectModel = new Project();
$statusModel = new ProjectStatus();

// Get current month or requested month
$currentMonth = $_GET['month'] ?? date('Y-m');
$monthStart = $currentMonth . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));

// Get projects for the month
$projects = $projectModel->getCalendarData($monthStart, $monthEnd);

// Calculate calendar grid
$firstDay = new DateTime($monthStart);
$lastDay = new DateTime($monthEnd);
$startOfWeek = clone $firstDay;
$startOfWeek->modify('last sunday');
$endOfWeek = clone $lastDay;
$endOfWeek->modify('next saturday');

$calendarDays = [];
$current = clone $startOfWeek;
while ($current <= $endOfWeek) {
    $calendarDays[] = clone $current;
    $current->modify('+1 day');
}

// Group projects by date
$projectsByDate = [];
foreach ($projects as $project) {
    $startDate = $project['start_date'];
    $endDate = $project['completion_date'];

    if ($startDate) {
        $current = new DateTime($startDate);
        $end = $endDate ? new DateTime($endDate) : $current;

        while ($current <= $end) {
            $dateKey = $current->format('Y-m-d');
            if (!isset($projectsByDate[$dateKey])) {
                $projectsByDate[$dateKey] = [];
            }
            $projectsByDate[$dateKey][] = $project;
            $current->modify('+1 day');
        }
    }
}

// Navigation dates
$prevMonth = date('Y-m', strtotime($monthStart . ' -1 month'));
$nextMonth = date('Y-m', strtotime($monthStart . ' +1 month'));
?>

<div class="page-header">
    <h1 class="page-title">Calendar - <?php echo date('F Y', strtotime($monthStart)); ?></h1>
    <div class="page-actions">
        <a href="?month=<?php echo $prevMonth; ?>" class="btn btn-secondary">← Previous</a>
        <a href="calendar.php" class="btn btn-secondary">Today</a>
        <a href="?month=<?php echo $nextMonth; ?>" class="btn btn-secondary">Next →</a>
    </div>
</div>

<div class="calendar-container">
    <div class="calendar-grid">
        <!-- Day headers -->
        <div class="calendar-header">Sunday</div>
        <div class="calendar-header">Monday</div>
        <div class="calendar-header">Tuesday</div>
        <div class="calendar-header">Wednesday</div>
        <div class="calendar-header">Thursday</div>
        <div class="calendar-header">Friday</div>
        <div class="calendar-header">Saturday</div>

        <!-- Calendar days -->
        <?php foreach ($calendarDays as $day):
            $dateKey = $day->format('Y-m-d');
            $dayProjects = $projectsByDate[$dateKey] ?? [];
            $isCurrentMonth = $day->format('Y-m') === $currentMonth;
            $isToday = $day->format('Y-m-d') === date('Y-m-d');
        ?>
            <div class="calendar-day <?php echo $isCurrentMonth ? 'current-month' : 'other-month'; ?> <?php echo $isToday ? 'today' : ''; ?>"
                 data-date="<?php echo $dateKey; ?>">
                <div class="day-number"><?php echo $day->format('j'); ?></div>
                <div class="day-projects">
                    <?php foreach (array_slice($dayProjects, 0, 3) as $project): ?>
                        <div class="project-card"
                             style="background: <?php echo $project['status_color']; ?>; color: white;"
                             data-project-id="<?php echo $project['id']; ?>"
                             draggable="true"
                             title="<?php echo htmlspecialchars($project['title'] . ' - ' . ($project['client_name'] ?? 'No client')); ?>">
                            <div class="project-title"><?php echo htmlspecialchars(substr($project['title'], 0, 20) . (strlen($project['title']) > 20 ? '...' : '')); ?></div>
                            <div class="project-client"><?php echo htmlspecialchars(substr($project['client_name'] ?? '', 0, 15)); ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($dayProjects) > 3): ?>
                        <div class="more-projects">+<?php echo count($dayProjects) - 3; ?> more</div>
                    <?php endif; ?>
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
.calendar-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #dee2e6;
}

.calendar-header {
    background: #f8f9fa;
    padding: 15px;
    text-align: center;
    font-weight: 600;
    color: #495057;
}

.calendar-day {
    background: white;
    min-height: 120px;
    padding: 8px;
    position: relative;
    transition: background-color 0.3s;
}

.calendar-day:hover {
    background: #f8f9fa;
}

.calendar-day.other-month {
    background: #f8f9fa;
    color: #6c757d;
}

.calendar-day.today {
    background: #e3f2fd;
}

.calendar-day.today .day-number {
    background: #1976d2;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.day-number {
    font-weight: 600;
    margin-bottom: 5px;
}

.day-projects {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.project-card {
    padding: 4px 6px;
    border-radius: 3px;
    font-size: 11px;
    cursor: pointer;
    transition: opacity 0.3s;
}

.project-card:hover {
    opacity: 0.8;
}

.project-card.dragging {
    opacity: 0.5;
}

.project-title {
    font-weight: 600;
    line-height: 1.2;
}

.project-client {
    opacity: 0.8;
    font-size: 10px;
}

.more-projects {
    font-size: 10px;
    color: #666;
    padding: 2px;
    text-align: center;
}

.calendar-day.drag-over {
    background: #e3f2fd;
    border: 2px dashed #1976d2;
}

@media (max-width: 768px) {
    .calendar-header {
        padding: 10px 5px;
        font-size: 12px;
    }

    .calendar-day {
        min-height: 80px;
        padding: 5px;
    }

    .project-card {
        font-size: 10px;
        padding: 2px 4px;
    }
}
</style>

<script>
let draggedProject = null;
let draggedProjectData = null;

document.addEventListener('DOMContentLoaded', function() {
    // Make project cards draggable
    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
        card.addEventListener('click', handleProjectClick);
    });

    // Make calendar days drop targets
    const calendarDays = document.querySelectorAll('.calendar-day');
    calendarDays.forEach(day => {
        day.addEventListener('dragover', handleDragOver);
        day.addEventListener('drop', handleDrop);
        day.addEventListener('dragenter', handleDragEnter);
        day.addEventListener('dragleave', handleDragLeave);
    });
});

function handleDragStart(e) {
    draggedProject = this;
    draggedProjectData = {
        id: this.dataset.projectId,
        originalDate: this.closest('.calendar-day').dataset.date
    };
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    draggedProject = null;
    draggedProjectData = null;

    // Remove drag-over class from all days
    document.querySelectorAll('.calendar-day').forEach(day => {
        day.classList.remove('drag-over');
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

    if (draggedProjectData) {
        const newDate = this.dataset.date;
        if (newDate !== draggedProjectData.originalDate) {
            updateProjectDate(draggedProjectData.id, newDate);
        }
    }
}

function handleProjectClick(e) {
    e.stopPropagation();
    const projectId = this.dataset.projectId;
    loadProjectModal(projectId);
}

function updateProjectDate(projectId, newDate) {
    fetch('api/update_project_date.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            project_id: projectId,
            start_date: newDate
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            WorkTrack.showNotification('Project date updated!', 'success');
            // Reload calendar
            setTimeout(() => location.reload(), 1000);
        } else {
            WorkTrack.showNotification('Failed to update project date', 'danger');
        }
    })
    .catch(error => {
        WorkTrack.showNotification('Error updating project date', 'danger');
    });
}

function loadProjectModal(projectId) {
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