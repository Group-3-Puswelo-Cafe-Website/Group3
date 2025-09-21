<?php
require '../db.php';
require '../shared/config.php';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $pdo->beginTransaction();
        
        // Delete requisition items first
        $stmt = $pdo->prepare("DELETE FROM purchase_requisition_items WHERE requisition_id = ?");
        $stmt->execute([$_GET['id']]);
        
        // Delete requisition
        $stmt = $pdo->prepare("DELETE FROM purchase_requisitions WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        
        $pdo->commit();
        header("Location: purchase_requisitions.php?status=deleted");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error deleting requisition: " . $e->getMessage();
    }
}

// Handle form submission for new requisition
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requisition_number = 'REQ-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $requested_by = $_POST['requested_by'];
    $description = $_POST['description'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO purchase_requisitions (requisition_number, requested_by, request_date, status, description) 
                               VALUES (?, ?, NOW(), 'pending', ?)");
        $stmt->execute([$requisition_number, $requested_by, $description]);
        
        $requisition_id = $pdo->lastInsertId();
        
        // Handle items
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['description']) && $item['quantity'] > 0) {
                    // First, check if the product exists
                    $stmt = $pdo->prepare("SELECT id FROM products WHERE name LIKE ? OR sku LIKE ? LIMIT 1");
                    $stmt->execute(['%' . $item['description'] . '%', '%' . $item['description'] . '%']);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($product) {
                        $product_id = $product['id'];
                    } else {
                        // If product doesn't exist, create a simple one
                        $stmt = $pdo->prepare("INSERT INTO products (sku, name, description, unit) VALUES (?, ?, ?, ?)");
                        $sku = 'PROD-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                        $stmt->execute([$sku, $item['description'], $item['description'], $item['unit'] ?: 'pcs']);
                        $product_id = $pdo->lastInsertId();
                    }
                    
                    // Insert the requisition item with correct column names
                    $stmt = $pdo->prepare("INSERT INTO purchase_requisition_items (requisition_id, product_id, quantity, estimated_unit_price) 
                                           VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $requisition_id, 
                        $product_id, 
                        $item['quantity'], 
                        $item['estimated_cost'] ?? 0
                    ]);
                }
            }
        }
        
        header("Location: purchase_requisitions.php?status=success");
        exit;
    } catch (Exception $e) {
        $error = "Error creating requisition: " . $e->getMessage();
    }
}

// Handle approval
if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE purchase_requisitions 
                               SET status = 'approved', approved_by = 'Admin', approval_date = NOW() 
                               WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header("Location: purchase_requisitions.php?status=approved");
        exit;
    } catch (Exception $e) {
        $error = "Error approving requisition: " . $e->getMessage();
    }
}

