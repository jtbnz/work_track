<?php
$pageTitle = 'Materials';
require_once 'includes/header.php';
require_once 'includes/models/Material.php';
require_once 'includes/models/Supplier.php';
require_once 'includes/models/MiscMaterial.php';
require_once 'includes/models/FoamGrade.php';
require_once 'includes/models/FoamProduct.php';

$materialModel = new Material();
$supplierModel = new Supplier();
$miscModel = new MiscMaterial();
$foamGradeModel = new FoamGrade();
$foamProductModel = new FoamProduct();

$message = '';
$messageType = '';

// Get active tab (materials, foam, misc)
$activeTab = $_GET['tab'] ?? 'materials';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Materials actions
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
    // Foam Grade actions
    elseif ($action === 'createFoamGrade') {
        $data = [
            'grade_code' => $_POST['gradeCode'],
            'description' => $_POST['description'] ?? '',
            'is_active' => isset($_POST['isActive']) ? 1 : 0
        ];

        if ($foamGradeModel->create($data)) {
            $message = 'Foam grade created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to create foam grade.';
            $messageType = 'danger';
        }
        $activeTab = 'foam';
    } elseif ($action === 'updateFoamGrade') {
        $id = $_POST['id'];
        $data = [
            'grade_code' => $_POST['gradeCode'],
            'description' => $_POST['description'] ?? '',
            'is_active' => isset($_POST['isActive']) ? 1 : 0
        ];

        if ($foamGradeModel->update($id, $data)) {
            $message = 'Foam grade updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update foam grade.';
            $messageType = 'danger';
        }
        $activeTab = 'foam';
    } elseif ($action === 'deleteFoamGrade') {
        $id = $_POST['id'];
        $result = $foamGradeModel->delete($id);

        if ($result['success']) {
            $message = 'Foam grade deleted successfully!';
            $messageType = 'success';
        } else {
            $message = $result['message'] ?? 'Failed to delete foam grade.';
            $messageType = 'warning';
        }
        $activeTab = 'foam';
    }
    // Foam Product actions
    elseif ($action === 'createFoamProduct') {
        $data = [
            'grade_id' => $_POST['gradeId'],
            'thickness' => $_POST['thickness'],
            'sheet_cost' => (float)$_POST['sheetCost'],
            'sheet_area' => (float)($_POST['sheetArea'] ?? 3.91),
            'is_active' => isset($_POST['isActive']) ? 1 : 0
        ];

        if ($foamProductModel->create($data)) {
            $message = 'Foam product created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to create foam product.';
            $messageType = 'danger';
        }
        $activeTab = 'foam';
    } elseif ($action === 'updateFoamProduct') {
        $id = $_POST['id'];
        $data = [
            'grade_id' => $_POST['gradeId'],
            'thickness' => $_POST['thickness'],
            'sheet_cost' => (float)$_POST['sheetCost'],
            'sheet_area' => (float)($_POST['sheetArea'] ?? 3.91),
            'is_active' => isset($_POST['isActive']) ? 1 : 0
        ];

        if ($foamProductModel->update($id, $data)) {
            $message = 'Foam product updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update foam product.';
            $messageType = 'danger';
        }
        $activeTab = 'foam';
    } elseif ($action === 'deleteFoamProduct') {
        $id = $_POST['id'];
        $result = $foamProductModel->delete($id);

        if ($result['success']) {
            $message = 'Foam product deleted successfully!';
            $messageType = 'success';
        } else {
            $message = $result['message'] ?? 'Failed to delete foam product.';
            $messageType = 'warning';
        }
        $activeTab = 'foam';
    }
    // Misc Material actions
    elseif ($action === 'createMisc') {
        $data = [
            'name' => $_POST['name'],
            'fixed_price' => (float)$_POST['fixedPrice'],
            'is_active' => isset($_POST['isActive']) ? 1 : 0
        ];

        if ($miscModel->create($data)) {
            $message = 'Misc charge created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to create misc charge.';
            $messageType = 'danger';
        }
        $activeTab = 'misc';
    } elseif ($action === 'updateMisc') {
        $id = $_POST['id'];
        $data = [
            'name' => $_POST['name'],
            'fixed_price' => (float)$_POST['fixedPrice'],
            'is_active' => isset($_POST['isActive']) ? 1 : 0
        ];

        if ($miscModel->update($id, $data)) {
            $message = 'Misc charge updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update misc charge.';
            $messageType = 'danger';
        }
        $activeTab = 'misc';
    } elseif ($action === 'deleteMisc') {
        $id = $_POST['id'];
        $result = $miscModel->delete($id);

        if ($result['success']) {
            $message = 'Misc charge deleted successfully!';
            $messageType = 'success';
        } else {
            $message = $result['message'] ?? 'Failed to delete misc charge.';
            $messageType = 'warning';
        }
        $activeTab = 'misc';
    }
}

