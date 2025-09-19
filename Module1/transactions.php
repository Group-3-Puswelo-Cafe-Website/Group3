<?php
require '../db.php';
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
<!doctype html><html><head><meta charset="utf-8"><title>Transactions</title><link rel="stylesheet" href="../styles.css"></head><body>
  <?php include 'sidebar.php'; ?>
<div class="container">
  <div class="header"><h1>Transactions</h1><div><a class="btn" href="index.php">Back</a></div></div>
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
</body></html>
