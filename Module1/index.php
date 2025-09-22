<?php
require '../db.php';
require '../shared/config.php';

// Function to get transaction details with PO/GR references
function getTransactionDetails($transaction) {
    global $pdo;
    
    $details = [];
    
    if (!empty($transaction['reference_id']) && !empty($transaction['reference_type'])) {
        if ($transaction['reference_type'] === 'gr') {
            // Get goods receipt details
            $stmt = $pdo->prepare("
                SELECT gr.receipt_number, gr.po_id, po.po_number
                FROM goods_receipts gr
                LEFT JOIN purchase_orders po ON gr.po_id = po.id
                WHERE gr.id = ?
            ");
            $stmt->execute([$transaction['reference_id']]);
            $gr_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($gr_data) {
                $details['goods_receipt'] = $gr_data['receipt_number'];
                if ($gr_data['po_number']) {
                    $details['purchase_order'] = [
                        'number' => $gr_data['po_number'],
                        'id' => $gr_data['po_id']
                    ];
                }
            }
        } elseif ($transaction['reference_type'] === 'po') {
            // Get purchase order details
            $stmt = $pdo->prepare("
                SELECT po_number FROM purchase_orders WHERE id = ?
            ");
            $stmt->execute([$transaction['reference_id']]);
            $po_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($po_data) {
                $details['purchase_order'] = [
                    'number' => $po_data['po_number'],
                    'id' => $transaction['reference_id']
                ];
            }
        }
    }
    
    return $details;
}

// ---------- Handle Add / Update ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $sku = $_POST['sku'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $category = $_POST['category'];
    $unit = $_POST['unit'] ?: 'pcs';
    $expiration_date = $_POST['expiration_date'] ?: null;
    $min_qty = (int)($_POST['min_qty'] ?? 0);
    $max_qty = (int)($_POST['max_qty'] ?? 0);
    
    // Fix: Properly handle warehouse_id - convert empty string to null
    $warehouse_id = $_POST['warehouse_id'] ?? null;
    if ($warehouse_id === '') {
        $warehouse_id = null;
    } else {
        $warehouse_id = (int)$warehouse_id;
    }

    try {
        if ($id) {
            // Update product including warehouse_id
            $stmt = $pdo->prepare("UPDATE products 
                SET sku=?, name=?, description=?, category=?, unit=?, expiration_date=?, min_qty=?, max_qty=?, warehouse_id=? 
                WHERE id=?");
            $stmt->execute([$sku, $name, $desc, $category, $unit, $expiration_date, $min_qty, $max_qty, $warehouse_id, $id]);

            // Fix: Delete existing location assignments first
            $pdo->prepare("DELETE FROM product_locations WHERE product_id=?")->execute([$id]);
            
            // Add new location assignment if warehouse selected
            if ($warehouse_id) {
                $pdo->prepare("INSERT INTO product_locations (product_id, location_id, quantity)
                               VALUES (?, ?, 0)")
                    ->execute([$id, $warehouse_id]);
            }
        } else {
            // Add new product including warehouse_id
            $stmt = $pdo->prepare("INSERT INTO products 
                (sku,name,description,category,unit,expiration_date,min_qty,max_qty,warehouse_id) 
                VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$sku, $name, $desc, $category, $unit, $expiration_date, $min_qty, $max_qty, $warehouse_id]);

            $newId = $pdo->lastInsertId();

            // Assign warehouse in product_locations table if selected
            if ($warehouse_id) {
                $pdo->prepare("INSERT INTO product_locations (product_id, location_id, quantity) 
                               VALUES (?, ?, 0)")
                    ->execute([$newId, $warehouse_id]);
            }
        }

        header("Location: index.php?status=success");
        exit;
    } catch (Exception $e) {
        // For debugging - show the actual error
        echo "<div style='color:red; padding:20px; border:1px solid red;'>";
        echo "<h3>Error:</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<p>File: " . $e->getFile() . "</p>";
        echo "<p>Line: " . $e->getLine() . "</p>";
        echo "</div>";
        exit;
    }
}

// ---------- Load list ----------
$search = $_GET['q'] ?? '';
$params = [];
$sql = "SELECT p.*, 
        l.name AS warehouse_name,
        COALESCE((SELECT SUM(pl.quantity) FROM product_locations pl WHERE pl.product_id = p.id),0) AS total_qty
        FROM products p
        LEFT JOIN locations l ON p.warehouse_id = l.id
        WHERE 1";

if ($search) { 
    $sql .= " AND (p.name LIKE :s OR p.sku LIKE :s OR p.category LIKE :s)"; 
    $params[':s']="%$search%"; 
}

$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql); 
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- Load Warehouses ----------
$warehouses = $pdo->query("SELECT id, code, name FROM locations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// ---------- Load Stock Transactions with References ----------
$transactions = $pdo->query("
    SELECT st.*, 
           p.name as product_name,
           l_from.name as location_from_name,
           l_to.name as location_to_name
    FROM stock_transactions st
    LEFT JOIN products p ON st.product_id = p.id
    LEFT JOIN locations l_from ON st.location_from = l_from.id
    LEFT JOIN locations l_to ON st.location_to = l_to.id
    ORDER BY st.trans_date DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Product Item Inventory</title>
  <link rel="stylesheet" href="styles.css">
  <base href="<?php echo BASE_URL; ?>">
  
  <!-- Add Modal CSS -->
  <style>
    /* Modal Styles */
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
      max-width: 500px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      position: relative;
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }
    .close {
      cursor: pointer;
      font-size: 20px;
    }
    .transaction-section {
      margin-top: 30px;
    }
    .transaction-table {
      margin-top: 15px;
    }
    .reference-link {
      color: #3498db;
      text-decoration: none;
    }
    .reference-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<?php include '../shared/sidebar.php'; ?>

<?php if (isset($_GET['status'])): ?>
<script>
  <?php if ($_GET['status'] === 'success'): ?>
    alert("Item saved successfully!");
  <?php elseif ($_GET['status'] === 'error'): ?>
    alert("Failed to save item. Please try again.");
  <?php endif; ?>
</script>
<?php endif; ?>

<div class="container" style="margin-left: 18rem;">
  <div class="header">
    <h1>Product Inventory</h1>
    <div>
      <button class="btn btn-primary" onclick="openAddModal()">+ Add Item</button>
    </div>
  </div>

  <div class="card">
    <form method="get" class="form-row">
      <input class="input" name="q" placeholder="Search by name, sku, category" value="<?php echo htmlspecialchars($search) ?>">
      <button class="btn" type="submit">Search</button>
    </form>

    <table class="table">
      <thead>
        <tr>
          <th>SKU</th><th>Name</th><th>Category</th><th>Unit</th>
          <th>Total Qty</th><th>Min / Max</th><th>Expiration</th><th>Warehouse</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($items as $it): ?>
          <tr>
            <td><?php echo htmlspecialchars($it['sku']) ?></td>
            <td><?php echo htmlspecialchars($it['name']) ?></td>
            <td><?php echo htmlspecialchars($it['category']) ?></td>
            <td><?php echo htmlspecialchars($it['unit']) ?></td>
            <td><?php echo (int)$it['total_qty'] ?></td>
            <td><?php echo (int)$it['min_qty'] ?> / <?php echo (int)$it['max_qty'] ?></td>
            <td class="small"><?php echo $it['expiration_date'] ?: '-' ?></td>
            <td>
              <?php 
                $wh = array_filter($warehouses, fn($w) => $w['id'] == $it['warehouse_id']);
                echo htmlspecialchars($it['warehouse_name'] ?: '-');
              ?>
            </td>
            <td class="actions">
              <button class="btn" onclick='openEditModal(<?php echo json_encode($it) ?>);return false;'>Edit</button>
              <a class="btn" href="<?php echo BASE_URL; ?>Module1/stock_in.php?action=stock-in&id=<?php echo $it['id'] ?>">Stock-in</a>
              <a class="btn" href="<?php echo BASE_URL; ?>Module1/stock_out.php?action=stock-out&id=<?php echo $it['id'] ?>">Stock-out</a>
              <a class="btn" href="#" onclick='openTransferModal(<?php echo json_encode($it) ?>); return false;'>Transfer</a>
              <a class="btn" href="<?php echo BASE_URL; ?>Module1/delete_item.php?id=<?php echo $it['id'] ?>" onclick="return confirm('Delete item?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; if(!count($items)): ?>
          <tr><td colspan="9" class="small">No items found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Stock Transactions Section -->
  <div class="card transaction-section">
    <h2>Recent Stock Transactions</h2>
    <table class="table transaction-table">
      <thead>
        <tr>
          <th>Product</th>
          <th>Quantity</th>
          <th>Type</th>
          <th>Location</th>
          <th>Reference</th>
          <th>Date</th>
          <th>User</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($transactions as $trans): ?>
          <?php $details = getTransactionDetails($trans); ?>
          <tr>
            <td><?php echo htmlspecialchars($trans['product_name']) ?></td>
            <td><?php echo $trans['qty'] ?></td>
            <td><?php echo ucfirst($trans['type']) ?></td>
            <td>
              <?php 
                if ($trans['location_to']) {
                    echo htmlspecialchars($trans['location_to_name']);
                } elseif ($trans['location_from']) {
                    echo htmlspecialchars($trans['location_from_name']);
                } else {
                    echo '-';
                }
              ?>
            </td>
            <td>
              <?php if (!empty($details)): ?>
                <?php if (isset($details['goods_receipt'])): ?>
                  GR: <?php echo htmlspecialchars($details['goods_receipt']) ?><br>
                <?php endif; ?>
                <?php if (isset($details['purchase_order'])): ?>
                  <a href="<?php echo BASE_URL; ?>Module3/purchase_orders.php?id=<?php echo $details['purchase_order']['id'] ?>" target="_blank" class="reference-link">
                    PO: <?php echo htmlspecialchars($details['purchase_order']['number']) ?>
                  </a>
                <?php endif; ?>
              <?php else: ?>
                <?php echo htmlspecialchars($trans['reference'] ?: '-') ?>
              <?php endif; ?>
            </td>
            <td><?php echo $trans['trans_date'] ?></td>
            <td><?php echo htmlspecialchars($trans['user_name']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal" id="itemModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="modalTitle">Add Item</h2>
      <span class="close" onclick="closeModal()">&times;</span>
    </div>
    <form method="post" id="itemForm">
      <input type="hidden" name="id" id="item_id">
      <div class="form-row">
        <div class="col"><label>SKU<input class="input" name="sku" id="item_sku" required></label></div>
        <div class="col"><label>Name<input class="input" name="name" id="item_name" required></label></div>
      </div>
      <div class="form-row">
        <div class="col"><label>Category<input class="input" name="category" id="item_category"></label></div>
        <div class="col"><label>Unit<input class="input" name="unit" id="item_unit" value="pcs"></label></div>
      </div>
      <div class="form-row">
        <div class="col"><label>Expiration Date<input class="input" name="expiration_date" id="item_exp" type="date"></label></div>
        <div class="col"><label>Min Qty<input class="input" name="min_qty" id="item_min" type="number" value="0"></label></div>
        <div class="col"><label>Max Qty<input class="input" name="max_qty" id="item_max" type="number" value="0"></label></div>
      </div>
      <div class="form-row">
        <label>Description<textarea class="input" name="description" id="item_desc"></textarea></label>
      </div>
      <div class="form-row">
        <label>Warehouse
          <select class="input" name="warehouse_id" id="item_warehouse">
            <option value="">-- Select Warehouse --</option>
            <?php foreach($warehouses as $w): ?>
              <option value="<?php echo $w['id'] ?>"><?php echo htmlspecialchars($w['code']." - ".$w['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="form-row">
        <button class="btn btn-primary" type="submit">Save</button>
        <button class="btn" type="button" onclick="closeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Stock-In Modal -->
<div class="modal" id="stockInModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="stockInTitle">Stock-In Product</h2>
      <span class="close" onclick="closeStockInModal()">&times;</span>
    </div>
    <form method="post" id="stockInForm">
      <input type="hidden" name="product_id" id="stockInProductId">
      <p><strong>Product:</strong> <span id="stockInProductName"></span></p>
      <p><strong>Warehouse:</strong> <span id="stockInWarehouse"></span></p>
      <p><strong>Current Quantity:</strong> <span id="stockInCurrentQty"></span></p>

      <label>
        Quantity to Add:
        <input type="number" name="quantity" value="0" min="1" required>
      </label>
      <br><br>
      <button class="btn btn-primary" type="submit">Stock In</button>
      <button class="btn" type="button" onclick="closeStockInModal()">Cancel</button>
    </form>
  </div>
</div>

<!-- Transfer Modal -->
<div class="modal" id="transferModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Transfer Product</h2>
      <span class="close" onclick="closeTransferModal()">&times;</span>
    </div>
    <form method="post" action="<?php echo BASE_URL; ?>Module1/transfer_handle.php">
      <input type="hidden" name="product_id" id="transferProductId">
      <input type="hidden" name="from_warehouse_id" id="transferFromWarehouseId">

      <p><strong>Product:</strong> <span id="transferProductName"></span></p>
      <p><strong>Current Warehouse:</strong> <span id="transferFromWarehouseName"></span></p>

      <label>Target Warehouse:
        <select name="to_warehouse_id" class="input" required>
          <option value="">-- Select Target --</option>
          <?php foreach($warehouses as $w): ?>
            <option value="<?php echo $w['id'] ?>"><?php echo htmlspecialchars($w['code']." - ".$w['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label><br><br>

      <label>Quantity:
        <input type="number" name="quantity" min="1" class="input" required>
      </label><br><br>

      <label>Note:
        <input type="text" name="note" class="input">
      </label><br><br>

      <button class="btn btn-primary" type="submit">Transfer</button>
      <button class="btn" type="button" onclick="closeTransferModal()">Cancel</button>
    </form>
  </div>
</div>

<script>
function openAddModal(){
  document.getElementById('modalTitle').innerText = "Add Item";
  document.getElementById('itemForm').reset();
  document.getElementById('item_id').value = "";
  document.getElementById('itemModal').style.display='flex';
}

function openEditModal(item){
  document.getElementById('modalTitle').innerText = "Edit Item";
  document.getElementById('item_id').value = item.id;
  document.getElementById('item_sku').value = item.sku;
  document.getElementById('item_name').value = item.name;
  document.getElementById('item_category').value = item.category;
  document.getElementById('item_unit').value = item.unit;
  document.getElementById('item_exp').value = item.expiration_date || "";
  document.getElementById('item_min').value = item.min_qty;
  document.getElementById('item_max').value = item.max_qty;
  document.getElementById('item_desc').value = item.description;

  // Preselect warehouse
  const whSelect = document.getElementById('item_warehouse');
  whSelect.value = item.warehouse_id || "";

  document.getElementById('itemModal').style.display='flex';
}

function closeModal(){
  document.getElementById('itemModal').style.display='none';
}

function closeStockInModal() {
  document.getElementById('stockInModal').style.display = 'none';
}

function closeTransferModal() {
  document.getElementById('transferModal').style.display = 'none';
}

window.onclick = function(e) {
  if (e.target === document.getElementById('itemModal')) {
    closeModal();
  }
  if (e.target === document.getElementById('stockInModal')) {
    closeStockInModal();
  }
  if (e.target === document.getElementById('transferModal')) {
    closeTransferModal();
  }
}

function openTransferModal(item) {
  document.getElementById('transferProductId').value = item.id;
  document.getElementById('transferFromWarehouseId').value = item.warehouse_id || '';
  document.getElementById('transferProductName').innerText = item.name;
  document.getElementById('transferFromWarehouseName').innerText = item.warehouse_name || '-';
  document.getElementById('transferModal').style.display = 'flex';
}
</script>
</body>
</html>

<?php if (isset($_GET['status'])): ?>
<script>
  <?php if ($_GET['status'] === 'transfer_success'): ?>
    alert("Transfer completed successfully.");
  <?php elseif ($_GET['status'] === 'transfer_error'): ?>
    alert("Transfer failed. Please check quantity or try again.");
  <?php elseif ($_GET['status'] === 'error_same_warehouse'): ?>
    alert("Cannot transfer to the same warehouse.");
  <?php endif; ?>
</script>
<?php endif; ?>