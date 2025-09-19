<?php
require 'db.php';

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

    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE products SET sku=?,name=?,description=?,category=?,unit=?,expiration_date=?,min_qty=?,max_qty=? WHERE id=?");
        $stmt->execute([$sku,$name,$desc,$category,$unit,$expiration_date,$min_qty,$max_qty,$id]);
    } else {
        // Add new
        $stmt = $pdo->prepare("INSERT INTO products (sku,name,description,category,unit,expiration_date,min_qty,max_qty) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$sku,$name,$desc,$category,$unit,$expiration_date,$min_qty,$max_qty]);
    }
    header("Location: index.php");
    exit;
}

// ---------- Load list ----------
$search = $_GET['q'] ?? '';
$params = [];
$sql = "SELECT p.*, (SELECT SUM(pl.quantity) FROM product_locations pl WHERE pl.product_id = p.id) AS total_qty FROM products p WHERE 1 ";
if ($search) { $sql .= " AND (p.name LIKE :s OR p.sku LIKE :s OR p.category LIKE :s)"; $params[':s']="%$search%"; }
$sql .= " ORDER BY p.id DESC";
$stmt=$pdo->prepare($sql); $stmt->execute($params);
$items=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Inventory - Module 1</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    /* Modal styling */
    .modal {display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;}
    .modal-content {background:#fff;padding:20px;border-radius:8px;max-width:600px;width:100%;position:relative;}
    .modal-header {display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
    .close {cursor:pointer;font-size:20px;}
  </style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>Inventory & Warehouse — Module 1</h1>
    <div>
      <a class="btn" href="locations.php">Manage Locations</a>
      <a class="btn" href="transactions.php">Transactions</a>
      <a class="btn" href="alerts.php">Alerts & Reports</a>
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
        <tr><th>SKU</th><th>Name</th><th>Category</th><th>Unit</th><th>Total Qty</th><th>Min / Max</th><th>Expiration</th><th>Actions</th></tr>
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
            <td class="actions">
              <button class="btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($it)) ?>);return false;">Edit</button>
              <a class="btn" href="stock_transaction.php?action=stock-in&id=<?php echo $it['id'] ?>">Stock-in</a>
              <a class="btn" href="stock_transaction.php?action=stock-out&id=<?php echo $it['id'] ?>">Stock-out</a>
              <a class="btn" href="stock_transaction.php?action=transfer&id=<?php echo $it['id'] ?>">Transfer</a>
              <a class="btn" href="Module1/delete_item.php?id=<?php echo $it['id'] ?>"onclick="return confirm('Delete item?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; if(!count($items)): ?>
          <tr><td colspan="8" class="small">No items found.</td></tr>
        <?php endif; ?>
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
        <div class="col"><label>Expiration Date<input class="input" name="expiration_date" id="item_exp"></label></div>
        <div class="col"><label>Min Qty<input class="input" name="min_qty" id="item_min" type="number" value="0"></label></div>
        <div class="col"><label>Max Qty<input class="input" name="max_qty" id="item_max" type="number" value="0"></label></div>
      </div>
      <div class="form-row">
        <label>Description<textarea class="input" name="description" id="item_desc"></textarea></label>
      </div>
      <div class="form-row">
        <button class="btn btn-primary" type="submit">Save</button>
        <button class="btn" type="button" onclick="closeModal()">Cancel</button>
      </div>
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
  document.getElementById('itemModal').style.display='flex';
}
function closeModal(){
  document.getElementById('itemModal').style.display='none';
}
window.onclick=function(e){
  if(e.target==document.getElementById('itemModal')){closeModal();}
}
</script>
</body>
</html>
