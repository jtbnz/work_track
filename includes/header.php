<?php
require_once __DIR__ . '/auth.php';
Auth::requireAuth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? ''; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/public/css/style.css">
    <script src="<?php echo BASE_PATH; ?>/public/js/main.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo BASE_PATH; ?>/index.php" class="nav-brand"><?php echo SITE_NAME; ?></a>
            <ul class="nav-menu">
                <li><a href="<?php echo BASE_PATH; ?>/index.php">Dashboard</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/projects.php">Projects</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/clients.php">Clients</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/calendar.php">Calendar</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/kanban.php">Kanban</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/gantt.php">Gantt</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/reports.php">Reports</a></li>
                <li class="nav-dropdown">
                    <a href="#">⚙️ Admin</a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo BASE_PATH; ?>/status.php">Statuses</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/templates.php">Templates</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/users.php">Users</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/calendar-sync.php">📅 Calendar Sync</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/backup.php">Backup</a></li>
                    </ul>
                </li>
            </ul>
            <div class="nav-user">
                <span>Welcome, <?php echo htmlspecialchars(Auth::getCurrentUsername()); ?></span>
                <a href="<?php echo BASE_PATH; ?>/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>
    <div class="main-container">