<?php
require '../db.php';

header('Content-Type: application/json');

// Handle POST requests for inventory updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        if (isset($input['action'])) {
            switch ($input['action']) {
                case 'receive_from_po':
                    // Receive goods from purchase order
                    $pdo->beginTransaction();
                    
                    // Create goods receipt
                    $receipt_number = 'GR-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                    $stmt = $pdo->prepare("
                        INSERT INTO goods_receipts 
                        (receipt_number, po_id, receipt_date, received_by, location_id, notes)
                        VALUES (?, ?, NOW(), ?, ?, ?)
                    ");
                    $stmt->execute([
                        $receipt_number,
                        $input['po_id'],
                        $input['received_by'] ?? 'API',
                        $input['location_id'],
                        $input['notes'] ?? 'API generated'
                    ]);
                    $gr_id = $pdo->lastInsertId();
                    
                    // Process items
                    foreach ($input['items'] as $item) {
                        // Create goods receipt item
                        $stmt = $pdo->prepare("
                            INSERT INTO goods_receipt_items (gr_id, product_id, quantity, location_id)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$gr_id, $item['product_id'], $item['quantity'], $item['location_id']]);
                        
                        // Update inventory
                        $stmt = $pdo->prepare("
                            INSERT INTO product_locations (product_id, location_id, quantity)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE quantity = quantity + ?
                        ");
                        $stmt->execute([
                            $item['product_id'],
                            $item['location_id'],
                            $item['quantity'],
                            $item['quantity']
                        ]);
                        
                        // Record transaction
                        $stmt = $pdo->prepare("
                            INSERT INTO stock_transactions 
                            (product_id, location_from, location_to, qty, type, reference, note, trans_date, user_name, reference_id, reference_type)
                            VALUES (?, NULL, ?, ?, 'stock-in', ?, ?, NOW(), ?, ?, 'gr')
                        ");
                        $stmt->execute([
                            $item['product_id'],
                            $item['location_id'],
                            $item['quantity'],
                            $receipt_number,
                            "Received from PO via API",
                            $input['received_by'] ?? 'API',
                            $gr_id
                        ]);
                    }
                    
                    $pdo->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'gr_id' => $gr_id,
                        'receipt_number' => $receipt_number,
                        'message' => 'Goods received successfully'
                    ]);
                    break;
                    
                default:
                    throw new Exception('Unknown action');
            }
        } else {
            throw new Exception('Action not specified');
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle GET requests for inventory checks
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (isset($_GET['action']) && $_GET['action'] === 'check_stock') {
            $product_id = $_GET['product_id'];
            $location_id = $_GET['location_id'] ?? null;
            
            $sql = "
                SELECT COALESCE(SUM(pl.quantity), 0) as available_stock
                FROM product_locations pl
                WHERE pl.product_id = ?
            ";
            $params = [$product_id];
            
            if ($location_id) {
                $sql .= " AND pl.location_id = ?";
                $params[] = $location_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'available_stock' => (int)$result['available_stock']
            ]);
        } else {
            throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>