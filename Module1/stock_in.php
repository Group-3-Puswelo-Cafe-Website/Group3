<?php
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$item_id = intval($input['item_id'] ?? 0);
$warehouse_id = intval($input['warehouse_id'] ?? 0);
$quantity = intval($input['quantity'] ?? 0);
$expiry = $input['expiry_date'] ?? null;
$ref = $input['reference'] ?? '';
$user = $input['created_by'] ?? 'system';

if (!$item_id || !$warehouse_id || $quantity <= 0) {
  http_response_code(400); echo json_encode(['error'=>'item_id, warehouse_id and quantity (>0) required']); exit;
}

try {
  $pdo->beginTransaction();

  // insert or update stock record
  $stmt = $pdo->prepare("INSERT INTO item_stocks (item_id, warehouse_id, quantity, expiry_date) VALUES (?,?,?,?)
    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), expiry_date = COALESCE(VALUES(expiry_date), expiry_date)");
  $stmt->execute([$item_id,$warehouse_id,$quantity, $expiry]);

  // log transaction
  $tstmt = $pdo->prepare("INSERT INTO stock_transactions (item_id, warehouse_to, type, quantity, reference, expiry_date, created_by) VALUES (?,?,?,?,?,?,?)");
  $tstmt->execute([$item_id, $warehouse_id, 'stock_in', $quantity, $ref, $expiry, $user]);

  $pdo->commit();
  echo json_encode(['success'=>true]);
} catch (PDOException $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
