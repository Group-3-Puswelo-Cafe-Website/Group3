<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $from_id = $_POST['from_warehouse_id'];
    $to_id = $_POST['to_warehouse_id'];
    $qty = (int)$_POST['quantity'];
    $note = $_POST['note'] ?? '';

    if ($from_id == $to_id) {
        header("Location: index.php?status=error_same_warehouse");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Deduct from source warehouse
        $stmt = $pdo->prepare("UPDATE product_locations SET quantity = quantity - ? 
                               WHERE product_id = ? AND location_id = ? AND quantity >= ?");
        $stmt->execute([$qty, $product_id, $from_id, $qty]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Not enough stock to transfer.");
        }

        // Add to destination warehouse
        $pdo->prepare("INSERT INTO product_locations (product_id, location_id, quantity)
                       VALUES (?, ?, ?) 
                       ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)")
            ->execute([$product_id, $to_id, $qty]);

        // Insert into transactions log
        $pdo->prepare("INSERT INTO stock_transactions 
            (product_id, type, location_from, location_to, qty, note, trans_date)
            VALUES (?, 'transfer', ?, ?, ?, ?, NOW())")
            ->execute([$product_id, $from_id, $to_id, $qty, $note]);

        $pdo->commit();
        header("Location: index.php?status=transfer_success");
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: index.php?status=transfer_error");
    }
    exit;
}
