<?php
$pageTitle = 'Projects';
require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Projects</h1>
    <div class="page-actions">
        <a href="?action=new" class="btn btn-primary">➕ New Project</a>
    </div>
</div>

<div class="table-container">
    <div style="text-align: center; padding: 60px 20px;">
        <h2 style="color: #667eea; margin-bottom: 20px;">🚧 Projects Module Coming Soon</h2>
        <p style="color: #666; font-size: 18px; margin-bottom: 30px;">
            The project management system is currently under development.<br>
            This will include project creation, editing, status management, and file attachments.
        </p>

        <div style="background: #f8f9fa; border-radius: 10px; padding: 30px; margin: 30px 0; text-align: left; max-width: 600px; margin-left: auto; margin-right: auto;">
            <h3 style="color: #495057; margin-bottom: 15px;">Planned Features:</h3>
            <ul style="color: #666; line-height: 1.8;">
                <li>✅ Create and edit projects with client assignment</li>
                <li>✅ Customizable project statuses with colors</li>
                <li>✅ Project templates for recurring work</li>
                <li>✅ File attachments and document management</li>
                <li>✅ Project timeline tracking (start/end dates)</li>
                <li>✅ Full audit trail for all changes</li>
                <li>✅ Search and filter projects</li>
                <li>✅ Export project data</li>
            </ul>
        </div>

        <p style="color: #666;">
            For now, you can see the sample project in the database by viewing the
            <a href="index.php" style="color: #667eea;">Dashboard</a> or
            <a href="clients.php" style="color: #667eea;">Clients</a> section.
        </p>

        <div style="margin-top: 40px;">
            <a href="index.php" class="btn btn-primary">← Back to Dashboard</a>
            <a href="clients.php" class="btn btn-secondary">Manage Clients</a>
        </div>
    </div>
</div>

<script>
// Add some interactivity to show this is working
document.addEventListener('DOMContentLoaded', function() {
    const features = document.querySelectorAll('li');
    features.forEach((feature, index) => {
        setTimeout(() => {
            feature.style.opacity = '0';
            feature.style.transform = 'translateX(-20px)';
            feature.style.transition = 'opacity 0.3s, transform 0.3s';
            setTimeout(() => {
                feature.style.opacity = '1';
                feature.style.transform = 'translateX(0)';
            }, 10);
        }, index * 100);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>