// Handle import success redirect
if (isset($_GET['imported']) && $_GET['imported'] === '1') {
    $message = 'Materials imported successfully!';
    $messageType = 'success';
}

// Build filters for materials tab
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

// Sorting for materials
$sort = $_GET['sort'] ?? 'item_name';
$sortDir = $_GET['dir'] ?? 'ASC';

// Get data
$materials = $materialModel->getAll($filters, $sort, $sortDir);
$suppliers = $supplierModel->getActive();
$foamGrades = $foamGradeModel->getWithProducts();
$miscMaterials = $miscModel->getAll();

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

// Get specific items for editing
$editMaterial = null;
if (isset($_GET['edit']) && $activeTab === 'materials') {
    $editMaterial = $materialModel->getById($_GET['edit']);
}

$adjustStockMaterial = null;
if (isset($_GET['adjustStock'])) {
    $adjustStockMaterial = $materialModel->getById($_GET['adjustStock']);
}

$editFoamGrade = null;
if (isset($_GET['editGrade'])) {
    $editFoamGrade = $foamGradeModel->getById($_GET['editGrade']);
    $activeTab = 'foam';
}

$editFoamProduct = null;
if (isset($_GET['editProduct'])) {
    $editFoamProduct = $foamProductModel->getById($_GET['editProduct']);
    $activeTab = 'foam';
}

$editMisc = null;
if (isset($_GET['editMisc'])) {
    $editMisc = $miscModel->getById($_GET['editMisc']);
    $activeTab = 'misc';
}

$showForm = isset($_GET['action']) && $_GET['action'] === 'new' || $editMaterial;
$showImport = isset($_GET['action']) && $_GET['action'] === 'import';
$showFoamGradeForm = isset($_GET['action']) && $_GET['action'] === 'newGrade' || $editFoamGrade;
$showFoamProductForm = isset($_GET['action']) && $_GET['action'] === 'newProduct' || $editFoamProduct;
$showMiscForm = isset($_GET['action']) && $_GET['action'] === 'newMisc' || $editMisc;

// Get default sheet area for foam form
$db = Database::getInstance();
$defaultSheetArea = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'foam_default_sheet_area'");
$defaultSheetArea = $defaultSheetArea ? (float)$defaultSheetArea['setting_value'] : 3.91;
?>

<div class="page-header">
    <h1 class="page-title">Materials & Products</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Tab Navigation -->
<div class="tab-navigation">
    <a href="?tab=materials" class="tab-link <?php echo $activeTab === 'materials' ? 'active' : ''; ?>">Materials</a>
    <a href="?tab=foam" class="tab-link <?php echo $activeTab === 'foam' ? 'active' : ''; ?>">Foam Products</a>
    <a href="?tab=misc" class="tab-link <?php echo $activeTab === 'misc' ? 'active' : ''; ?>">Miscellaneous</a>
</div>

<!-- Materials Tab -->
<?php if ($activeTab === 'materials'): ?>
<div class="tab-content">
    <div class="tab-actions">
        <form method="GET" style="display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <input type="hidden" name="tab" value="materials">
            <input type="text" name="search" placeholder="Search materials..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="form-control" style="width: 200px;">
            <select name="supplier" class="form-control" style="width: 150px;">
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo $supplier['id']; ?>" <?php echo ($_GET['supplier'] ?? '') == $supplier['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($supplier['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label style="display: inline-flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="lowStock" value="1" <?php echo !empty($_GET['lowStock']) ? 'checked' : ''; ?>>
                Low Stock Only
            </label>
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
        <div style="display: flex; gap: 10px;">
            <a href="?tab=materials&action=import" class="btn btn-info">Import CSV</a>
            <a href="?tab=materials&action=new" class="btn btn-primary">+ New Material</a>
        </div>
    </div>

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
                <a href="materials.php?tab=materials" class="btn btn-secondary">Cancel</a>
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
                <a href="materials.php?tab=materials" class="btn btn-secondary">Cancel</a>
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
                <a href="materials.php?tab=materials" class="btn btn-secondary">Cancel</a>
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
                            <a href="?tab=materials&edit=<?php echo $material['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="?tab=materials&adjustStock=<?php echo $material['id']; ?>" class="btn btn-sm btn-info">Stock</a>
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
</div>

