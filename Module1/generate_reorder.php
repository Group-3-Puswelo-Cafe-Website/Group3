<?php
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';

try {
  $pdo->beginTransaction();
  $sql = "SELECT i.id as item_id, IFNULL(SUM(isk.quantity),0) as total_qty, i.min_threshold
          FROM items i
          LEFT JOIN item_stocks isk ON isk.item_id = i.id
          GROUP BY i.id HAVING total_qty <= i.min_threshold";
  $rows = $pdo->query($sql)->fetchAll();

  $inserted = [];
  foreach ($rows as $r) {
    // naive reorder qty = max_threshold - total_qty if max > min
    $item_id = $r['item_id'];
    $stmt2 = $pdo->prepare("SELECT max_threshold FROM items WHERE id = ?");
    $stmt2->execute([$item_id]);
    $max = intval($stmt2->fetchColumn() ?? 0);
    $qty = max(1, $max - intval($r['total_qty']));
    // create reorder for default warehouse (choose first warehouse) - or iterate per warehouse for finer control
    $wh = $pdo->query("SELECT id FROM warehouses ORDER BY id LIMIT 1")->fetchColumn();
    if ($wh) {
      $ins = $pdo->prepare("INSERT INTO reorder_requests (item_id, warehouse_id, quantity) VALUES (?,?,?)");
      $ins->execute([$item_id, $wh, $qty]);
      $inserted[] = ['item_id'=>$item_id,'qty'=>$qty,'warehouse'=>$wh];
    }
  }
  $pdo->commit();
  echo json_encode(['created' => $inserted]);
} catch (PDOException $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
