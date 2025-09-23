<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';
require_once 'includes/db.php';

$db = Database::getInstance();
$userId = Auth::getCurrentUserId();

// Get statistics
$totalProjects = $db->fetchOne("SELECT COUNT(*) as count FROM projects")['count'];
$activeProjects = $db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE status_id IN (SELECT id FROM project_statuses WHERE name IN ('In Progress', 'Pending'))")['count'];
$completedProjects = $db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE status_id = (SELECT id FROM project_statuses WHERE name = 'Completed')")['count'];
$totalClients = $db->fetchOne("SELECT COUNT(*) as count FROM clients")['count'];

// Get recent activity
$recentActivity = $db->fetchAll("
    SELECT al.*, u.username
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.timestamp DESC
    LIMIT 10
");

// Get upcoming deadlines
$upcomingDeadlines = $db->fetchAll("
    SELECT p.*, c.name as client_name, ps.name as status_name, ps.color as status_color
    FROM projects p
    LEFT JOIN clients c ON p.client_id = c.id
    LEFT JOIN project_statuses ps ON p.status_id = ps.id
    WHERE p.completion_date >= date('now')
    AND ps.name NOT IN ('Completed', 'Cancelled')
    ORDER BY p.completion_date ASC
    LIMIT 5
");
?>

<h1 class="page-title">Dashboard</h1>

<!-- Statistics Cards -->
<div class="dashboard-grid">
    <div class="dashboard-card stats-card">
        <div class="stats-number"><?php echo $totalProjects; ?></div>
        <div class="card-title">Total Projects</div>
    </div>
    <div class="dashboard-card stats-card">
        <div class="stats-number"><?php echo $activeProjects; ?></div>
        <div class="card-title">Active Projects</div>
    </div>
    <div class="dashboard-card stats-card">
        <div class="stats-number"><?php echo $completedProjects; ?></div>
        <div class="card-title">Completed</div>
    </div>
    <div class="dashboard-card stats-card">
        <div class="stats-number"><?php echo $totalClients; ?></div>
        <div class="card-title">Total Clients</div>
    </div>
</div>

<!-- Navigation Cards -->
<h2 style="margin: 30px 0 20px 0;">Quick Access</h2>
<div class="dashboard-grid">
    <a href="<?php echo BASE_PATH; ?>/projects.php" class="dashboard-card">
        <div class="card-icon" style="background: #e3f2fd; color: #1976d2;">📋</div>
        <div class="card-title">Projects</div>
        <div class="card-description">Manage all your projects</div>
    </a>
    <a href="<?php echo BASE_PATH; ?>/clients.php" class="dashboard-card">
        <div class="card-icon" style="background: #f3e5f5; color: #7b1fa2;">👥</div>
        <div class="card-title">Clients</div>
        <div class="card-description">View and manage clients</div>
    </a>
    <a href="<?php echo BASE_PATH; ?>/calendar.php" class="dashboard-card">
        <div class="card-icon" style="background: #e8f5e9; color: #388e3c;">📅</div>
        <div class="card-title">Calendar</div>
        <div class="card-description">Monthly project view</div>
    </a>
    <a href="<?php echo BASE_PATH; ?>/kanban.php" class="dashboard-card">
        <div class="card-icon" style="background: #fff3e0; color: #f57c00;">📊</div>
        <div class="card-title">Kanban Board</div>
        <div class="card-description">Drag & drop project status</div>
    </a>
    <a href="<?php echo BASE_PATH; ?>/gantt.php" class="dashboard-card">
        <div class="card-icon" style="background: #fce4ec; color: #c2185b;">📈</div>
        <div class="card-title">Gantt Chart</div>
        <div class="card-description">Timeline visualization</div>
    </a>
    <a href="<?php echo BASE_PATH; ?>/reports.php" class="dashboard-card">
        <div class="card-icon" style="background: #e0f2f1; color: #00796b;">📊</div>
        <div class="card-title">Reports</div>
        <div class="card-description">Project analytics & export</div>
    </a>
</div>

<!-- Quick Actions -->
<div style="display: flex; gap: 15px; margin: 30px 0;">
    <a href="<?php echo BASE_PATH; ?>/projects.php?action=new" class="btn btn-primary">➕ New Project</a>
    <a href="<?php echo BASE_PATH; ?>/clients.php?action=new" class="btn btn-success">➕ New Client</a>
</div>

<!-- Upcoming Deadlines -->
<div class="table-container">
    <h2 style="margin-bottom: 20px;">Upcoming Deadlines</h2>
    <?php if (count($upcomingDeadlines) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Deadline</th>
                    <th>Days Remaining</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($upcomingDeadlines as $project):
                    $daysRemaining = (strtotime($project['completion_date']) - time()) / 86400;
                    $daysRemaining = ceil($daysRemaining);
                ?>
                    <tr>
                        <td><a href="<?php echo BASE_PATH; ?>/projects.php?id=<?php echo $project['id']; ?>" style="color: #667eea; text-decoration: none;"><?php echo htmlspecialchars($project['title']); ?></a></td>
                        <td><?php echo htmlspecialchars($project['client_name'] ?? 'No client'); ?></td>
                        <td><span class="status-badge" style="background: <?php echo $project['status_color']; ?>; color: white;"><?php echo htmlspecialchars($project['status_name']); ?></span></td>
                        <td><?php echo date('M j, Y', strtotime($project['completion_date'])); ?></td>
                        <td>
                            <?php if ($daysRemaining < 0): ?>
                                <span style="color: #dc3545;">Overdue</span>
                            <?php elseif ($daysRemaining == 0): ?>
                                <span style="color: #ffc107;">Today</span>
                            <?php elseif ($daysRemaining == 1): ?>
                                <span style="color: #ffc107;">Tomorrow</span>
                            <?php else: ?>
                                <?php echo $daysRemaining; ?> days
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: #666;">No upcoming deadlines</p>
    <?php endif; ?>
</div>

<!-- Recent Activity -->
<div class="table-container" style="margin-top: 30px;">
    <h2 style="margin-bottom: 20px;">Recent Activity</h2>
    <?php if (count($recentActivity) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentActivity as $activity): ?>
                    <tr>
                        <td><?php echo date('M j, g:i A', strtotime($activity['timestamp'])); ?></td>
                        <td><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></td>
                        <td>
                            <?php
                            $actionClass = '';
                            if ($activity['action'] == 'INSERT') $actionClass = 'btn-success';
                            elseif ($activity['action'] == 'UPDATE') $actionClass = 'btn-warning';
                            elseif ($activity['action'] == 'DELETE') $actionClass = 'btn-danger';
                            ?>
                            <span class="status-badge <?php echo $actionClass; ?>" style="color: white;">
                                <?php echo $activity['action']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($activity['table_name']); ?></td>
                        <td style="font-size: 13px; color: #666;">
                            <?php
                            $changes = json_decode($activity['changes_json'], true);
                            if ($changes && is_array($changes)) {
                                $summary = [];
                                foreach (array_slice($changes, 0, 2) as $key => $value) {
                                    if (!is_array($value)) {
                                        $summary[] = $key . ': ' . substr($value, 0, 30);
                                    }
                                }
                                echo htmlspecialchars(implode(', ', $summary));
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: #666;">No recent activity</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>