<?php
require '../db.php';
require '../shared/config.php';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header("Location: suppliers.php?status=deleted");
        exit;
    } catch (Exception $e) {
        $error = "Error deleting supplier: " . $e->getMessage();
    }
}

// Handle form submission for new/edit supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $code = $_POST['code'];
    $name = $_POST['name'];
    $contact_info = $_POST['contact_info'];
    $address = $_POST['address'];
    $performance_rating = $_POST['performance_rating'] ?? null;
    
    try {
        if ($id) {
            // Update existing supplier
            $stmt = $pdo->prepare("UPDATE suppliers SET code = ?, name = ?, contact_info = ?, address = ?, performance_rating = ? WHERE id = ?");
            $stmt->execute([$code, $name, $contact_info, $address, $performance_rating, $id]);
            header("Location: suppliers.php?status=updated");
        } else {
            // Insert new supplier
            $stmt = $pdo->prepare("INSERT INTO suppliers (code, name, contact_info, address, performance_rating) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$code, $name, $contact_info, $address, $performance_rating]);
            header("Location: suppliers.php?status=success");
        }
        exit;
    } catch (Exception $e) {
        $error = "Error saving supplier: " . $e->getMessage();
    }
}

// Load all suppliers
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Load supplier for editing
$edit_supplier = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_supplier = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Suppliers Management</title>
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
        .supplier-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .supplier-info h4 {
            margin-top: 0;
            color: #2c3e50;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .info-value {
            flex: 1;
        }
        .btn-edit {
            background-color: #3498db;
            color: white;
        }
        .btn-edit:hover {
            background-color: #2980b9;
        }
        .rating-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            background: #f39c12;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>

    <div class="container" style="margin-left: 18rem;">
        <div class="header">
            <h1>Suppliers Management</h1>
            <div>
                <button class="btn btn-primary" onclick="openAddModal()">+ New Supplier</button>
            </div>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div class="card" style="background-color: #d4edda; border-left: 4px solid #27ae60; margin-bottom: 20px;">
                <p style="color: #155724; margin: 0;">Supplier created successfully!</p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
            <div class="card" style="background-color: #d4edda; border-left: 4px solid #27ae60; margin-bottom: 20px;">
                <p style="color: #155724; margin: 0;">Supplier updated successfully!</p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
            <div class="card" style="background-color: #d4edda; border-left: 4px solid #27ae60; margin-bottom: 20px;">
                <p style="color: #155724; margin: 0;">Supplier deleted successfully!</p>
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
                        <th>Code</th>
                        <th>Supplier Name</th>
                        <th>Contact Info</th>
                        <th>Rating</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($supplier['code']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($supplier['contact_info'], 0, 50) . '...'); ?></td>
                            <td>
                                <?php if ($supplier['performance_rating']): ?>
                                    <span class="rating-badge"><?php echo number_format($supplier['performance_rating'], 2); ?>/5.00</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn" onclick="viewSupplier(<?php echo $supplier['id']; ?>)">View</button>
                                <button class="btn btn-edit" onclick="editSupplier(<?php echo $supplier['id']; ?>)">Edit</button>
                                <button class="btn btn-danger" onclick="deleteSupplier(<?php echo $supplier['id']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Supplier Modal -->
    <div class="modal" id="supplierModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">New Supplier</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="post" action="<?php echo BASE_URL; ?>Module3/suppliers.php" id="supplierForm">
                <input type="hidden" name="id" id="supplierId">
                
                <div class="form-row">
                    <div class="col">
                        <label>Supplier Code<input type="text" class="input" name="code" id="supplierCode" required maxlength="50"></label>
                    </div>
                    <div class="col">
                        <label>Supplier Name<input type="text" class="input" name="name" id="supplierName" required></label>
                    </div>
                </div>
                
                <div class="form-row">
                    <label>Contact Information
                        <textarea class="input" name="contact_info" id="contactInfo" rows="3" placeholder="Contact person, email, phone, etc."></textarea>
                    </label>
                </div>
                
                <div class="form-row">
                    <label>Address
                        <textarea class="input" name="address" id="supplierAddress" rows="2"></textarea>
                    </label>
                </div>
                
                <div class="form-row">
                    <div class="col">
                        <label>Performance Rating (0-5)
                            <input type="number" class="input" name="performance_rating" id="performanceRating" min="0" max="5" step="0.01" placeholder="e.g., 4.5">
                        </label>
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Create Supplier</button>
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Supplier Modal -->
    <div class="modal" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Supplier Details</h2>
                <span class="close" onclick="closeViewModal()">&times;</span>
            </div>
            <div id="viewContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'New Supplier';
            document.getElementById('submitBtn').textContent = 'Create Supplier';
            document.getElementById('supplierForm').reset();
            document.getElementById('supplierId').value = '';
            document.getElementById('supplierModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('supplierModal').style.display = 'none';
        }

        function editSupplier(id) {
            console.log('Editing supplier with ID:', id);
            const url = `<?php echo BASE_URL; ?>Module3/get_supplier_details.php?id=${id}`;
            console.log('Fetching URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    const supplier = data.supplier;
                    
                    document.getElementById('modalTitle').textContent = 'Edit Supplier';
                    document.getElementById('submitBtn').textContent = 'Update Supplier';
                    document.getElementById('supplierId').value = supplier.id;
                    document.getElementById('supplierCode').value = supplier.code;
                    document.getElementById('supplierName').value = supplier.name;
                    document.getElementById('contactInfo').value = supplier.contact_info || '';
                    document.getElementById('supplierAddress').value = supplier.address || '';
                    document.getElementById('performanceRating').value = supplier.performance_rating || '';
                    
                    document.getElementById('supplierModal').style.display = 'flex';
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error loading supplier details: ' + error.message);
                });
        }

        function viewSupplier(id) {
            console.log('Viewing supplier with ID:', id);
            const url = `<?php echo BASE_URL; ?>Module3/get_supplier_details.php?id=${id}`;
            console.log('Fetching URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    const supplier = data.supplier;
                    
                    let detailsHtml = `
                        <div class="supplier-info">
                            <h4>Supplier Information</h4>
                            <div class="info-row">
                                <div class="info-label">Code:</div>
                                <div class="info-value">${supplier.code}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Name:</div>
                                <div class="info-value">${supplier.name}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Contact Info:</div>
                                <div class="info-value">${supplier.contact_info || 'Not specified'}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Address:</div>
                                <div class="info-value">${supplier.address || 'Not specified'}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Performance Rating:</div>
                                <div class="info-value">
                                    ${supplier.performance_rating ? 
                                        `<span class="rating-badge">${parseFloat(supplier.performance_rating).toFixed(2)}/5.00</span>` : 
                                        'Not rated'
                                    }
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('viewContent').innerHTML = detailsHtml;
                    document.getElementById('viewModal').style.display = 'flex';
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error loading supplier details: ' + error.message);
                });
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function deleteSupplier(id) {
            if (confirm('Are you sure you want to delete this supplier? This action cannot be undone and may affect purchase orders associated with this supplier.')) {
                window.location.href = '<?php echo BASE_URL; ?>Module3/suppliers.php?action=delete&id=' + id;
            }
        }

        window.onclick = function(event) {
            if (event.target === document.getElementById('supplierModal')) {
                closeModal();
            }
            if (event.target === document.getElementById('viewModal')) {
                closeViewModal();
            }
        }
    </script>
</body>
</html>