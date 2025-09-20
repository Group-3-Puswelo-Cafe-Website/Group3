<?php
require '../db.php';

// Low stock per product (sum across locations)
$sql = "SELECT p.id, p.sku, p.name, p.min_qty, p.max_qty, IFNULL(SUM(pl.quantity),0) AS total_qty
        FROM products p
        LEFT JOIN product_locations pl ON pl.product_id = p.id
        GROUP BY p.id
        HAVING total_qty <= p.min_qty
        ORDER BY total_qty ASC";
$low = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Expiring soon (within 90 days)
$stmt = $pdo->prepare("SELECT p.id,p.sku,p.name,p.expiration_date,IFNULL(SUM(pl.quantity),0) AS total_qty
    FROM products p
    LEFT JOIN product_locations pl ON pl.product_id = p.id
    WHERE p.expiration_date IS NOT NULL AND p.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
    GROUP BY p.id
    ORDER BY p.expiration_date ASC");
$stmt->execute();
$expiring = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filter = $_GET['filter'] ?? '';
$sql = "SELECT t.*, p.sku, p.name AS product_name,
    lf.code AS from_code, lt.code AS to_code
    FROM stock_transactions t
    JOIN products p ON p.id = t.product_id
    LEFT JOIN locations lf ON lf.id = t.location_from
    LEFT JOIN locations lt ON lt.id = t.location_to
    WHERE 1 ";

$params = [];
if ($filter) {
    $sql .= " AND (t.type = :f OR p.sku LIKE :f2 OR p.name LIKE :f2) ";
    $params[':f'] = $filter;
    $params[':f2'] = "%$filter%";
}
$sql .= " ORDER BY t.trans_date DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html>
    <head><meta charset="utf-8">
    <title>Alerts & Reports</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
  <?php include 'sidebar.php'; ?>
<div class="container">
  <div class="header"><h1>Alerts & Reports</h1><a class="btn" href="index.php">Back</a></div>
  <div class="card">
    <h3>Low Stock (<= Min Quantity)</h3>
    <?php if (count($low) === 0): ?><div class="small">No low stock items.</div><?php else: ?>
      <table class="table"><thead><tr><th>SKU</th><th>Name</th><th>Total Qty</th><th>Min</th><th>Suggested Reorder Qty</th></tr></thead><tbody>
      <?php foreach($low as $l): 
         $suggest = max( ($l['max_qty'] - $l['total_qty']), 0);
      ?>
        <tr>
          <td><?php echo htmlspecialchars($l['sku'])?></td>
          <td><?php echo htmlspecialchars($l['name'])?></td>
          <td><?php echo (int)$l['total_qty']?></td>
          <td><?php echo (int)$l['min_qty']?></td>
          <td><?php echo (int)$suggest?> <span class="small"> (max - current)</span></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>Expiring Soon (next 90 days)</h3>
    <?php if (count($expiring) === 0): ?><div class="small">No items expiring soon.</div><?php else: ?>
      <table class="table">
        <thead><tr><th>SKU</th><th>Name</th><th>Expiry Date</th><th>Qty</th></tr></thead>
        <tbody>
        <?php foreach($expiring as $e): ?>
          <tr>
            <td><?php echo htmlspecialchars($e['sku'])?></td>
            <td><?php echo htmlspecialchars($e['name'])?></td>
            <td><?php echo htmlspecialchars($e['expiration_date'])?></td>
            <td><?php echo (int)$e['total_qty']?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<div class="container">
  <div class="header"><h1>Transactions</h1></div>
  <div class="card">
    <form method="get" class="form-row">
      <input class="input" name="filter" placeholder="Filter by type sku or name" value="<?php echo htmlspecialchars($filter) ?>">
      <button class="btn" type="submit">Filter</button>
    </form>
    <table class="table">
      <thead><tr><th>Date</th><th>Type</th><th>Product</th><th>From</th><th>To</th><th>Qty</th><th>Note</th></tr></thead>
      <tbody>
        <?php foreach($logs as $l): ?>
          <tr>
            <td><?php echo htmlspecialchars($l['trans_date'])?></td>
            <td><?php echo htmlspecialchars($l['type'])?></td>
            <td><?php echo htmlspecialchars($l['sku'].' — '.$l['product_name'])?></td>
            <td><?php echo htmlspecialchars($l['from_code'] ?? '-')?></td>
            <td><?php echo htmlspecialchars($l['to_code'] ?? '-')?></td>
            <td><?php echo (int)$l['qty']?></td>
            <td class="small"><?php echo htmlspecialchars($l['note'])?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>