<?php
session_start();
require '../db.php';
require '../shared/config.php';

// Handle PO ID parameter for pre-selecting a purchase order
$preselected_po_id = isset($_GET['po_id']) ? (int)$_GET['po_id'] : null;
// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header("Location: invoices.php?status=deleted");
        exit;
    } catch (Exception $e) {
        $error = "Error deleting invoice: " . $e->getMessage();
    }
}

// Handle form submission for new invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $po_id = $_POST['po_id'];
    $supplier_id = $_POST['supplier_id'];
    $invoice_date = $_POST['invoice_date'];
    $due_date = $_POST['due_date'];
    $total_amount = $_POST['total_amount'];
    $status = $_POST['status'] ?? 'pending';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO invoices 
            (invoice_number, po_id, supplier_id, invoice_date, due_date, total_amount, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $invoice_number, 
            $po_id, 
            $supplier_id, 
            $invoice_date, 
            $due_date, 
            $total_amount, 
            $status
        ]);
        
        header("Location: invoices.php?status=success");
        exit;
    } catch (Exception $e) {
        $error = "Error creating invoice: " . $e->getMessage();
    }
}

// Handle status update
if (isset($_GET['action']) && in_array($_GET['action'], ['mark_paid', 'mark_overdue']) && isset($_GET['id'])) {
    $status_map = [
        'mark_paid' => 'paid',
        'mark_overdue' => 'overdue'
    ];
    
    try {
        $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
        $stmt->execute([$status_map[$_GET['action']], $_GET['id']]);
        header("Location: invoices.php?status=updated");
        exit;
    } catch (Exception $e) {
        $error = "Error updating invoice status: " . $e->getMessage();
    }
}