<!-- Foam Tab -->
<?php elseif ($activeTab === 'foam'): ?>
<div class="tab-content">
    <div class="tab-actions">
        <div></div>
        <div style="display: flex; gap: 10px;">
            <a href="?tab=foam&action=newProduct" class="btn btn-primary">+ New Foam Product</a>
            <a href="?tab=foam&action=newGrade" class="btn btn-secondary">+ New Foam Grade</a>
        </div>
    </div>

    <?php if ($showFoamGradeForm): ?>
    <div class="form-container">
        <h2><?php echo $editFoamGrade ? 'Edit Foam Grade' : 'New Foam Grade'; ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $editFoamGrade ? 'updateFoamGrade' : 'createFoamGrade'; ?>">
            <?php if ($editFoamGrade): ?>
                <input type="hidden" name="id" value="<?php echo $editFoamGrade['id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label for="gradeCode">Grade Code *</label>
                    <input type="text" id="gradeCode" name="gradeCode" class="form-control" value="<?php echo htmlspecialchars($editFoamGrade['grade_code'] ?? ''); ?>" required placeholder="e.g., 38-200">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" class="form-control" value="<?php echo htmlspecialchars($editFoamGrade['description'] ?? ''); ?>" placeholder="e.g., Standard upholstery foam">
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="isActive" <?php echo ($editFoamGrade['is_active'] ?? 1) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save Foam Grade</button>
                <a href="materials.php?tab=foam" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <?php elseif ($showFoamProductForm): ?>
    <div class="form-container">
        <h2><?php echo $editFoamProduct ? 'Edit Foam Product' : 'New Foam Product'; ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $editFoamProduct ? 'updateFoamProduct' : 'createFoamProduct'; ?>">
            <?php if ($editFoamProduct): ?>
                <input type="hidden" name="id" value="<?php echo $editFoamProduct['id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label for="gradeId">Foam Grade *</label>
                    <select id="gradeId" name="gradeId" class="form-control" required>
                        <option value="">-- Select Grade --</option>
                        <?php foreach ($foamGrades as $grade): ?>
                            <option value="<?php echo $grade['id']; ?>" <?php echo ($editFoamProduct['grade_id'] ?? '') == $grade['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grade['grade_code']); ?>
                                <?php if ($grade['description']): ?>
                                    - <?php echo htmlspecialchars($grade['description']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="thickness">Thickness *</label>
                    <input type="text" id="thickness" name="thickness" class="form-control" value="<?php echo htmlspecialchars($editFoamProduct['thickness'] ?? ''); ?>" required placeholder="e.g., 50mm">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label for="sheetCost">Sheet Cost ($) *</label>
                    <input type="number" id="sheetCost" name="sheetCost" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($editFoamProduct['sheet_cost'] ?? ''); ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="sheetArea">Sheet Area (m²)</label>
                    <input type="number" id="sheetArea" name="sheetArea" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($editFoamProduct['sheet_area'] ?? $defaultSheetArea); ?>">
                    <small style="color: #666;">Default: <?php echo $defaultSheetArea; ?> m²</small>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="isActive" <?php echo ($editFoamProduct['is_active'] ?? 1) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save Foam Product</button>
                <a href="materials.php?tab=foam" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <?php else: ?>
    <div class="table-container">
        <?php if (count($foamGrades) === 0): ?>
            <p style="text-align: center; padding: 40px; color: #666;">
                No foam grades yet. Create your first foam grade!
            </p>
        <?php else: ?>
            <?php foreach ($foamGrades as $grade): ?>
            <div class="foam-grade-section">
                <div class="foam-grade-header">
                    <h3>
                        <?php echo htmlspecialchars($grade['grade_code']); ?>
                        <?php if ($grade['description']): ?>
                            <span style="font-weight: normal; color: #666;">- <?php echo htmlspecialchars($grade['description']); ?></span>
                        <?php endif; ?>
                        <?php if (!$grade['is_active']): ?>
                            <span class="badge badge-secondary">Inactive</span>
                        <?php endif; ?>
                    </h3>
                    <div>
                        <a href="?tab=foam&editGrade=<?php echo $grade['id']; ?>" class="btn btn-sm btn-warning">Edit Grade</a>
                        <?php if (count($grade['products']) === 0): ?>
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this foam grade?');">
                                <input type="hidden" name="action" value="deleteFoamGrade">
                                <input type="hidden" name="id" value="<?php echo $grade['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete Grade</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (count($grade['products']) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Thickness</th>
                            <th>Sheet Cost</th>
                            <th>Sheet Area</th>
                            <th>Cost per m²</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grade['products'] as $product): ?>
                            <?php $costPerSqM = $product['sheet_area'] > 0 ? $product['sheet_cost'] / $product['sheet_area'] : 0; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($product['thickness']); ?></strong></td>
                                <td>$<?php echo number_format($product['sheet_cost'], 2); ?></td>
                                <td><?php echo number_format($product['sheet_area'], 2); ?> m²</td>
                                <td>$<?php echo number_format($costPerSqM, 2); ?>/m²</td>
                                <td>
                                    <?php if ($product['is_active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?tab=foam&editProduct=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this foam product?');">
                                        <input type="hidden" name="action" value="deleteFoamProduct">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="padding: 20px; color: #666; text-align: center;">No products for this grade yet.</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Misc Tab -->
<?php elseif ($activeTab === 'misc'): ?>
<div class="tab-content">
    <div class="tab-actions">
        <div></div>
        <a href="?tab=misc&action=newMisc" class="btn btn-primary">+ New Misc Charge</a>
    </div>

    <?php if ($showMiscForm): ?>
    <div class="form-container">
        <h2><?php echo $editMisc ? 'Edit Misc Charge' : 'New Misc Charge'; ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $editMisc ? 'updateMisc' : 'createMisc'; ?>">
            <?php if ($editMisc): ?>
                <input type="hidden" name="id" value="<?php echo $editMisc['id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($editMisc['name'] ?? ''); ?>" required placeholder="e.g., Captain Tape">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="fixedPrice">Default Price ($) *</label>
                    <input type="number" id="fixedPrice" name="fixedPrice" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($editMisc['fixed_price'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="isActive" <?php echo ($editMisc['is_active'] ?? 1) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save Misc Charge</button>
                <a href="materials.php?tab=misc" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Default Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($miscMaterials as $misc): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($misc['name']); ?></strong></td>
                        <td>$<?php echo number_format($misc['fixed_price'], 2); ?></td>
                        <td>
                            <?php if ($misc['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?tab=misc&editMisc=<?php echo $misc['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this misc charge?');">
                                <input type="hidden" name="action" value="deleteMisc">
                                <input type="hidden" name="id" value="<?php echo $misc['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($miscMaterials) === 0): ?>
            <p style="text-align: center; padding: 40px; color: #666;">
                No miscellaneous charges yet. Create your first one!
            </p>
        <?php endif; ?>
    </div>
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

                const alertDiv = document.createElement('div');
                alertDiv.className = `alert ${alertClass}`;
                alertDiv.innerHTML = message;

                const formContainer = importForm.closest('.form-container');
                formContainer.insertBefore(alertDiv, importForm);

                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                importForm.reset();

                if (data.success) {
                    setTimeout(() => {
                        window.location.href = 'materials.php?tab=materials&imported=1';
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
/* Tab Navigation */
.tab-navigation {
    display: flex;
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 20px;
}
.tab-link {
    padding: 12px 24px;
    color: #495057;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    font-weight: 500;
    transition: all 0.2s;
}
.tab-link:hover {
    color: #007bff;
    background-color: #f8f9fa;
}
.tab-link.active {
    color: #007bff;
    border-bottom-color: #007bff;
}

/* Tab Content */
.tab-content {
    padding: 0;
}
.tab-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

/* Foam Grade Sections */
.foam-grade-section {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 20px;
    overflow: hidden;
}
.foam-grade-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
.foam-grade-header h3 {
    margin: 0;
    font-size: 16px;
}
.foam-grade-section table {
    margin: 0;
    border: none;
}
.foam-grade-section table thead th {
    background: #fff;
}

/* Existing Styles */
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
