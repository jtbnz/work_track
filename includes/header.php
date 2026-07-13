<?php
require_once __DIR__ . '/auth.php';
Auth::requireAuth();
require_once __DIR__ . '/models/Settings.php';
$currentTheme = (new Settings())->get('user_theme_' . Auth::getCurrentUserId(), 'light');
if (!in_array($currentTheme, ['light', 'dark'], true)) {
    $currentTheme = 'light';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $currentTheme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? ''; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_PATH; ?>/public/images/favicon.svg">
    <link rel="shortcut icon" href="<?php echo BASE_PATH; ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/public/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/public/css/theme.css">
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
                    <a href="#">Quoting</a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo BASE_PATH; ?>/quotes.php">Quotes</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/invoices.php">Invoices</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/materials.php">Materials</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/suppliers.php">Suppliers</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/quotingSettings.php">Settings</a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <a href="#">Admin</a>
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
                <button type="button" class="theme-toggle" id="themeToggle" title="Toggle light/dark mode"><?php echo $currentTheme === 'dark' ? '&#9728;' : '&#127769;'; ?></button>
                <span>Welcome, <?php echo htmlspecialchars(Auth::getCurrentUsername()); ?></span>
                <a href="<?php echo BASE_PATH; ?>/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>
    <script>
    (function() {
        var toggle = document.getElementById('themeToggle');
        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            if (toggle) toggle.innerHTML = theme === 'dark' ? '&#9728;' : '&#127769;';
            try { localStorage.setItem('wt-theme', theme); } catch (e) {}
        }
        if (toggle) {
            toggle.addEventListener('click', function() {
                var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                applyTheme(next);
                fetch('<?php echo BASE_PATH; ?>/api/userTheme.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({theme: next})
                });
            });
        }
        // Keep multiple windows (e.g. the floating stock window) in sync
        window.addEventListener('storage', function(e) {
            if (e.key === 'wt-theme' && (e.newValue === 'light' || e.newValue === 'dark')) {
                applyTheme(e.newValue);
            }
        });
    })();
    </script>
    <div class="main-container">