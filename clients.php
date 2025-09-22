<?php
$pageTitle = 'Clients';
require_once 'includes/header.php';
require_once 'includes/models/Client.php';

$clientModel = new Client();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $data = [
            'name' => $_POST['name'],
            'address' => $_POST['address'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? '',
            'remarks' => $_POST['remarks'] ?? ''
        ];

        if ($clientModel->create($data)) {
            $message = 'Client created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to create client.';
            $messageType = 'danger';
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $data = [
            'name' => $_POST['name'],
            'address' => $_POST['address'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? '',
            'remarks' => $_POST['remarks'] ?? ''
        ];

        if ($clientModel->update($id, $data)) {
            $message = 'Client updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update client.';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $result = $clientModel->delete($id);

        if ($result['success']) {
            $message = 'Client deleted successfully!';
            $messageType = 'success';
        } elseif ($result['confirm_required'] ?? false) {
            $message = $result['message'];
            $messageType = 'warning';
        } else {
            $message = 'Failed to delete client.';
            $messageType = 'danger';
        }
    }
}

// Get search query
$searchQuery = $_GET['search'] ?? '';

// Get all clients or search results
if ($searchQuery) {
    $clients = $clientModel->search($searchQuery);
} else {
    $clients = $clientModel->getAll();
}

// Get specific client for editing
$editClient = null;
if (isset($_GET['edit'])) {
    $editClient = $clientModel->getById($_GET['edit']);
}

$showForm = isset($_GET['action']) && $_GET['action'] === 'new' || $editClient;
?>

<div class="page-header">
    <h1 class="page-title">Clients</h1>
    <div class="page-actions">
        <form method="GET" style="display: inline-block; margin-right: 10px;">
            <input type="text" name="search" placeholder="Search clients..." value="<?php echo htmlspecialchars($searchQuery); ?>" class="form-control" style="width: 250px; display: inline-block;">
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>
        <a href="?action=new" class="btn btn-primary">➕ New Client</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($showForm): ?>
<div class="form-container">
    <h2><?php echo $editClient ? 'Edit Client' : 'New Client'; ?></h2>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $editClient ? 'update' : 'create'; ?>">
        <?php if ($editClient): ?>
            <input type="hidden" name="id" value="<?php echo $editClient['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($editClient['name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" class="form-control"><?php echo htmlspecialchars($editClient['address'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($editClient['phone'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editClient['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" name="remarks" class="form-control"><?php echo htmlspecialchars($editClient['remarks'] ?? ''); ?></textarea>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Save Client</button>
            <a href="clients.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Projects</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($client['name']); ?></strong>
                        <?php if ($client['remarks']): ?>
                            <br><small style="color: #666;"><?php echo htmlspecialchars(substr($client['remarks'], 0, 50)); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($client['email'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($client['phone'] ?? '-'); ?></td>
                    <td>
                        <?php if ($client['project_count'] > 0): ?>
                            <a href="projects.php?client=<?php echo $client['id']; ?>" style="color: #667eea;">
                                <?php echo $client['project_count']; ?> project(s)
                            </a>
                        <?php else: ?>
                            <span style="color: #999;">0 projects</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($client['created_at'])); ?></td>
                    <td>
                        <a href="?edit=<?php echo $client['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this client?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $client['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (count($clients) === 0): ?>
        <p style="text-align: center; padding: 40px; color: #666;">
            <?php echo $searchQuery ? 'No clients found matching your search.' : 'No clients yet. Create your first client!'; ?>
        </p>
    <?php endif; ?>
</div>
<?php endif; ?>

<style>
.btn-sm {
    padding: 5px 10px;
    font-size: 13px;
}
</style>

<?php require_once 'includes/footer.php'; ?>