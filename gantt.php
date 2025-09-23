<?php
$pageTitle = 'Gantt Chart';
require_once 'includes/header.php';
require_once 'includes/models/Project.php';
require_once 'includes/models/Client.php';
require_once 'includes/models/ProjectStatus.php';

$projectModel = new Project();
$clientModel = new Client();
$statusModel = new ProjectStatus();

// Get filters
$filters = [
    'client_id' => $_GET['client'] ?? '',
    'status_id' => $_GET['status'] ?? ''
];

// Get data for dropdowns
$clients = $clientModel->getAll();
$statuses = $statusModel->getAll();

// Get view mode (default to month)
$viewMode = $_GET['view'] ?? 'month';

// Get selected date or default to today
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$currentDate = date('Y-m-d');

// Calculate date range based on selected date and view mode
switch ($viewMode) {
    case 'week':
        // Start from Monday of the selected week
        $dayOfWeek = date('w', strtotime($selectedDate));
        $daysToMonday = ($dayOfWeek == 0) ? -6 : (1 - $dayOfWeek);
        $startDate = date('Y-m-d', strtotime($selectedDate . " $daysToMonday days"));
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));
        $prevStart = date('Y-m-d', strtotime($startDate . ' -7 days'));
        $nextStart = date('Y-m-d', strtotime($startDate . ' +7 days'));
        break;
    case 'day':
        $startDate = $selectedDate;
        $endDate = $selectedDate;
        $prevStart = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $nextStart = date('Y-m-d', strtotime($startDate . ' +1 day'));
        break;
    default: // month
        $startDate = date('Y-m-01', strtotime($selectedDate));
        $endDate = date('Y-m-t', strtotime($selectedDate));
        $prevStart = date('Y-m-01', strtotime($startDate . ' -1 month'));
        $nextStart = date('Y-m-01', strtotime($startDate . ' +1 month'));
        break;
}

// Get projects
$projects = $projectModel->getCalendarData($startDate, $endDate);

// Filter projects
if ($filters['client_id']) {
    $projects = array_filter($projects, function($p) use ($filters) {
        return $p['client_id'] == $filters['client_id'];
    });
}
if ($filters['status_id']) {
    $projects = array_filter($projects, function($p) use ($filters) {
        return $p['status_id'] == $filters['status_id'];
    });
}

// Calculate grid
$startTime = strtotime($startDate);
$endTime = strtotime($endDate);
$totalDays = ($endTime - $startTime) / 86400 + 1;
?>

<div class="page-header">
    <h1 class="page-title">Gantt Chart</h1>
    <div class="page-actions">
        <a href="?view=<?php echo $viewMode; ?>&date=<?php echo $prevStart; ?>" class="btn btn-secondary">← Previous</a>

        <input type="date" id="datePicker" value="<?php echo $selectedDate; ?>" class="form-control" style="width: auto;">

        <a href="?view=day&date=<?php echo $selectedDate; ?>" class="btn btn-secondary <?php echo $viewMode == 'day' ? 'active' : ''; ?>">Day</a>
        <a href="?view=week&date=<?php echo $selectedDate; ?>" class="btn btn-secondary <?php echo $viewMode == 'week' ? 'active' : ''; ?>">Week</a>
        <a href="?view=month&date=<?php echo $selectedDate; ?>" class="btn btn-secondary <?php echo $viewMode == 'month' ? 'active' : ''; ?>">Month</a>

        <a href="?view=<?php echo $viewMode; ?>&date=<?php echo $nextStart; ?>" class="btn btn-secondary">Next →</a>

        <a href="?view=<?php echo $viewMode; ?>&date=<?php echo $currentDate; ?>" class="btn btn-primary">Today</a>
    </div>
</div>

<!-- Filters -->
<div class="form-container" style="margin-bottom: 20px;">
    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
        <input type="hidden" name="view" value="<?php echo $viewMode; ?>">
        <input type="hidden" name="date" value="<?php echo $selectedDate; ?>">

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
            <a href="?view=<?php echo $viewMode; ?>" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Gantt Chart -->
