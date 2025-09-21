<?php
require '../db.php';
require '../shared/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $from_id = $_POST['from_warehouse_id'];
    $to_id = $_POST['to_warehouse_id'];
    $qty = (int)$_POST['quantity'];
    $note = $_POST['note'] ?? '';

    // Validate inputs
    if (!$product_id || !$from_id || !$to_id || $qty <= 0) {
        header("Location: " . BASE_URL . "Module1/index.php?status=transfer_error");
        exit;
    }

    if ($from_id == $to_id) {
        header("Location: " . BASE_URL . "Module1/index.php?status=error_same_warehouse");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Check if source warehouse has enough stock
        $stmt = $pdo->prepare("SELECT quantity FROM product_locations 
                               WHERE product_id = ? AND location_id = ?");
        $stmt->execute([$product_id, $from_id]);
        $source_stock = $stmt->fetchColumn();

        if ($source_stock === false || $source_stock < $qty) {
            throw new Exception("Not enough stock to transfer. Available: " . ($source_stock === false ? 0 : $source_stock));
        }

        // Deduct from source warehouse
        $stmt = $pdo->prepare("UPDATE product_locations SET quantity = quantity - ? 
                               WHERE product_id = ? AND location_id = ?");
        $stmt->execute([$qty, $product_id, $from_id]);

        // Add to destination warehouse
        $pdo->prepare("INSERT INTO product_locations (product_id, location_id, quantity)
                       VALUES (?, ?, ?) 
                       ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)")
            ->execute([$product_id, $to_id, $qty]);

        // Insert into transactions log
        $pdo->prepare("INSERT INTO stock_transactions 
            (product_id, type, location_from, location_to, qty, note, trans_date)
            VALUES (?, 'TRANSFER', ?, ?, ?, ?, NOW())")
            ->execute([$product_id, $from_id, $to_id, $qty, $note]);

        $pdo->commit();
        header("Location: " . BASE_URL . "Module1/index.php?status=transfer_success");
    } catch (Exception $e) {
        $pdo->rollBack();
        // For debugging - show error
        echo "<div style='color:red; padding:20px; border:1px solid red;'>";
        echo "<h3>Transfer Error:</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<p><a href='" . BASE_URL . "Module1/index.php'>Back to Inventory</a></p>";
        echo "</div>";
        exit;
        
        // Comment out the redirect for debugging
        // header("Location: " . BASE_URL . "Module1/index.php?status=transfer_error");
    }
    exit;
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transfer Error</title>
    <link rel="stylesheet" href="styles.css">
    <base href="<?php echo BASE_URL; ?>">
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>
    <div class="container" style="margin-left: 18rem;">
        <h1>Transfer Error</h1>
        <p>An error occurred during the transfer. Please try again.</p>
        <a class="btn" href="<?php echo BASE_URL; ?>Module1/index.php">Back to Inventory</a>
    </div>
</body>
</html>