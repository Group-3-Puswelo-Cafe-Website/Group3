<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $code = $_POST['code']; 
        $name = $_POST['name']; 
        $desc = $_POST['description'];
        $pdo->prepare("INSERT INTO locations (code,name,description) VALUES (?,?,?)")
            ->execute([$code,$name,$desc]);
    } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $pdo->prepare("DELETE FROM locations WHERE id=?")
            ->execute([(int)$_POST['id']]);
    }
    header('Location: ../locations.php');
    exit;
}

/* Fetch locations with total items and products list */
$sql = "
SELECT l.id, l.code, l.name, l.description,
       COALESCE(SUM(pl.quantity),0) AS total_items
FROM locations l
LEFT JOIN product_locations pl ON pl.location_id = l.id
GROUP BY l.id, l.code, l.name, l.description
ORDER BY l.id
";
$locations = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* Fetch products grouped by warehouse */
$productsStmt = $pdo->query("SELECT p.id, p.name, p.sku, p.warehouse_id 
                             FROM products p ORDER BY p.name");
$allProducts = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

// Organize products by warehouse
$productsByWarehouse = [];
foreach ($allProducts as $p) {
    if ($p['warehouse_id']) {
        $productsByWarehouse[$p['warehouse_id']][] = $p;
    }
}
?>


<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Locations</title>
<link rel="stylesheet" href="sidebar.css">
<link rel="stylesheet" href="../styles.css">
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
.modal-close {
  position: absolute;
  top: 8px;
  right: 10px;
  cursor: pointer;
  font-size: 18px;
  font-weight: bold;
  color: #666;
}
.products-list {
  font-size: 0.9em;
  margin: 5px 0 0 0;
  padding-left: 15px;
}
</style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

<div class="container">
  <div class="header">
    <h1>Locations</h1>
    <div>
      <input id="filterInput" class="input" style="max-width:200px" placeholder="Filter locations...">
      <button class="btn btn-primary" id="openModal">+ Add Location</button>
      <a class="btn" href="index.php">Back</a>
    </div>
  </div>

  <div class="card">
    <h3>Existing Locations</h3>
    <table class="table" id="locationsTable">
      <thead>
        <tr>
          <th>Code</th>
          <th>Name</th>
          <th>Description</th>
          <th>Products in Warehouse</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($locations as $loc): ?>
        <tr>
          <td><?php echo htmlspecialchars($loc['code'])?></td>
          <td><?php echo htmlspecialchars($loc['name'])?></td>
          <td class="small"><?php echo htmlspecialchars($loc['description'])?></td>
          <td>
            <?php 
              if(isset($productsByWarehouse[$loc['id']])) {
                  echo '<ul class="products-list">';
                  foreach($productsByWarehouse[$loc['id']] as $p){
                      echo '<li>'.htmlspecialchars($p['sku'].' - '.$p['name']).'</li>';
                  }
                  echo '</ul>';
              } else {
                  echo '-';
              }
            ?>
          </td>
          <td>
            <form style="display:inline" method="post">
              <input type="hidden" name="id" value="<?php echo $loc['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button class="btn" onclick="return confirm('Delete location?')">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal" id="locationModal">
  <div class="modal-content">
    <span class="modal-close" id="closeModal">&times;</span>
    <h3>Add Location</h3>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <div class="col"><input class="input" name="code" placeholder="Code e.g. WH-A" required></div>
        <div class="col"><input class="input" name="name" placeholder="Name" required></div>
      </div>
      <div class="form-row">
        <div class="col"><textarea class="input" name="description" placeholder="Description"></textarea></div>
      </div>
      <button class="btn btn-primary" type="submit">Save</button>
    </form>
  </div>
</div>

<script>
const modal = document.getElementById("locationModal");
const openBtn = document.getElementById("openModal");
const closeBtn = document.getElementById("closeModal");
openBtn.onclick = () => modal.style.display = "flex";
closeBtn.onclick = () => modal.style.display = "none";
window.onclick = (e) => { if(e.target === modal) modal.style.display = "none"; }

document.getElementById("filterInput").addEventListener("keyup", function() {
  const filter = this.value.toLowerCase();
  const rows = document.querySelectorAll("#locationsTable tbody tr");
  rows.forEach(row => {
    const text = row.innerText.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
});
</script>
</body>
</html>
