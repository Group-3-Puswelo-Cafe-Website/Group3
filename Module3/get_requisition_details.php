<?php
require '../db.php';
require '../shared/config.php';

header('Content-Type: application/json');

$requisition_id = $_GET['id'] ?? null;
if (!$requisition_id) {
    echo json_encode(['error' => 'Missing requisition ID']);
    exit;
}

// Get requisition details
$stmt = $pdo->prepare("
    SELECT pr.* 
    FROM purchase_requisitions pr 
    WHERE pr.id = ?
");
$stmt->execute([$requisition_id]);
$requisition = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$requisition) {
    echo json_encode(['error' => 'Requisition not found']);
    exit;
}

// Get requisition items with product details
$stmt = $pdo->prepare("
    SELECT pri.*, p.name as product_name, p.unit
    FROM purchase_requisition_items pri
    LEFT JOIN products p ON pri.product_id = p.id
    WHERE pri.requisition_id = ?
");
$stmt->execute([$requisition_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'requisition' => $requisition,
    'items' => $items
]);