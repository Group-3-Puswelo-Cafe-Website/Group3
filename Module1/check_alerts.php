<?php
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';

// Find items where total qty <= min_threshold and positive flag
$sql = "SELECT i.id, i.sku, i.name, i.min_threshold, IFNULL(SUM(isk.quantity),0) as total_qty
        FROM items i
        LEFT JOIN item_stocks isk ON isk.item_id = i.id
        GROUP BY i.id
        HAVING total_qty <= i.min_threshold";

$stmt = $pdo->query($sql);
$alerts = $stmt->fetchAll();

echo json_encode(['alerts'=>$alerts]);
