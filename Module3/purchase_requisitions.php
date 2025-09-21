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
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .close {
            cursor: pointer;
            font-size: 24px;
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
        .items-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .items-preview h4 {
            margin: 0 0 15px 0;
            font-size: 16px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .items-preview ul {
            margin: 0;
            padding-left: 20px;
        }
        .items-preview li {
            margin-bottom: 8px;
            font-size: 14px;
        }
        .items-preview .item-total {
            font-weight: bold;
            color: #2c3e50;
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
                                <span class="badge <?php 
                                    echo $req['status'] == 'approved' ? 'badge-success' : 'badge-warning'; 
                                ?>">
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
                    
                    let itemsHtml = '<div class="items-preview"><h4>Items:</h4><ul>';
                    let totalAmount = 0;
                    
                    if (data.items && data.items.length > 0) {
                        data.items.forEach(item => {
                            const itemTotal = item.quantity * item.estimated_unit_price;
                            totalAmount += itemTotal;
                            itemsHtml += `<li>${item.quantity} ${item.unit || 'pcs'} - ${item.product_name} (Unit Price: $${item.estimated_unit_price}) - <span class="item-total">$${itemTotal.toFixed(2)}</span></li>`;
                        });
                    } else {
                        itemsHtml += '<li>No items</li>';
                    }
                    
                    itemsHtml += `</ul><div style="text-align: right; margin-top: 15px; font-weight: bold;">Total: $${totalAmount.toFixed(2)}</div></div>`;
                    
                    document.getElementById('viewContent').innerHTML = itemsHtml;
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