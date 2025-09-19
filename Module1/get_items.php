<?php
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';

$q = $_GET['q'] ?? '';
$qparam = "%$q%";

$sql = "SELECT i.*, 
  IFNULL(SUM(isr.quantity),0) AS total_quantity
  FROM items i
  LEFT JOIN item_stocks isr ON isr.item_id = i.id
  WHERE i.name LIKE ? OR i.sku LIKE ? OR i.category LIKE ?
  GROUP BY i.id
  ORDER BY i.name ASC
  LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute([$qparam,$qparam,$qparam]);
$items = $stmt->fetchAll();

echo json_encode(['items'=>$items]);