// Load all invoices with purchase order and supplier details
$invoices = $pdo->query("
    SELECT i.*,
           po.po_number,
           s.name as supplier_name
    FROM invoices i
    LEFT JOIN purchase_orders po ON i.po_id = po.id
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    ORDER BY i.invoice_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Load delivered purchase orders for dropdown
$purchase_orders = $pdo->query("
    SELECT po.*, s.name as supplier_name, 
           (SELECT SUM(quantity * unit_price) FROM purchase_order_items WHERE po_id = po.id) as total_amount
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    WHERE po.status = 'delivered'
    ORDER BY po.order_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoices</title>
    <link rel="stylesheet" href="styles.css">
    <base href="<?php echo BASE_URL; ?>">
    <style>
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 100;
        }
        .modal-content {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            position: relative;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
        }
        .close {
            cursor: pointer;
            font-size: 24px;
            color: #999;
        }
        .close:hover {
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }
        .po-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #ddd;
        }
        .po-details h4 {
            margin-top: 0;
            color: #2c3e50;
        }
        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }
        .detail-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .detail-value {
            flex: 1;
        }
        
        /* New button styles for better alignment and appearance */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            text-align: center;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-success {
            background-color: #2ecc71;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
        }
        
        .btn-warning {
            background-color: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #e67e22;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background-color: #138496;
        }
        
        /* Table styling improvements */
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .table tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>

    <div class="container" style="margin-left: 18rem;">
        <div class="header">
            <h1>Invoices</h1>
            <div>
                <button class="btn btn-primary" onclick="openAddModal()">+ New Invoice</button>
            </div>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div class="card" style="background-color: #d4edda; border-left: 4px solid #27ae60; margin-bottom: 20px;">
                <p style="color: #155724; margin: 0;">Invoice created successfully!</p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
            <div class="card" style="background-color: #d4edda; border-left: 4px solid #27ae60; margin-bottom: 20px;">
                <p style="color: #155724; margin: 0;">Invoice updated successfully!</p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
            <div class="card" style="background-color: #d4edda; border-left: 4px solid #27ae60; margin-bottom: 20px;">
                <p style="color: #155724; margin: 0;">Invoice deleted successfully!</p>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="card" style="background-color: #f8d7da; border-left: 4px solid #e74c3c; margin-bottom: 20px;">
                <p style="color: #721c24; margin: 0;"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>PO #</th>
                        <th>Supplier</th>
                        <th>Invoice Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                            <td>
                                <?php if ($invoice['po_number']): ?>
                                    <a href="<?php echo BASE_URL; ?>Module3/purchase_orders.php?po=<?php echo urlencode($invoice['po_number']) ?>" target="_blank">
                                        <?php echo htmlspecialchars($invoice['po_number']); ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($invoice['supplier_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($invoice['invoice_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                            <td>₱<?php echo number_format($invoice['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($invoice['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($invoice['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($invoice['po_number']): ?>
                                        <a href="<?php echo BASE_URL; ?>Module3/purchase_orders.php?po=<?php echo urlencode($invoice['po_number']) ?>" class="btn btn-info" title="View Purchase Order">
                                            Back to PO
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($invoice['status'] === 'pending'): ?>
                                        <button class="btn btn-success" onclick="updateStatus(<?php echo $invoice['id']; ?>, 'mark_paid')" title="Mark as Paid">
                                            Mark Paid
                                        </button>
                                        <button class="btn btn-warning" onclick="updateStatus(<?php echo $invoice['id']; ?>, 'mark_overdue')" title="Mark as Overdue">
                                            Mark Overdue
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-danger" onclick="deleteInvoice(<?php echo $invoice['id']; ?>)" title="Delete Invoice">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Invoice Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>New Invoice</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="post" action="<?php echo BASE_URL; ?>Module3/invoices.php">
                <div class="form-row">
                    <div class="col">
                        <label>Select Purchase Order
                            <select class="input" name="po_id" id="poSelect" required onchange="updatePODetails()">
                                <option value="">Select a Purchase Order</option>
                                <?php foreach ($purchase_orders as $po): ?>
                                    <option value="<?php echo $po['id']; ?>" 
                                            data-supplier="<?php echo htmlspecialchars($po['supplier_name']); ?>"
                                            data-amount="<?php echo $po['total_amount']; ?>"
                                            data-supplier-id="<?php echo $po['supplier_id']; ?>">
                                        <?php echo htmlspecialchars($po['po_number']); ?> - 
                                        <?php echo htmlspecialchars($po['supplier_name']); ?> - 
                                        ₱<?php echo number_format($po['total_amount'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                </div>
                
                <div id="poDetails" style="display: none;" class="po-details">
                    <h4>Purchase Order Details</h4>
                    <div class="detail-row">
                        <div class="detail-label">Supplier:</div>
                        <div class="detail-value" id="poSupplier"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Total Amount:</div>
                        <div class="detail-value" id="poAmount"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="col">
                        <label>Invoice Date<input type="date" class="input" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" required></label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="col">
                        <label>Due Date<input type="date" class="input" name="due_date" required></label>
                    </div>
                </div>
                
                <input type="hidden" name="supplier_id" id="supplierId">
                <input type="hidden" name="total_amount" id="totalAmount">
                
                <div class="form-row" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Create Invoice</button>
                    <button type="button" class="btn" onclick="closeAddModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openAddModal() {
        document.getElementById('addModal').style.display = 'flex';
    }

    function closeAddModal() {
        document.getElementById('addModal').style.display = 'none';
        document.getElementById('poSelect').value = '';
        document.getElementById('poDetails').style.display = 'none';
    }

    function updatePODetails() {
        const select = document.getElementById('poSelect');
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption.value) {
            const supplier = selectedOption.getAttribute('data-supplier');
            const amount = selectedOption.getAttribute('data-amount');
            const supplierId = selectedOption.getAttribute('data-supplier-id');
            
            document.getElementById('poSupplier').textContent = supplier;
            document.getElementById('poAmount').textContent = '₱' + parseFloat(amount).toFixed(2);
            document.getElementById('supplierId').value = supplierId;
            document.getElementById('totalAmount').value = amount;
            
            // Set due date to 30 days from invoice date
            const invoiceDate = new Date(document.querySelector('input[name="invoice_date"]').value);
            const dueDate = new Date(invoiceDate);
            dueDate.setDate(dueDate.getDate() + 30);
            document.querySelector('input[name="due_date"]').value = dueDate.toISOString().split('T')[0];
            
            document.getElementById('poDetails').style.display = 'block';
        } else {
            document.getElementById('poDetails').style.display = 'none';
        }
    }

    function updateStatus(id, action) {
        const actionMap = {
            'mark_paid': 'mark this invoice as paid?',
            'mark_overdue': 'mark this invoice as overdue?'
        };
        
        if (confirm(`Are you sure you want to ${actionMap[action]}`)) {
            window.location.href = `<?php echo BASE_URL; ?>Module3/invoices.php?action=${action}&id=${id}`;
        }
    }

    function deleteInvoice(id) {
        if (confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
            window.location.href = `<?php echo BASE_URL; ?>Module3/invoices.php?action=delete&id=${id}`;
        }
    }

    window.onclick = function(event) {
        if (event.target === document.getElementById('addModal')) {
            closeAddModal();
        }
    }

    // Add this to the bottom of the page, inside the script tag
    document.addEventListener('DOMContentLoaded', function() {
        // Check if we need to pre-select a PO
        const urlParams = new URLSearchParams(window.location.search);
        const poId = urlParams.get('po_id');
        
        if (poId) {
            // Open the add modal
            openAddModal();
            
            // Set the PO select value
            const poSelect = document.getElementById('poSelect');
            poSelect.value = poId;
            
            // Trigger the change event to update the details
            updatePODetails();
        }
    });
    </script>
</body>
</html>