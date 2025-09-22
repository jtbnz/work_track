<?php
$pageTitle = 'User Management';
require_once 'includes/header.php';
require_once 'includes/db.php';

$db = Database::getInstance();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $email = $_POST['email'];

        $result = Auth::createUser($username, $password, $email);

        if ($result['success']) {
            $message = 'User created successfully!';
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        $userId = $_POST['user_id'];

        // Prevent deleting the last admin user
        $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
        if ($userCount <= 1) {
            $message = 'Cannot delete the last user.';
            $messageType = 'danger';
        } elseif ($userId == Auth::getCurrentUserId()) {
            $message = 'Cannot delete your own account while logged in.';
            $messageType = 'danger';
        } else {
            $result = $db->delete('users', 'id = :id', ['id' => $userId]);
            if ($result) {
                Auth::logAudit('users', $userId, 'DELETE', ['id' => $userId]);
                $message = 'User deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete user.';
                $messageType = 'danger';
            }
        }
    } elseif ($action === 'reset_password') {
        $userId = $_POST['user_id'];
        $newPassword = $_POST['new_password'];

        $result = $db->update('users',
            ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)],
            'id = :id',
            ['id' => $userId]
        );

        if ($result) {
            Auth::logAudit('users', $userId, 'UPDATE', ['action' => 'password_reset']);
            $message = 'Password reset successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to reset password.';
            $messageType = 'danger';
        }
    }
}

// Get all users
$users = $db->fetchAll("
    SELECT u.*,
           COUNT(DISTINCT p.id) as project_count,
           COUNT(DISTINCT c.id) as client_count
    FROM users u
    LEFT JOIN projects p ON u.id = p.created_by
    LEFT JOIN clients c ON u.id = c.created_by
    GROUP BY u.id
    ORDER BY u.created_at DESC
");

$showForm = isset($_GET['action']) && $_GET['action'] === 'new';
?>

<div class="page-header">
    <h1 class="page-title">User Management</h1>
    <div class="page-actions">
        <a href="?action=new" class="btn btn-primary">➕ New User</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($showForm): ?>
<div class="form-container">
    <h2>Create New User</h2>
    <form method="POST">
        <input type="hidden" name="action" value="create">

        <div class="form-group">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" class="form-control" required minlength="6">
            <small style="color: #666;">Minimum 6 characters</small>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Create User</button>
            <a href="users.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Created</th>
                <th>Last Login</th>
                <th>Projects Created</th>
                <th>Clients Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                        <?php if ($user['id'] == Auth::getCurrentUserId()): ?>
                            <span class="status-badge" style="background: #28a745; color: white; font-size: 11px; margin-left: 8px;">YOU</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <?php if ($user['last_login']): ?>
                            <?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>
                        <?php else: ?>
                            <span style="color: #999;">Never</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $user['project_count']; ?></td>
                    <td><?php echo $user['client_count']; ?></td>
                    <td>
                        <button onclick="showResetPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                class="btn btn-sm btn-warning">Reset Password</button>

                        <?php if ($user['id'] != Auth::getCurrentUserId() && count($users) > 1): ?>
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Reset Password Modal -->
<div id="reset-password-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Reset Password</h3>
            <span class="modal-close">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="reset-user-id">

            <div class="form-group">
                <label>User: <strong id="reset-username"></strong></label>
            </div>

            <div class="form-group">
                <label for="new_password">New Password *</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                <small style="color: #666;">Minimum 6 characters</small>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="hideResetPasswordModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function showResetPasswordModal(userId, username) {
    document.getElementById('reset-user-id').value = userId;
    document.getElementById('reset-username').textContent = username;
    document.getElementById('reset-password-modal').style.display = 'block';
}

function hideResetPasswordModal() {
    document.getElementById('reset-password-modal').style.display = 'none';
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('reset-password-modal');
    if (event.target === modal) {
        hideResetPasswordModal();
    }
});

// Close modal with close button
document.querySelector('.modal-close').addEventListener('click', hideResetPasswordModal);
</script>

<style>
.btn-sm {
    padding: 5px 10px;
    font-size: 13px;
}
</style>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>