<?php
$pageTitle = 'Suppliers';
require_once 'includes/header.php';
require_once 'includes/models/Supplier.php';

$supplierModel = new Supplier();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $data = [
            'name' => $_POST['name'],
            'contact_name' => $_POST['contactName'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? '',
            'address' => $_POST['address'] ?? '',
            'notes' => $_POST['notes'] ?? '',
            'is_active' => isset($_POST['isActive']) ? 1 : 0
        ];

        if ($supplierModel->create($data)) {
            $message = 'Supplier created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to create supplier.';
            $messageType = 'danger';
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $data = [
            'name' => $_POST['name'],
            'contact_name' => $_POST['contactName'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? '',
            'address' => $_POST['address'] ?? '',
            'notes' => $_POST['notes'] ?? '',
            'is_active' => isset($_POST['isActive']) ? 1 : 0
        ];

        if ($supplierModel->update($id, $data)) {
            $message = 'Supplier updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update supplier.';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $result = $supplierModel->delete($id);

        if ($result['success']) {
            $message = 'Supplier deleted successfully!';
            $messageType = 'success';
        } else {
            $message = $result['message'] ?? 'Failed to delete supplier.';
            $messageType = 'warning';
        }
    }
}

// Get search query
$searchQuery = $_GET['search'] ?? '';

// Get all suppliers or search results
if ($searchQuery) {
    $suppliers = $supplierModel->search($searchQuery);
} else {
    $suppliers = $supplierModel->getAll();
}

// Get specific supplier for editing
$editSupplier = null;
if (isset($_GET['edit'])) {
    $editSupplier = $supplierModel->getById($_GET['edit']);
}

$showForm = isset($_GET['action']) && $_GET['action'] === 'new' || $editSupplier;
?>

<div class="page-header">
    <h1 class="page-title">Suppliers</h1>
    <div class="page-actions">
        <form method="GET" style="display: inline-block; margin-right: 10px;">
            <input type="text" name="search" placeholder="Search suppliers..." value="<?php echo htmlspecialchars($searchQuery); ?>" class="form-control" style="width: 250px; display: inline-block;">
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>
        <a href="?action=new" class="btn btn-primary">+ New Supplier</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($showForm): ?>
<div class="form-container">
    <h2><?php echo $editSupplier ? 'Edit Supplier' : 'New Supplier'; ?></h2>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $editSupplier ? 'update' : 'create'; ?>">
        <?php if ($editSupplier): ?>
            <input type="hidden" name="id" value="<?php echo $editSupplier['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="name">Supplier Name *</label>
            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($editSupplier['name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="contactName">Contact Name</label>
            <input type="text" id="contactName" name="contactName" class="form-control" value="<?php echo htmlspecialchars($editSupplier['contact_name'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($editSupplier['phone'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editSupplier['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" class="form-control"><?php echo htmlspecialchars($editSupplier['address'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control"><?php echo htmlspecialchars($editSupplier['notes'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="isActive" <?php echo ($editSupplier['is_active'] ?? 1) ? 'checked' : ''; ?>>
                Active
            </label>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Save Supplier</button>
            <a href="suppliers.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Materials</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                        <?php if ($supplier['notes']): ?>
                            <br><small style="color: #666;"><?php echo htmlspecialchars(substr($supplier['notes'], 0, 50)); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($supplier['contact_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($supplier['email'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($supplier['phone'] ?? '-'); ?></td>
                    <td>
                        <?php if ($supplier['material_count'] > 0): ?>
                            <a href="materials.php?supplier=<?php echo $supplier['id']; ?>" style="color: #667eea;">
                                <?php echo $supplier['material_count']; ?> material(s)
                            </a>
                        <?php else: ?>
                            <span style="color: #999;">0 materials</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($supplier['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?edit=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $supplier['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (count($suppliers) === 0): ?>
        <p style="text-align: center; padding: 40px; color: #666;">
            <?php echo $searchQuery ? 'No suppliers found matching your search.' : 'No suppliers yet. Create your first supplier!'; ?>
        </p>
    <?php endif; ?>
</div>
<?php endif; ?>

<style>
.btn-sm {
    padding: 5px 10px;
    font-size: 13px;
}
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}
.badge-success {
    background-color: #28a745;
    color: white;
}
.badge-secondary {
    background-color: #6c757d;
    color: white;
}
</style>

<?php require_once 'includes/footer.php'; ?>