<div class="gantt-container">
    <div class="gantt-chart">
        <!-- Timeline Header -->
        <div class="gantt-header">
            <div class="gantt-row-header">Project</div>
            <div class="gantt-timeline">
                <?php if ($viewMode == 'month'): ?>
                    <!-- Month label row -->
                    <div class="month-label-row">
                        <div class="month-label" style="width: 100%; text-align: center; font-weight: bold; padding: 5px;">
                            <?php echo date('F Y', strtotime($startDate)); ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php
                $prevMonth = '';
                for ($i = 0; $i < $totalDays; $i++) {
                    $date = date('Y-m-d', strtotime($startDate . " +$i days"));
                    $isToday = $date == $currentDate;
                    $isWeekend = in_array(date('w', strtotime($date)), [0, 6]);
                    $currentMonth = date('M', strtotime($date));
                ?>
                    <div class="gantt-day <?php echo $isToday ? 'today' : ''; ?> <?php echo $isWeekend ? 'weekend' : ''; ?>">
                        <?php if ($viewMode == 'month'): ?>
                            <div class="day-number"><?php echo date('j', strtotime($date)); ?></div>
                            <div class="day-name"><?php echo date('D', strtotime($date)); ?></div>
                        <?php elseif ($viewMode == 'week'): ?>
                            <div><?php echo date('D', strtotime($date)); ?></div>
                            <div style="font-weight: bold;"><?php echo date('M j', strtotime($date)); ?></div>
                        <?php else: // day view ?>
                            <div><?php echo date('D, M j', strtotime($date)); ?></div>
                        <?php endif; ?>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- Project Rows -->
        <?php foreach ($projects as $project):
            $projStart = strtotime($project['start_date']);
            $projEnd = $project['completion_date'] ? strtotime($project['completion_date']) : $projStart;

            // Calculate bar position and width
            if ($projStart < $startTime) {
                $barStart = 0;
                $actualStart = $startTime;
            } else {
                $barStart = ($projStart - $startTime) / 86400;
                $actualStart = $projStart;
            }

            if ($projEnd > $endTime) {
                $actualEnd = $endTime;
            } else {
                $actualEnd = $projEnd;
            }

            $barDuration = ($actualEnd - $actualStart) / 86400 + 1;
            $barWidth = ($barDuration / $totalDays) * 100;
            $barLeft = ($barStart / $totalDays) * 100;
        ?>
            <div class="gantt-row">
                <div class="gantt-row-header">
                    <div class="project-info">
                        <strong><?php echo htmlspecialchars(substr($project['title'], 0, 30)); ?></strong>
                        <br>
                        <small><?php echo htmlspecialchars($project['client_name'] ?? 'No client'); ?></small>
                    </div>
                </div>
                <div class="gantt-timeline">
                    <div class="gantt-bar"
                         style="left: <?php echo $barLeft; ?>%; width: <?php echo $barWidth; ?>%; background: <?php echo $project['status_color']; ?>;"
                         title="<?php echo htmlspecialchars($project['title']); ?> (<?php echo date('M j', $projStart); ?> - <?php echo date('M j', $projEnd); ?>)">
                        <span class="bar-label">
                            <?php echo htmlspecialchars($project['status_name']); ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (count($projects) == 0): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                No projects found for the selected date range and filters.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const datePicker = document.getElementById('datePicker');
    if (datePicker) {
        datePicker.addEventListener('change', function() {
            const currentView = '<?php echo $viewMode; ?>';
            window.location.href = '?view=' + currentView + '&date=' + this.value;
        });
    }
});
</script>

<style>
.gantt-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow-x: auto;
}

.gantt-chart {
    min-width: 800px;
}

.gantt-header {
    display: flex;
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    position: relative;
    margin-top: 35px;
}

.gantt-row {
    display: flex;
    border-bottom: 1px solid #dee2e6;
    min-height: 50px;
}

.gantt-row:hover {
    background: #f8f9fa;
}

.gantt-row-header {
    width: 200px;
    padding: 10px;
    border-right: 1px solid #dee2e6;
    flex-shrink: 0;
}

.gantt-timeline {
    flex: 1;
    display: flex;
    position: relative;
}

.gantt-header .gantt-timeline {
    display: flex;
    flex-wrap: wrap;
    position: relative;
}

.month-label-row {
    position: absolute;
    top: -30px;
    left: 0;
    right: 0;
    background: #667eea;
    color: white;
    border-radius: 5px 5px 0 0;
    height: 30px;
    display: flex;
    align-items: center;
}

.gantt-day {
    flex: 1;
    text-align: center;
    padding: 5px 2px;
    border-right: 1px solid #eee;
    font-size: 11px;
    min-width: 30px;
}

.gantt-day .day-number {
    font-weight: bold;
    font-size: 13px;
}

.gantt-day .day-name {
    font-size: 10px;
    color: #666;
    margin-top: 2px;
}

.gantt-day.today {
    background: #e3f2fd;
}

.gantt-day.weekend {
    background: #f5f5f5;
}

.gantt-row .gantt-timeline {
    position: relative;
}

.gantt-bar {
    position: absolute;
    top: 10px;
    height: 30px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    padding: 0 8px;
    cursor: pointer;
    transition: opacity 0.3s;
    overflow: hidden;
}

.gantt-bar:hover {
    opacity: 0.8;
}

.bar-label {
    color: white;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.project-info {
    font-size: 13px;
    line-height: 1.3;
}

.btn.active {
    background: #667eea;
    color: white;
}

@media (max-width: 768px) {
    .gantt-row-header {
        width: 150px;
    }

    .project-info {
        font-size: 12px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>