<?php
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';
$id = intval($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
try {
  $pdo->prepare("DELETE FROM items WHERE id = ?")->execute([$id]);
  echo json_encode(['success'=>true]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
