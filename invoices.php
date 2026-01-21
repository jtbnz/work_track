<?php
$pageTitle = 'Invoices';
require_once 'includes/header.php';

// TODO: Phase 5 - Invoice list and management
?>

<div class="page-header">
    <h1 class="page-title">Invoices</h1>
    <div class="page-actions">
        <!-- TODO: Enable when invoice creation is implemented -->
        <button class="btn btn-primary" disabled title="Create invoices from accepted quotes">+ New Invoice</button>
    </div>
</div>

<div class="alert alert-info">
    <strong>Coming Soon:</strong> Invoice management functionality is under development.
    <br>Invoices will be created by converting accepted quotes.
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Client</th>
                <th>Date</th>
                <th>Due Date</th>
                <th>Total</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                    No invoices yet. Create a quote and convert it to an invoice to get started!
                </td>
            </tr>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
