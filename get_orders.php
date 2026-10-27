<?php
require_once "db.php";

$phone = isset($_GET['phone']) ? $_GET['phone'] : '';

try {
    if (!empty($phone)) {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE customer_phone = ? ORDER BY created_at DESC");
        $stmt->execute([$phone]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM orders ORDER BY created_at DESC");
        $stmt->execute();
    }
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy kèm chi tiết các món trong mỗi đơn hàng
    foreach ($orders as &$order) {
        $stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt_items->execute([$order['id']]);
        $order['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(["status" => "success", "data" => $orders]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}