<?php
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$item_id = intval($input['item_id'] ?? 0);
$warehouse_id = intval($input['warehouse_id'] ?? 0);
$quantity = intval($input['quantity'] ?? 0);
$ref = $input['reference'] ?? '';
$user = $input['created_by'] ?? 'system';

if (!$item_id || !$warehouse_id || $quantity <= 0) {
  http_response_code(400); echo json_encode(['error'=>'item_id, warehouse_id and quantity (>0) required']); exit;
}

try {
  $pdo->beginTransaction();

  // check current qty
  $stmt = $pdo->prepare("SELECT quantity FROM item_stocks WHERE item_id = ? AND warehouse_id = ? FOR UPDATE");
  $stmt->execute([$item_id,$warehouse_id]);
  $row = $stmt->fetch();
  $current = $row ? intval($row['quantity']) : 0;
  if ($current < $quantity) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error'=>'insufficient stock', 'current'=>$current]);
    exit;
  }

  // update
  $upd = $pdo->prepare("UPDATE item_stocks SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ?");
  $upd->execute([$quantity,$item_id,$warehouse_id]);

  // log
  $tstmt = $pdo->prepare("INSERT INTO stock_transactions (item_id, warehouse_from, type, quantity, reference, created_by) VALUES (?,?,?,?,?,?)");
  $tstmt->execute([$item_id, $warehouse_id, 'stock_out', $quantity, $ref, $user]);

  $pdo->commit();
  echo json_encode(['success'=>true]);
} catch (PDOException $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
