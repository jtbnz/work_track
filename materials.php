<?php
$pageTitle = 'Materials';
require_once 'includes/header.php';
require_once 'includes/models/Material.php';
require_once 'includes/models/Supplier.php';

$materialModel = new Material();
$supplierModel = new Supplier();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $data = [
            'supplier_id' => $_POST['supplierId'] ?: null,
            'manufacturers_code' => $_POST['manufacturersCode'] ?? '',
            'item_name' => $_POST['itemName'],
            'cost_excl' => (float)($_POST['costExcl'] ?? 0),
            'sell_price' => (float)($_POST['sellPrice'] ?? 0),
            'comments' => $_POST['comments'] ?? '',
            'stock_on_hand' => (float)($_POST['stockOnHand'] ?? 0),
            'reorder_quantity' => (float)($_POST['reorderQuantity'] ?? 0),
            'reorder_level' => (float)($_POST['reorderLevel'] ?? 5),
            'unit_of_measure' => $_POST['unitOfMeasure'] ?? 'each',
            'is_active' => isset($_POST['isActive']) ? 1 : 0
        ];

        if ($materialModel->create($data)) {
            $message = 'Material created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to create material.';
            $messageType = 'danger';
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $data = [
            'supplier_id' => $_POST['supplierId'] ?: null,
            'manufacturers_code' => $_POST['manufacturersCode'] ?? '',
            'item_name' => $_POST['itemName'],
            'cost_excl' => (float)($_POST['costExcl'] ?? 0),
            'sell_price' => (float)($_POST['sellPrice'] ?? 0),
            'comments' => $_POST['comments'] ?? '',
            'stock_on_hand' => (float)($_POST['stockOnHand'] ?? 0),
            'reorder_quantity' => (float)($_POST['reorderQuantity'] ?? 0),
            'reorder_level' => (float)($_POST['reorderLevel'] ?? 5),
            'unit_of_measure' => $_POST['unitOfMeasure'] ?? 'each',
            'is_active' => isset($_POST['isActive']) ? 1 : 0
        ];

        if ($materialModel->update($id, $data)) {
            $message = 'Material updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update material.';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $result = $materialModel->delete($id);

        if ($result['success']) {
            $message = 'Material deleted successfully!';
            $messageType = 'success';
        } else {
            $message = $result['message'] ?? 'Failed to delete material.';
            $messageType = 'warning';
        }
    } elseif ($action === 'adjustStock') {
        $id = $_POST['id'];
        $adjustment = (float)$_POST['stockAdjustment'];
        $notes = $_POST['adjustmentNotes'] ?? '';

        $newStock = $materialModel->adjustStock($id, $adjustment, 'manual', null, null, $notes);

        if ($newStock !== false) {
            $message = "Stock adjusted successfully. New stock level: $newStock";
            $messageType = 'success';
        } else {
            $message = 'Failed to adjust stock.';
            $messageType = 'danger';
        }
    }
}

// Handle import success redirect
if (isset($_GET['imported']) && $_GET['imported'] === '1') {
    $message = 'Materials imported successfully!';
    $messageType = 'success';
}

