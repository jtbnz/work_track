<?php
$pageTitle = 'Calendar Sync Instructions';
require_once 'includes/header.php';
require_once 'includes/auth.php';

// Get the current server URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$hostname = $_SERVER['HTTP_HOST'];
$basePath = BASE_PATH;
$fullUrl = $protocol . $hostname . $basePath;
$icalUrl = $fullUrl . '/api/calendar_feed.php';

// Get current user info for personalized URL
$userId = Auth::getCurrentUserId();
$username = Auth::getCurrentUsername();
?>

<div class="page-header">
    <h1 class="page-title">📅 Calendar Sync Instructions</h1>
</div>

<div class="instructions-container">
    <!-- Server Information Card -->
    <div class="info-card">
        <h2>📍 Your Calendar URL</h2>
        <div class="url-display">
            <input type="text" id="calendarUrl" value="<?php echo $icalUrl; ?>" readonly class="url-input">
            <button onclick="copyToClipboard()" class="btn btn-primary">📋 Copy URL</button>
        </div>
        <p class="info-text">
            <strong>Server:</strong> <?php echo $hostname; ?><br>
            <strong>Path:</strong> <?php echo $basePath ?: '(root)'; ?><br>
            <strong>Protocol:</strong> <?php echo $protocol === 'https://' ? 'HTTPS (Secure)' : 'HTTP'; ?>
        </p>
    </div>

    <!-- iOS/iPhone Instructions -->
    <div class="instruction-card">
        <h2>📱 iPhone/iPad Instructions</h2>
        <div class="device-instructions">
            <h3>Method 1: Using Settings App (Recommended)</h3>
            <ol class="instruction-list">
                <li>
                    <strong>Open Settings</strong>
                    <p>Tap the Settings app on your iPhone or iPad</p>
                </li>
                <li>
                    <strong>Navigate to Calendar</strong>
                    <p>Scroll down and tap "Calendar" (you might need to scroll quite a bit)</p>
                </li>
                <li>
                    <strong>Add Account</strong>
                    <p>Tap "Accounts" → "Add Account"</p>
                </li>
                <li>
                    <strong>Select Other</strong>
                    <p>Scroll to the bottom and tap "Other"</p>
                </li>
                <li>
                    <strong>Add Subscribed Calendar</strong>
                    <p>Tap "Add Subscribed Calendar"</p>
                </li>
                <li>
                    <strong>Enter the URL</strong>
                    <p>Paste this URL: <code><?php echo $icalUrl; ?></code></p>
                    <p class="tip">💡 Tip: Copy the URL from the box above first!</p>
                </li>
                <li>
                    <strong>Configure Settings</strong>
                    <ul>
                        <li><strong>Description:</strong> WorkTrack Calendar</li>
                        <li><strong>Location:</strong> Leave as default</li>
                        <li><strong>Remove Alarms:</strong> Toggle OFF (to keep project reminders)</li>
                        <li><strong>Update Frequency:</strong> Every Hour (or as preferred)</li>
                    </ul>
                </li>
                <li>
                    <strong>Save</strong>
                    <p>Tap "Save" in the top right corner</p>
                </li>
            </ol>

            <h3>Method 2: Direct Link (Quick Setup)</h3>
            <ol class="instruction-list">
                <li>
                    <strong>Copy the Calendar URL</strong>
                    <p>Click the "Copy URL" button above</p>
                </li>
                <li>
                    <strong>Open Safari</strong>
                    <p>Open Safari browser on your iPhone/iPad</p>
                </li>
                <li>
                    <strong>Paste and Modify URL</strong>
                    <p>In the address bar, type: <code>webcal://</code> then paste the rest of the URL after http://</p>
                    <p>Example: <code>webcal://<?php echo $hostname . $basePath; ?>/api/calendar_feed.php</code></p>
                </li>
                <li>
                    <strong>Subscribe</strong>
                    <p>Tap "Subscribe" when prompted</p>
                </li>
                <li>
                    <strong>Confirm Settings</strong>
                    <p>Review the settings and tap "Done"</p>
                </li>
            </ol>
        </div>
    </div>

    <!-- macOS Instructions -->
    <div class="instruction-card">
        <h2>💻 Mac Instructions</h2>
        <div class="device-instructions">
            <ol class="instruction-list">
                <li>
                    <strong>Open Calendar App</strong>
                    <p>Open the Calendar application on your Mac</p>
                </li>
                <li>
                    <strong>File Menu</strong>
                    <p>Click "File" → "New Calendar Subscription..."</p>
                </li>
                <li>
                    <strong>Enter URL</strong>
                    <p>Paste: <code><?php echo $icalUrl; ?></code></p>
                </li>
                <li>
                    <strong>Configure Settings</strong>
                    <ul>
                        <li><strong>Name:</strong> WorkTrack</li>
                        <li><strong>Color:</strong> Choose your preference</li>
                        <li><strong>Location:</strong> iCloud or On My Mac</li>
                        <li><strong>Auto-refresh:</strong> Every hour</li>
                        <li><strong>Alerts:</strong> Keep enabled for reminders</li>
                    </ul>
                </li>
                <li>
                    <strong>Click OK</strong>
                    <p>Your calendar will now sync automatically</p>
                </li>
            </ol>
        </div>
    </div>

    <!-- Other Apps -->
    <div class="instruction-card">
        <h2>📱 Other Calendar Apps</h2>
        <div class="device-instructions">
            <h3>Google Calendar</h3>
            <ol class="instruction-list">
                <li>Open Google Calendar on desktop (not available on mobile)</li>
                <li>Click the "+" next to "Other calendars"</li>
                <li>Select "From URL"</li>
                <li>Paste: <code><?php echo $icalUrl; ?></code></li>
                <li>Click "Add Calendar"</li>
            </ol>

            <h3>Outlook</h3>
            <ol class="instruction-list">
                <li>Open Outlook (desktop or web)</li>
                <li>Go to Calendar view</li>
                <li>Click "Add Calendar" → "Subscribe from web"</li>
                <li>Paste the URL and give it a name</li>
                <li>Choose color and charm (icon)</li>
                <li>Click "Import"</li>
            </ol>
        </div>
    </div>

    <!-- Troubleshooting -->
    <div class="instruction-card">
        <h2>🔧 Troubleshooting</h2>
        <div class="troubleshoot-section">
            <h3>Common Issues</h3>
            <div class="issue">
                <strong>❌ Calendar won't sync</strong>
                <ul>
                    <li>Ensure you're connected to the internet</li>
                    <li>Check if the server <code><?php echo $hostname; ?></code> is accessible from your device</li>
                    <li>Try removing and re-adding the calendar</li>
                    <li>Make sure the URL is copied correctly</li>
                </ul>
            </div>
            <div class="issue">
                <strong>❌ "Cannot connect to server" error</strong>
                <ul>
                    <li>Your device might need to be on the same network as the server</li>
                    <li>Check if your server requires VPN access</li>
                    <li>Try using the IP address instead of hostname</li>
                </ul>
            </div>
            <div class="issue">
                <strong>❌ Events not updating</strong>
                <ul>
                    <li>Pull down to refresh in the Calendar app</li>
                    <li>Check the refresh frequency in calendar settings</li>
                    <li>Go to Settings → Calendar → Accounts → Fetch New Data</li>
                </ul>
            </div>
        </div>

        <h3>Important Notes</h3>
        <div class="notes-section">
            <p>📌 <strong>Read-Only:</strong> This is a subscribed calendar. You cannot edit events from your phone - changes must be made in WorkTrack.</p>
            <p>🔄 <strong>Sync Frequency:</strong> By default, iOS checks for updates every hour. You can change this in Settings → Calendar → Accounts → Fetch New Data.</p>
            <p>🎨 <strong>Calendar Color:</strong> After adding, you can change the calendar color in the Calendar app by tapping "Calendars" at the bottom.</p>
            <p>🔐 <strong>Security:</strong> <?php echo $protocol === 'https://' ? 'Your connection is secure (HTTPS)' : '⚠️ Your connection is not encrypted. Consider using HTTPS for better security.'; ?></p>
        </div>
    </div>
