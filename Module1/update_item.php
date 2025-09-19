<?php
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$id = intval($input['id'] ?? 0);
if (!$id) { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }

$fields = ['sku','name','description','category','unit','min_threshold','max_threshold'];
$sets = [];
$params = [];
foreach ($fields as $f) {
  if (isset($input[$f])) { $sets[] = "$f = ?"; $params[] = $input[$f]; }
}
if (empty($sets)) { echo json_encode(['success'=>false,'message'=>'nothing to update']); exit; }

$params[] = $id;
$sql = "UPDATE items SET ".implode(', ', $sets)." WHERE id = ?";
try {
  $pdo->prepare($sql)->execute($params);
  echo json_encode(['success'=>true]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
