<?php
header('Content-Type: application/json');
require_once __DIR__.'/../db.php';

$item_id = intval($_GET['item_id'] ?? 0);
$limit = intval($_GET['limit'] ?? 200);

$sql = "SELECT st.*, i.name as item_name, wf.name as from_name, wt.name as to_name
        FROM stock_transactions st
        LEFT JOIN items i ON i.id = st.item_id
        LEFT JOIN warehouses wf ON wf.id = st.warehouse_from
        LEFT JOIN warehouses wt ON wt.id = st.warehouse_to
        ".($item_id ? " WHERE st.item_id = ".intval($item_id) : "")."
        ORDER BY st.created_at DESC
        LIMIT ".intval($limit);

$stmt = $pdo->query($sql);
echo json_encode(['transactions' => $stmt->fetchAll()]);
