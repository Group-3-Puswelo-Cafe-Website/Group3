<?php
require '../db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Purchase Order ID is required']);
    exit;
}

$po_id = $_GET['id'];

try {
    // Get purchase order details
    $stmt = $pdo->prepare("
        SELECT po.*, s.name as supplier_name
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.id
        WHERE po.id = ?
    ");
    $stmt->execute([$po_id]);
    $purchase_order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$purchase_order) {
        echo json_encode(['error' => 'Purchase order not found']);
        exit;
    }
    
    // Get purchase order items
    $stmt = $pdo->prepare("
        SELECT poi.*, p.name as product_name
        FROM purchase_order_items poi
        LEFT JOIN products p ON poi.product_id = p.id
        WHERE poi.po_id = ?
    ");
    $stmt->execute([$po_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'purchase_order' => $purchase_order,
        'items' => $items
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>