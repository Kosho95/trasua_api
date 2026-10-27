<?php
require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['customer_name']) || !isset($data['cart']) || empty($data['cart'])) {
    echo json_encode(["status" => "error", "message" => "Dữ liệu đơn hàng không hợp lệ."]);
    exit();
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_phone, delivery_address, note, total_amount, discount) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['customer_name'],
        $data['customer_phone'],
        $data['delivery_address'],
        isset($data['note']) ? $data['note'] : '',
        $data['total_amount'],
        isset($data['discount']) ? $data['discount'] : 0
    ]);
    $order_id = $conn->lastInsertId();

    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_name, size, toppings_text, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");

    foreach ($data['cart'] as $item) {
        // Gộp Topping, Mức đường, Mức đá vào chung 1 chuỗi để lưu vào CSDL
        $options_info = [];
        if (!empty($item['sugar'])) $options_info[] = "Đường: " . $item['sugar'];
        if (!empty($item['ice'])) $options_info[] = "Đá: " . $item['ice'];
        if (!empty($item['toppings'])) $options_info[] = "Topping: " . implode(", ", $item['toppings']);
        if (!empty($item['item_note'])) $options_info[] = "Ghi chú: " . $item['item_note'];

        $toppings_str = !empty($options_info) ? implode(" | ", $options_info) : "Mặc định";

        $stmt_item->execute([
            $order_id,
            $item['name'],
            $item['size'],
            $toppings_str,
            $item['quantity'],
            $item['unit_price'],
            $item['subtotal']
        ]);
    }

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Đặt hàng thành công!", "order_id" => $order_id]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}