// Build filters
$filters = [];
if (!empty($_GET['supplier'])) {
    $filters['supplier_id'] = $_GET['supplier'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (!empty($_GET['lowStock'])) {
    $filters['low_stock'] = true;
}

// Sorting
$sort = $_GET['sort'] ?? 'item_name';
$sortDir = $_GET['dir'] ?? 'ASC';

// Get materials with sorting
$materials = $materialModel->getAll($filters, $sort, $sortDir);

// Helper function for sort links
function sortLink($column, $label, $currentSort, $currentDir) {
    $params = $_GET;
    $params['sort'] = $column;
    $params['dir'] = ($currentSort === $column && $currentDir === 'ASC') ? 'DESC' : 'ASC';
    $url = '?' . http_build_query($params);

    $arrow = '';
    if ($currentSort === $column) {
        $arrow = $currentDir === 'ASC' ? ' ▲' : ' ▼';
    }

    return '<a href="' . htmlspecialchars($url) . '" class="sort-link">' . $label . $arrow . '</a>';
}

// Get suppliers for dropdown
$suppliers = $supplierModel->getActive();

// Get specific material for editing
$editMaterial = null;
if (isset($_GET['edit'])) {
    $editMaterial = $materialModel->getById($_GET['edit']);
}

// Check if showing stock adjustment modal
$adjustStockMaterial = null;
if (isset($_GET['adjustStock'])) {
    $adjustStockMaterial = $materialModel->getById($_GET['adjustStock']);
}

$showForm = isset($_GET['action']) && $_GET['action'] === 'new' || $editMaterial;
$showImport = isset($_GET['action']) && $_GET['action'] === 'import';
?>

<div class="page-header">
    <h1 class="page-title">Materials</h1>
    <div class="page-actions">
        <form method="GET" style="display: inline-block; margin-right: 10px;">
            <input type="text" name="search" placeholder="Search materials..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="form-control" style="width: 200px; display: inline-block;">
            <select name="supplier" class="form-control" style="width: 150px; display: inline-block;">
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo $supplier['id']; ?>" <?php echo ($_GET['supplier'] ?? '') == $supplier['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($supplier['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label style="margin-left: 10px;">
                <input type="checkbox" name="lowStock" value="1" <?php echo !empty($_GET['lowStock']) ? 'checked' : ''; ?>>
                Low Stock Only
            </label>
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
        <a href="?action=import" class="btn btn-info">Import CSV</a>
        <a href="?action=new" class="btn btn-primary">+ New Material</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($showImport): ?>
<div class="form-container">
    <h2>Import Materials from CSV</h2>
    <p>Upload a CSV file with the following columns (header names are flexible):</p>
    <ul>
        <li><strong>Supplier</strong> - Supplier name (will be created if doesn't exist)</li>
        <li><strong>Manufacture</strong> or <strong>Manufacturers Code</strong> - Product code/SKU</li>
        <li><strong>Item</strong> - Product name</li>
        <li><strong>Cost Excl</strong> - Cost excluding GST</li>
        <li><strong>Sell</strong> - Selling price</li>
        <li><strong>Comments</strong> - Notes (optional)</li>
        <li><strong>Stock on hand</strong> - Current stock level (optional)</li>
        <li><strong>Reorder Quantity</strong> - Minimum order quantity (optional)</li>
        <li><strong>Unit Of Measure</strong> - Unit (optional, defaults to 'each')</li>
    </ul>
    <p><a href="quoting/materials_template.csv" download>Download sample CSV template</a></p>
    <form method="POST" action="api/materialsImport.php" enctype="multipart/form-data" id="importForm">
        <div class="form-group">
            <label for="csvFile">CSV File (.csv)</label>
            <input type="file" id="csvFile" name="csvFile" class="form-control" accept=".csv" required>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="updateExisting" value="1">
                Update existing materials (match by Manufacturers Code + Supplier)
            </label>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Import</button>
            <a href="materials.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php elseif ($showForm): ?>
<div class="form-container">
    <h2><?php echo $editMaterial ? 'Edit Material' : 'New Material'; ?></h2>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $editMaterial ? 'update' : 'create'; ?>">
        <?php if ($editMaterial): ?>
            <input type="hidden" name="id" value="<?php echo $editMaterial['id']; ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group" style="flex: 1;">
                <label for="itemName">Item Name *</label>
                <input type="text" id="itemName" name="itemName" class="form-control" value="<?php echo htmlspecialchars($editMaterial['item_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="manufacturersCode">Manufacturer's Code</label>
                <input type="text" id="manufacturersCode" name="manufacturersCode" class="form-control" value="<?php echo htmlspecialchars($editMaterial['manufacturers_code'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" style="flex: 1;">
                <label for="supplierId">Supplier</label>
                <select id="supplierId" name="supplierId" class="form-control">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['id']; ?>" <?php echo ($editMaterial['supplier_id'] ?? '') == $supplier['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="unitOfMeasure">Unit of Measure</label>
                <input type="text" id="unitOfMeasure" name="unitOfMeasure" class="form-control" value="<?php echo htmlspecialchars($editMaterial['unit_of_measure'] ?? 'each'); ?>" placeholder="e.g., each, metre, kg">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" style="flex: 1;">
                <label for="costExcl">Cost (Excl GST) $</label>
                <input type="number" id="costExcl" name="costExcl" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($editMaterial['cost_excl'] ?? '0'); ?>">
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="sellPrice">Sell Price $</label>
                <input type="number" id="sellPrice" name="sellPrice" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($editMaterial['sell_price'] ?? '0'); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" style="flex: 1;">
                <label for="stockOnHand">Stock on Hand</label>
                <input type="number" id="stockOnHand" name="stockOnHand" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($editMaterial['stock_on_hand'] ?? '0'); ?>">
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="reorderLevel">Reorder Level</label>
                <input type="number" id="reorderLevel" name="reorderLevel" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($editMaterial['reorder_level'] ?? '5'); ?>">
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="reorderQuantity">Reorder Quantity</label>
                <input type="number" id="reorderQuantity" name="reorderQuantity" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($editMaterial['reorder_quantity'] ?? '0'); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="comments">Comments</label>
            <textarea id="comments" name="comments" class="form-control"><?php echo htmlspecialchars($editMaterial['comments'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="isActive" <?php echo ($editMaterial['is_active'] ?? 1) ? 'checked' : ''; ?>>
                Active
            </label>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Save Material</button>
            <a href="materials.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php elseif ($adjustStockMaterial): ?>
<div class="form-container">
    <h2>Adjust Stock: <?php echo htmlspecialchars($adjustStockMaterial['item_name']); ?></h2>
    <p>Current stock: <strong><?php echo $adjustStockMaterial['stock_on_hand']; ?></strong> <?php echo htmlspecialchars($adjustStockMaterial['unit_of_measure']); ?></p>
    <form method="POST">
        <input type="hidden" name="action" value="adjustStock">
        <input type="hidden" name="id" value="<?php echo $adjustStockMaterial['id']; ?>">

        <div class="form-group">
            <label for="stockAdjustment">Adjustment Amount (use negative for reduction)</label>
            <input type="number" id="stockAdjustment" name="stockAdjustment" class="form-control" step="0.01" required>
        </div>

        <div class="form-group">
            <label for="adjustmentNotes">Notes</label>
            <textarea id="adjustmentNotes" name="adjustmentNotes" class="form-control" placeholder="Reason for adjustment"></textarea>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Apply Adjustment</button>
            <a href="materials.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th><?php echo sortLink('item_name', 'Item', $sort, $sortDir); ?></th>
                <th><?php echo sortLink('supplier_name', 'Supplier', $sort, $sortDir); ?></th>
                <th><?php echo sortLink('manufacturers_code', 'Code', $sort, $sortDir); ?></th>
                <th><?php echo sortLink('cost_excl', 'Cost (Excl)', $sort, $sortDir); ?></th>
                <th><?php echo sortLink('sell_price', 'Sell Price', $sort, $sortDir); ?></th>
                <th><?php echo sortLink('stock_on_hand', 'Stock', $sort, $sortDir); ?></th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($materials as $material): ?>
                <?php
                $isLowStock = $material['stock_on_hand'] <= $material['reorder_level'];
                ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($material['item_name']); ?></strong>
                        <?php if ($material['comments']): ?>
                            <br><small style="color: #666;"><?php echo htmlspecialchars(substr($material['comments'], 0, 40)); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($material['supplier_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($material['manufacturers_code'] ?? '-'); ?></td>
                    <td>$<?php echo number_format($material['cost_excl'], 2); ?></td>
                    <td>$<?php echo number_format($material['sell_price'], 2); ?></td>
                    <td>
                        <span class="<?php echo $isLowStock ? 'stock-low' : ''; ?>">
                            <?php echo $material['stock_on_hand']; ?>
                        </span>
                        <?php if ($isLowStock): ?>
                            <span class="badge badge-warning" title="Below reorder level of <?php echo $material['reorder_level']; ?>">Low</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($material['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?edit=<?php echo $material['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="?adjustStock=<?php echo $material['id']; ?>" class="btn btn-sm btn-info">Stock</a>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this material?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $material['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (count($materials) === 0): ?>
        <p style="text-align: center; padding: 40px; color: #666;">
            <?php echo !empty($filters) ? 'No materials found matching your filters.' : 'No materials yet. Create your first material or import from CSV!'; ?>
        </p>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(importForm);
            const submitBtn = importForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Importing...';

            fetch('api/materialsImport.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Create result message
                let alertClass = data.success ? 'alert-success' : 'alert-danger';
                let message = data.message;

                if (data.success) {
                    message = `Import completed successfully! ${data.inserted} materials inserted, ${data.updated} updated.`;
                }

                if (data.errors && data.errors.length > 0) {
                    message += '<br><br><strong>Errors:</strong><ul>';
                    data.errors.forEach(err => {
                        message += `<li>${err}</li>`;
                    });
                    message += '</ul>';
                }

                // Show result alert
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert ${alertClass}`;
                alertDiv.innerHTML = message;

                const formContainer = importForm.closest('.form-container');
                formContainer.insertBefore(alertDiv, importForm);

                // Reset form and button
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                importForm.reset();

                // If successful, redirect after a moment
                if (data.success) {
                    setTimeout(() => {
                        window.location.href = 'materials.php?imported=1';
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Import error:', error);
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger';
                alertDiv.textContent = 'Import failed: ' + error.message;

                const formContainer = importForm.closest('.form-container');
                formContainer.insertBefore(alertDiv, importForm);

                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
});
</script>

<style>
.sort-link {
    color: inherit;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
}
.sort-link:hover {
    color: #007bff;
}
thead th {
    cursor: pointer;
    white-space: nowrap;
}
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
.badge-warning {
    background-color: #ffc107;
    color: #212529;
}
.stock-low {
    color: #dc3545;
    font-weight: bold;
}
.form-row {
    display: flex;
    gap: 15px;
}
.form-row .form-group {
    flex: 1;
}
.btn-info {
    background-color: #17a2b8;
    color: white;
}
.btn-info:hover {
    background-color: #138496;
}
</style>

<?php require_once 'includes/footer.php'; ?>
