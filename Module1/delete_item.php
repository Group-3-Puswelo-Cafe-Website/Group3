<?php
require '../db.php';
require '../shared/config.php';

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header('Location: ' . BASE_URL . 'Module1/index.php');
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Delete from product_locations
    $pdo->prepare("DELETE FROM product_locations WHERE product_id = ?")->execute([$product_id]);
    
    // Delete from stock_transactions
    $pdo->prepare("DELETE FROM stock_transactions WHERE product_id = ?")->execute([$product_id]);
    
    // Delete the product
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$product_id]);
    
    $pdo->commit();
    header('Location: ' . BASE_URL . 'Module1/index.php?status=success');
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ' . BASE_URL . 'Module1/index.php?status=error');
}
exit;
?>