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
    <link rel="stylesheet" href="/public/css/style.css">
    <script src="/public/js/main.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/index.php" class="nav-brand"><?php echo SITE_NAME; ?></a>
            <ul class="nav-menu">
                <li><a href="/index.php">Dashboard</a></li>
                <li><a href="/projects.php">Projects</a></li>
                <li><a href="/clients.php">Clients</a></li>
                <li><a href="/calendar.php">Calendar</a></li>
                <li><a href="/kanban.php">Kanban</a></li>
                <li><a href="/gantt.php">Gantt</a></li>
                <li><a href="/reports.php">Reports</a></li>
            </ul>
            <div class="nav-user">
                <span>Welcome, <?php echo htmlspecialchars(Auth::getCurrentUsername()); ?></span>
                <a href="/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>
    <div class="main-container">