// Load all requisitions with item details
$requisitions = $pdo->query("
    SELECT pr.*,
           (SELECT COUNT(*) FROM purchase_requisition_items WHERE requisition_id = pr.id) as item_count
    FROM purchase_requisitions pr
    ORDER BY pr.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Requisitions</title>
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
            max-width: 700px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
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
        .item-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .item-row input {
            flex: 1;
        }
        .btn-remove {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-add {
            background: #27ae60;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 15px;
        }
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
        .req-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .req-details h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .detail-value {
            flex: 1;
        }
        .items-section {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .items-header {
            background: #34495e;
            color: white;
            padding: 12px 15px;
            font-weight: bold;
        }
        .items-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }
        .items-list li {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .items-list li:last-child {
            border-bottom: none;
        }
        .item-info {
            flex: 1;
        }
        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .item-meta {
            color: #666;
            font-size: 14px;
        }
        .item-price {
            text-align: right;
            min-width: 150px;
        }
        .item-unit-price {
            color: #666;
            font-size: 14px;
        }
        .item-total {
            font-weight: bold;
            color: #2c3e50;
            font-size: 16px;
        }
        .total-section {
            background: #ecf0f1;
            padding: 15px;
            text-align: right;
            font-weight: bold;
            font-size: 18px;
            color: #2c3e50;
            border-top: 1px solid #ddd;
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
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>

    <div class="container" style="margin-left: 18rem;">
        <div class="header">
            <h1>Purchase Requisitions</h1>
            <div>
                <button class="btn btn-primary" onclick="openAddModal()">+ New Requisition</button>
            </div>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div class="card" style="background-color: #d4edda; border-left: 4px solid #27ae60; margin-bottom: 20px;">
                <p style="color: #155724; margin: 0;">Requisition created successfully!</p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'approved'): ?>
            <div class="card" style="background-color: #d4edda; border-left: 4px solid #27ae60; margin-bottom: 20px;">
                <p style="color: #155724; margin: 0;">Requisition approved successfully!</p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
            <div class="card" style="background-color: #d4edda; border-left: 4px solid #27ae60; margin-bottom: 20px;">
                <p style="color: #155724; margin: 0;">Requisition deleted successfully!</p>
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
                        <th>Requisition No.</th>
                        <th>Requested By</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th>Request Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requisitions as $req): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($req['requisition_number']); ?></td>
                            <td><?php echo htmlspecialchars($req['requested_by']); ?></td>
                            <td><?php echo htmlspecialchars(substr($req['description'], 0, 50) . '...'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($req['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($req['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo (int)$req['item_count']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($req['request_date'])); ?></td>
                            <td>
                                <button class="btn" onclick="viewRequisition(<?php echo $req['id']; ?>)">View</button>
                                <?php if ($req['status'] === 'pending'): ?>
                                    <button class="btn btn-success" onclick="approveRequisition(<?php echo $req['id']; ?>)">Approve</button>
                                <?php endif; ?>
                                <button class="btn btn-danger" onclick="deleteRequisition(<?php echo $req['id']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Requisition Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>New Purchase Requisition</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="post" action="<?php echo BASE_URL; ?>Module3/purchase_requisitions.php" id="requisitionForm">
                <div class="form-row">
                    <div class="col">
                        <label>Requested By<input type="text" class="input" name="requested_by" required></label>
                    </div>
                </div>
                <div class="form-row">
                    <label>Description<textarea class="input" name="description" rows="3" required></textarea></label>
                </div>
                
                <h3>Items</h3>
                <div id="itemsContainer">
                    <div class="item-row">
                        <input type="text" class="input" placeholder="Product Name" name="items[0][description]" required>
                        <input type="number" class="input" placeholder="Qty" name="items[0][quantity]" min="1" required style="width: 100px;">
                        <input type="text" class="input" placeholder="Unit" name="items[0][unit]" style="width: 100px;">
                        <input type="number" class="input" placeholder="Unit Price" name="items[0][estimated_cost]" step="0.01" style="width: 120px;">
                        <button type="button" class="btn-remove" onclick="removeItem(this)">×</button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addItem()">+ Add Item</button>
                
                <div class="form-row" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Submit Requisition</button>
                    <button type="button" class="btn" onclick="closeAddModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Requisition Modal -->
    <div class="modal" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Requisition Details</h2>
                <span class="close" onclick="closeViewModal()">&times;</span>
            </div>
            <div id="viewContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        let itemCount = 1;

        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
            document.getElementById('requisitionForm').reset();
            // Reset items to just one row
            document.getElementById('itemsContainer').innerHTML = `
                <div class="item-row">
                    <input type="text" class="input" placeholder="Product Name" name="items[0][description]" required>
                    <input type="number" class="input" placeholder="Qty" name="items[0][quantity]" min="1" required style="width: 100px;">
                    <input type="text" class="input" placeholder="Unit" name="items[0][unit]" style="width: 100px;">
                    <input type="number" class="input" placeholder="Unit Price" name="items[0][estimated_cost]" step="0.01" style="width: 120px;">
                    <button type="button" class="btn-remove" onclick="removeItem(this)">×</button>
                </div>
            `;
            itemCount = 1;
        }

        function addItem() {
            const container = document.getElementById('itemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'item-row';
            newItem.innerHTML = `
                <input type="text" class="input" placeholder="Product Name" name="items[${itemCount}][description]" required>
                <input type="number" class="input" placeholder="Qty" name="items[${itemCount}][quantity]" min="1" required style="width: 100px;">
                <input type="text" class="input" placeholder="Unit" name="items[${itemCount}][unit]" style="width: 100px;">
                <input type="number" class="input" placeholder="Unit Price" name="items[${itemCount}][estimated_cost]" step="0.01" style="width: 120px;">
                <button type="button" class="btn-remove" onclick="removeItem(this)">×</button>
            `;
            container.appendChild(newItem);
            itemCount++;
        }

        function removeItem(button) {
            const container = document.getElementById('itemsContainer');
            if (container.children.length > 1) {
                button.parentElement.remove();
            }
        }

        function viewRequisition(id) {
            // Fetch requisition details via AJAX
            fetch(`<?php echo BASE_URL; ?>Module3/get_requisition_details.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    const req = data.requisition;
                    
                    // Build requisition details HTML
                    let detailsHtml = `
                        <div class="req-details">
                            <h3>Requisition Information</h3>
                            <div class="detail-row">
                                <div class="detail-label">Requisition No.:</div>
                                <div class="detail-value">${req.requisition_number}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Requested By:</div>
                                <div class="detail-value">${req.requested_by}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Request Date:</div>
                                <div class="detail-value">${new Date(req.request_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Status:</div>
                                <div class="detail-value">
                                    <span class="status-badge status-${req.status.toLowerCase()}">${req.status.charAt(0).toUpperCase() + req.status.slice(1)}</span>
                                </div>
                            </div>
                            ${req.approved_by ? `
                                <div class="detail-row">
                                    <div class="detail-label">Approved By:</div>
                                    <div class="detail-value">${req.approved_by}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Approval Date:</div>
                                    <div class="detail-value">${new Date(req.approval_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                                </div>
                            ` : ''}
                            <div class="detail-row">
                                <div class="detail-label">Description:</div>
                                <div class="detail-value">${req.description}</div>
                            </div>
                        </div>
                    `;
                    
                    // Build items list HTML
                    let itemsHtml = `
                        <div class="items-section">
                            <div class="items-header">Items Requested</div>
                            <ul class="items-list">
                    `;
                    
                    let totalAmount = 0;
                    
                    if (data.items && data.items.length > 0) {
                        data.items.forEach(item => {
                            const itemTotal = item.quantity * item.estimated_unit_price;
                            totalAmount += itemTotal;
                            
                            itemsHtml += `
                                <li>
                                    <div class="item-info">
                                        <div class="item-name">${item.product_name}</div>
                                        <div class="item-meta">${item.quantity} ${item.unit || 'pcs'}</div>
                                    </div>
                                    <div class="item-price">
                                        <div class="item-unit-price">₱${parseFloat(item.estimated_unit_price).toFixed(2)} each</div>
                                        <div class="item-total">₱${itemTotal.toFixed(2)}</div>
                                    </div>
                                </li>
                            `;
                        });
                    } else {
                        itemsHtml += '<li style="text-align: center; color: #999;">No items found</li>';
                    }
                    
                    itemsHtml += `
                            </ul>
                            <div class="total-section">
                                Total Amount: ₱${totalAmount.toFixed(2)}
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('viewContent').innerHTML = detailsHtml + itemsHtml;
                    document.getElementById('viewModal').style.display = 'flex';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading requisition details. Please check the console for more information.');
                });
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function approveRequisition(id) {
            if (confirm('Are you sure you want to approve this requisition?')) {
                window.location.href = '<?php echo BASE_URL; ?>Module3/purchase_requisitions.php?action=approve&id=' + id;
            }
        }

        function deleteRequisition(id) {
            if (confirm('Are you sure you want to delete this requisition? This action cannot be undone.')) {
                window.location.href = '<?php echo BASE_URL; ?>Module3/purchase_requisitions.php?action=delete&id=' + id;
            }
        }

        window.onclick = function(event) {
            if (event.target === document.getElementById('addModal')) {
                closeAddModal();
            }
            if (event.target === document.getElementById('viewModal')) {
                closeViewModal();
            }
        }
    </script>
</body>
</html>