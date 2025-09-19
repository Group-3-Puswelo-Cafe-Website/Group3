<?php
// api/add_item.php
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$sku = trim($input['sku'] ?? '');
$name = trim($input['name'] ?? '');
$desc = $input['description'] ?? '';
$category = $input['category'] ?? '';
$unit = $input['unit'] ?? 'pcs';
$min = intval($input['min_threshold'] ?? 0);
$max = intval($input['max_threshold'] ?? 0);

if (!$sku || !$name) {
  http_response_code(400);
  echo json_encode(['error'=>'sku and name required']);
  exit;
}

try {
  $stmt = $pdo->prepare("INSERT INTO items (sku,name,description,category,unit,min_threshold,max_threshold) VALUES (?,?,?,?,?,?,?)");
  $stmt->execute([$sku,$name,$desc,$category,$unit,$min,$max]);
  echo json_encode(['success'=>true, 'item_id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
