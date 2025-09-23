<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/models/Project.php';

$projectModel = new Project();
$db = Database::getInstance();

// Get all active projects
$projects = $db->fetchAll("
    SELECT p.*, c.name as client_name, ps.name as status_name, ps.color as status_color
    FROM projects p
    LEFT JOIN clients c ON p.client_id = c.id
    LEFT JOIN project_statuses ps ON p.status_id = ps.id
    WHERE ps.is_active = 1
    ORDER BY p.start_date ASC
");

// iCal header
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="worktrack.ics"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Get server info for unique IDs
$hostname = $_SERVER['HTTP_HOST'];

// Start iCal content
echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//WorkTrack//Project Calendar//EN\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "METHOD:PUBLISH\r\n";
echo "X-WR-CALNAME:WorkTrack Projects\r\n";
echo "X-WR-CALDESC:Project deadlines and schedules from WorkTrack\r\n";
echo "X-WR-TIMEZONE:" . date_default_timezone_get() . "\r\n";
echo "REFRESH-INTERVAL;VALUE=DURATION:PT1H\r\n"; // Refresh every hour

// Add timezone information
$tz = date_default_timezone_get();
echo "BEGIN:VTIMEZONE\r\n";
echo "TZID:$tz\r\n";
echo "BEGIN:STANDARD\r\n";
echo "DTSTART:19700101T000000\r\n";
echo "TZOFFSETFROM:" . date('O') . "\r\n";
echo "TZOFFSETTO:" . date('O') . "\r\n";
echo "END:STANDARD\r\n";
echo "END:VTIMEZONE\r\n";

// Add projects as events
foreach ($projects as $project) {
    if (!$project['start_date']) continue;

    // Generate unique ID
    $uid = 'worktrack-project-' . $project['id'] . '-' . md5($project['updated_at']) . '@' . $hostname;

    // Format dates
    $dtstart = date('Ymd', strtotime($project['start_date']));
    $dtend = $project['completion_date']
        ? date('Ymd', strtotime($project['completion_date'] . ' +1 day'))
        : date('Ymd', strtotime($project['start_date'] . ' +1 day'));

    // Format last modified
    $dtstamp = date('Ymd\THis\Z', strtotime($project['updated_at']));
    $created = date('Ymd\THis\Z', strtotime($project['created_at']));

    // Build summary (title)
    $summary = $project['title'];
    if ($project['client_name']) {
        $summary .= ' - ' . $project['client_name'];
    }

    // Build description
    $description = '';
    if ($project['details']) {
        $description .= strip_tags($project['details']) . '\n\n';
    }
    $description .= 'Status: ' . $project['status_name'] . '\n';
    if ($project['client_name']) {
        $description .= 'Client: ' . $project['client_name'] . '\n';
    }
    if ($project['fabric']) {
        $description .= 'Fabric: ' . $project['fabric'] . '\n';
    }

    // Add dates to description
    $description .= '\nStart: ' . date('M j, Y', strtotime($project['start_date']));
    if ($project['completion_date']) {
        $description .= '\nDue: ' . date('M j, Y', strtotime($project['completion_date']));
    }

    // Escape special characters for iCal
    $summary = str_replace([',', ';', "\n", "\r"], ['\,', '\;', '\\n', ''], $summary);
    $description = str_replace([',', ';', "\n", "\r"], ['\,', '\;', '\\n', ''], $description);

    // Determine status for the event
    $eventStatus = 'CONFIRMED';
    if (in_array(strtolower($project['status_name']), ['cancelled', 'on hold'])) {
        $eventStatus = 'CANCELLED';
    } elseif (strtolower($project['status_name']) == 'completed') {
        $eventStatus = 'COMPLETED';
    }

    echo "BEGIN:VEVENT\r\n";
    echo "UID:$uid\r\n";
    echo "DTSTAMP:$dtstamp\r\n";
    echo "CREATED:$created\r\n";
    echo "LAST-MODIFIED:$dtstamp\r\n";
    echo "DTSTART;VALUE=DATE:$dtstart\r\n";
    echo "DTEND;VALUE=DATE:$dtend\r\n";
    echo "SUMMARY:$summary\r\n";

    if ($description) {
        // Wrap long lines at 75 characters with proper continuation
        $lines = explode('\\n', $description);
        foreach ($lines as $line) {
            if (strlen($line) <= 73) {
                if ($lines[0] === $line) {
                    echo "DESCRIPTION:$line";
                } else {
                    echo "\\n$line";
                }
            } else {
                $wrapped = wordwrap($line, 73, "\r\n ", false);
                if ($lines[0] === $line) {
                    echo "DESCRIPTION:$wrapped";
                } else {
                    echo "\\n$wrapped";
                }
            }
        }
        echo "\r\n";
    }

    echo "STATUS:$eventStatus\r\n";
    echo "TRANSP:TRANSPARENT\r\n";

    // Add categories based on status
    echo "CATEGORIES:" . strtoupper(str_replace(' ', '_', $project['status_name'])) . "\r\n";

    // Add priority based on status
    $priority = 5; // Normal
    if (in_array(strtolower($project['status_name']), ['urgent', 'high priority'])) {
        $priority = 1;
    } elseif (strtolower($project['status_name']) == 'in progress') {
        $priority = 3;
    }
    echo "PRIORITY:$priority\r\n";

    // Add color based on status (X-properties for compatible clients)
    if ($project['status_color']) {
        $color = strtoupper(ltrim($project['status_color'], '#'));
        echo "X-APPLE-CALENDAR-COLOR:#$color\r\n";
        echo "X-OUTLOOK-COLOR:#$color\r\n";
        echo "X-GOOGLE-CALENDAR-COLOR:#$color\r\n";
    }

    // Add URL back to the project (if accessible)
    $projectUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
                . $hostname . BASE_PATH . '/projects.php?id=' . $project['id'];
    echo "URL:$projectUrl\r\n";

    echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";
?>