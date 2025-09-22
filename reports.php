<?php
$pageTitle = 'Reports';
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
    'status_id' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Get data for dropdowns
$clients = $clientModel->getAll();
$statuses = $statusModel->getAll();

// Get projects with filters for reporting
$projects = $projectModel->getAll($filters);
?>

<div class="page-header">
    <h1 class="page-title">Reports & Analytics</h1>
    <div class="page-actions">
        <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Report</button>
        <button onclick="exportToCSV()" class="btn btn-primary">📥 Export to CSV</button>
    </div>
</div>

<!-- Filter Section -->
<div class="form-container" style="margin-bottom: 20px;">
    <h3>Report Filters</h3>
    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
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
        <div class="form-group" style="margin-bottom: 0;">
            <label for="date_from">From Date</label>
            <input type="date" id="date_from" name="date_from" class="form-control" value="<?php echo $filters['date_from']; ?>">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label for="date_to">To Date</label>
            <input type="date" id="date_to" name="date_to" class="form-control" value="<?php echo $filters['date_to']; ?>">
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Generate Report</button>
            <a href="reports.php" class="btn btn-secondary">Clear Filters</a>
        </div>
    </form>
</div>

<!-- Summary Statistics -->
<div class="dashboard-grid" style="margin-bottom: 30px;">
    <div class="dashboard-card">
        <div class="card-title">Total Projects</div>
        <div class="stats-number"><?php echo count($projects); ?></div>
    </div>
    <?php
    $statusCounts = [];
    foreach ($projects as $project) {
        $statusName = $project['status_name'];
        if (!isset($statusCounts[$statusName])) {
            $statusCounts[$statusName] = 0;
        }
        $statusCounts[$statusName]++;
    }
    foreach ($statusCounts as $statusName => $count):
    ?>
    <div class="dashboard-card">
        <div class="card-title"><?php echo htmlspecialchars($statusName); ?></div>
        <div class="stats-number"><?php echo $count; ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Detailed Report Table -->
<div class="table-container">
    <h3>Project Details Report</h3>
    <table id="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Project Title</th>
                <th>Client</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>Completion Date</th>
                <th>Fabric</th>
                <th>Created</th>
                <th>Created By</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?php echo $project['id']; ?></td>
                    <td><?php echo htmlspecialchars($project['title']); ?></td>
                    <td><?php echo htmlspecialchars($project['client_name'] ?? 'No Client'); ?></td>
                    <td>
                        <span class="status-badge" style="background: <?php echo $project['status_color']; ?>; color: white;">
                            <?php echo htmlspecialchars($project['status_name']); ?>
                        </span>
                    </td>
                    <td><?php echo $project['start_date'] ? date('Y-m-d', strtotime($project['start_date'])) : '-'; ?></td>
                    <td><?php echo $project['completion_date'] ? date('Y-m-d', strtotime($project['completion_date'])) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($project['fabric'] ?: '-'); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($project['created_date'])); ?></td>
                    <td><?php echo htmlspecialchars($project['created_by_name']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (count($projects) === 0): ?>
        <p style="text-align: center; padding: 40px; color: #666;">
            No projects found matching your filter criteria.
        </p>
    <?php endif; ?>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('report-table');
    const rows = Array.from(table.querySelectorAll('tr'));

    const csvContent = rows.map(row => {
        const cols = Array.from(row.querySelectorAll('th, td'));
        return cols.map(col => {
            // Remove HTML and clean up text
            let text = col.textContent.trim();
            // Escape quotes and wrap in quotes if contains comma
            if (text.includes(',') || text.includes('"')) {
                text = '"' + text.replace(/"/g, '""') + '"';
            }
            return text;
        }).join(',');
    }).join('\n');

    // Create download link
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', 'project_report_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    WorkTrack.showNotification('Report exported to CSV!', 'success');
}
</script>

<style>
@media print {
    .navbar, .footer, .page-actions, .form-container {
        display: none !important;
    }

    .page-header {
        border-bottom: 2px solid #000;
        margin-bottom: 20px;
    }

    .dashboard-grid {
        page-break-inside: avoid;
    }

    .table-container {
        page-break-inside: auto;
    }

    tr {
        page-break-inside: avoid;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>