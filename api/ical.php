<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/models/Project.php';

// Get token from query parameter for authentication
$token = $_GET['token'] ?? '';
$userId = $_GET['user'] ?? '';

if (!$token || !$userId) {
    http_response_code(401);
    die('Invalid calendar URL');
}

// Validate token (simple validation - in production use proper token management)
$expectedToken = md5($userId . 'worktrack_ical_salt');
if ($token !== $expectedToken) {
    http_response_code(401);
    die('Invalid token');
}

$projectModel = new Project();
$db = Database::getInstance();

// Get user info
$user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
if (!$user) {
    http_response_code(404);
    die('User not found');
}

// Get all projects
$projects = $projectModel->getAll();

// iCal header
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="worktrack_projects.ics"');

// Start iCal content
echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//WorkTrack//Project Calendar//EN\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "METHOD:PUBLISH\r\n";
echo "X-WR-CALNAME:WorkTrack Projects\r\n";
echo "X-WR-CALDESC:Project deadlines and schedules from WorkTrack\r\n";

// Add projects as events
foreach ($projects as $project) {
    if (!$project['start_date']) continue;

    $uid = 'project-' . $project['id'] . '@worktrack.local';
    $dtstart = date('Ymd', strtotime($project['start_date']));
    $dtend = $project['completion_date']
        ? date('Ymd', strtotime($project['completion_date'] . ' +1 day'))
        : date('Ymd', strtotime($project['start_date'] . ' +1 day'));

    $summary = $project['title'];
    if ($project['client_name']) {
        $summary .= ' (' . $project['client_name'] . ')';
    }

    $description = '';
    if ($project['details']) {
        $description .= strip_tags($project['details']) . '\n\n';
    }
    $description .= 'Status: ' . $project['status_name'] . '\n';
    if ($project['fabric']) {
        $description .= 'Fabric: ' . $project['fabric'] . '\n';
    }

    // Escape special characters for iCal
    $summary = str_replace([',', ';', '\n'], ['\,', '\;', '\\n'], $summary);
    $description = str_replace([',', ';', '\n'], ['\,', '\;', '\\n'], $description);

    echo "BEGIN:VEVENT\r\n";
    echo "UID:$uid\r\n";
    echo "DTSTART;VALUE=DATE:$dtstart\r\n";
    echo "DTEND;VALUE=DATE:$dtend\r\n";
    echo "SUMMARY:$summary\r\n";
    if ($description) {
        // Wrap long lines at 75 characters
        $wrapped = wordwrap($description, 73, "\r\n ", true);
        echo "DESCRIPTION:$wrapped\r\n";
    }
    echo "STATUS:CONFIRMED\r\n";
    echo "TRANSP:TRANSPARENT\r\n";

    // Add color based on status (X-properties for compatible clients)
    if ($project['status_color']) {
        $color = ltrim($project['status_color'], '#');
        echo "X-APPLE-CALENDAR-COLOR:#$color\r\n";
        echo "X-OUTLOOK-COLOR:#$color\r\n";
    }

    echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";
?>