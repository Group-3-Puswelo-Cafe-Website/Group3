<?php
require '../db.php';
// ---------- Load list ----------
$search = $_GET['q'] ?? '';
$params = [];
$sql = "SELECT p.*, 
        l.name AS warehouse_name,
        COALESCE((SELECT SUM(pl.quantity) FROM product_locations pl WHERE pl.product_id = p.id),0) AS total_qty
        FROM products p
        LEFT JOIN locations l ON p.warehouse_id = l.id
        WHERE 1";

if ($search) { 
    $sql .= " AND (p.name LIKE :s OR p.sku LIKE :s OR p.category LIKE :s)"; 
    $params[':s']="%$search%"; 
}

$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql); 
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- Load Warehouses ----------
$warehouses = $pdo->query("SELECT id, code, name FROM locations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>