</div>

<script>
function copyToClipboard() {
    const urlInput = document.getElementById('calendarUrl');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // For mobile devices

    try {
        document.execCommand('copy');

        // Visual feedback
        const button = event.target;
        const originalText = button.innerText;
        button.innerText = '✓ Copied!';
        button.style.background = '#28a745';

        setTimeout(() => {
            button.innerText = originalText;
            button.style.background = '';
        }, 2000);
    } catch (err) {
        alert('Failed to copy. Please select and copy manually.');
    }
}
</script>

<style>
.instructions-container {
    max-width: 900px;
    margin: 0 auto;
}

.info-card, .instruction-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.url-display {
    display: flex;
    gap: 10px;
    margin: 20px 0;
}

.url-input {
    flex: 1;
    padding: 12px;
    font-family: monospace;
    font-size: 14px;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 5px;
    background: rgba(255,255,255,0.9);
    color: #333;
}

.info-text {
    margin-top: 15px;
    padding: 15px;
    background: rgba(255,255,255,0.1);
    border-radius: 5px;
    line-height: 1.8;
}

.instruction-card h2 {
    color: #667eea;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}

.instruction-card h3 {
    color: #333;
    margin-top: 25px;
    margin-bottom: 15px;
}

.instruction-list {
    counter-reset: step-counter;
    list-style: none;
    padding-left: 0;
}

.instruction-list li {
    counter-increment: step-counter;
    margin-bottom: 20px;
    position: relative;
    padding-left: 50px;
}

.instruction-list li::before {
    content: counter(step-counter);
    position: absolute;
    left: 0;
    top: 0;
    background: #667eea;
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.instruction-list li strong {
    display: block;
    margin-bottom: 5px;
    color: #333;
    font-size: 16px;
}

.instruction-list li p {
    margin: 5px 0;
    color: #666;
    line-height: 1.5;
}

.instruction-list li code {
    background: #f4f4f4;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 13px;
    color: #d73502;
    word-break: break-all;
}

.instruction-list ul {
    margin-top: 10px;
    margin-left: 20px;
}

.instruction-list ul li {
    counter-increment: none;
    padding-left: 0;
    margin-bottom: 8px;
}

.instruction-list ul li::before {
    display: none;
}

.tip {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 10px;
    margin: 10px 0;
    border-radius: 3px;
}

.troubleshoot-section {
    margin-top: 20px;
}

.issue {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.issue strong {
    display: block;
    margin-bottom: 10px;
    color: #dc3545;
}

.issue ul {
    margin: 10px 0 0 20px;
    color: #666;
}

.issue ul li {
    margin-bottom: 5px;
}

.notes-section {
    background: #e8f4fd;
    padding: 20px;
    border-radius: 5px;
    margin-top: 20px;
}

.notes-section p {
    margin-bottom: 12px;
    line-height: 1.6;
}

.device-instructions {
    margin-top: 20px;
}

@media (max-width: 768px) {
    .url-display {
        flex-direction: column;
    }

    .instruction-list li {
        padding-left: 40px;
    }

    .instruction-list li::before {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>