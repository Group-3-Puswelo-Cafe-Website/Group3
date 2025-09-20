<?php
require '../db.php';

// ---------- Handle Add / Update ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $sku = $_POST['sku'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $category = $_POST['category'];
    $unit = $_POST['unit'] ?: 'pcs';
    $expiration_date = $_POST['expiration_date'] ?: null;
    $min_qty = (int)($_POST['min_qty'] ?? 0);
    $max_qty = (int)($_POST['max_qty'] ?? 0);
    $warehouse_id = $_POST['warehouse_id'] ?? null;

    try {
        if ($id) {
    // Update product including warehouse_id
    $stmt = $pdo->prepare("UPDATE products 
        SET sku=?, name=?, description=?, category=?, unit=?, expiration_date=?, min_qty=?, max_qty=?, warehouse_id=? 
        WHERE id=?");
    $stmt->execute([$sku, $name, $desc, $category, $unit, $expiration_date, $min_qty, $max_qty, $warehouse_id, $id]);

    // Update product_locations table if warehouse selected
    if ($warehouse_id) {
        $pdo->prepare("INSERT INTO product_locations (product_id, location_id, quantity)
                       VALUES (?, ?, 0)
                       ON DUPLICATE KEY UPDATE location_id=VALUES(location_id)")
            ->execute([$id, $warehouse_id]);
    } else {
        // Remove warehouse mapping if deselected
        $pdo->prepare("DELETE FROM product_locations WHERE product_id=?")->execute([$id]);
    }
} else {
    // Add new product including warehouse_id
    $stmt = $pdo->prepare("INSERT INTO products 
        (sku,name,description,category,unit,expiration_date,min_qty,max_qty,warehouse_id) 
        VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$sku, $name, $desc, $category, $unit, $expiration_date, $min_qty, $max_qty, $warehouse_id]);

    $newId = $pdo->lastInsertId();

    // Assign warehouse in product_locations table if selected
    if ($warehouse_id) {
        $pdo->prepare("INSERT INTO product_locations (product_id, location_id, quantity) 
                       VALUES (?, ?, 0)")
            ->execute([$newId, $warehouse_id]);
    }
}


        header("Location: index.php?status=success");
        exit;
    } catch (Exception $e) {
        header("Location: index.php?status=error");
        exit;
    }
}

?>
