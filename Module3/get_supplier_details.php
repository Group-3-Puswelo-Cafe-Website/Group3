<?php
require '../db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Supplier ID is required']);
    exit;
}

$supplier_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$supplier) {
        echo json_encode(['error' => 'Supplier not found']);
        exit;
    }
    
    echo json_encode(['supplier' => $supplier]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>