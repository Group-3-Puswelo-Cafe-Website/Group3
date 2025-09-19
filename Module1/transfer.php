<?php
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$item_id = intval($input['item_id'] ?? 0);
$from = intval($input['warehouse_from'] ?? 0);
$to = intval($input['warehouse_to'] ?? 0);
$quantity = intval($input['quantity'] ?? 0);
$user = $input['created_by'] ?? 'system';

if (!$item_id || !$from || !$to || $quantity <= 0) {
  http_response_code(400); echo json_encode(['error'=>'item_id, warehouse_from, warehouse_to, quantity required']); exit;
}
if ($from === $to) { http_response_code(400); echo json_encode(['error'=>'from and to cannot be same']); exit; }

try {
  $pdo->beginTransaction();

  // check from qty
  $stmt = $pdo->prepare("SELECT quantity FROM item_stocks WHERE item_id = ? AND warehouse_id = ? FOR UPDATE");
  $stmt->execute([$item_id,$from]);
  $row = $stmt->fetch();
  $current = $row ? intval($row['quantity']) : 0;
  if ($current < $quantity) { $pdo->rollBack(); http_response_code(400); echo json_encode(['error'=>'insufficient stock']); exit; }

  // deduct from
  $pdo->prepare("UPDATE item_stocks SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ?")->execute([$quantity,$item_id,$from]);

  // add to (insert or update)
  $pdo->prepare("INSERT INTO item_stocks (item_id, warehouse_id, quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)")
    ->execute([$item_id,$to,$quantity]);

  // log transfer
  $pdo->prepare("INSERT INTO stock_transactions (item_id, warehouse_from, warehouse_to, type, quantity, created_by) VALUES (?,?,?,?,?,?)")
    ->execute([$item_id,$from,$to,'transfer',$quantity,$user]);

  $pdo->commit();
  echo json_encode(['success'=>true]);
} catch (PDOException $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
