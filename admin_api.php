<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';

// Tự động nhận diện tên cột tổng tiền trong bảng orders
$total_col = 'total_price';
try {
    $cols_stmt = $conn->query("DESCRIBE orders");
    $existing_cols = $cols_stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach (['final_total', 'total_price', 'total_amount', 'total'] as $candidate) {
        if (in_array($candidate, $existing_cols)) {
            $total_col = $candidate;
            break;
        }
    }
} catch (Exception $e) {
    $total_col = 'total_price';
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (empty($action) && isset($input['action'])) {
    $action = $input['action'];
}

// 1. LẤY TOÀN BỘ DỮ LIỆU DASHBOARD CHO REACT ADMIN
if ($action === 'get_data' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Thống kê
        $total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
        $pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Đang xử lý'")->fetchColumn() ?: 0;
        $processing_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Đang pha chế'")->fetchColumn() ?: 0;
        $completed_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Đã hoàn thành'")->fetchColumn() ?: 0;
        $total_products = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0;
        $total_revenue = $conn->query("SELECT SUM($total_col) FROM orders WHERE status != 'Đã hủy'")->fetchColumn() ?: 0;
        $total_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;

        // Danh mục
        $categories = $conn->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Món ăn
        $products = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Toppings
        $toppings = $conn->query("SELECT * FROM toppings ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Đơn hàng kèm chi tiết từng ly
        $orders = $conn->query("SELECT * FROM orders ORDER BY id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($orders as &$order) {
            $order['total_amount'] = floatval($order[$total_col] ?? 0);
            try {
                $stmt_it = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $stmt_it->execute([$order['id']]);
                $order['items'] = $stmt_it->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Exception $ex) {
                $order['items'] = [];
            }
        }
        unset($order);

        // Khách hàng
        $users = $conn->query("SELECT id, name, phone, address, created_at FROM users ORDER BY id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        echo json_encode([
            "status" => "success",
            "stats" => [
                "total_orders" => intval($total_orders),
                "pending_orders" => intval($pending_orders),
                "processing_orders" => intval($processing_orders),
                "completed_orders" => intval($completed_orders),
                "total_products" => intval($total_products),
                "total_revenue" => floatval($total_revenue),
                "total_users" => intval($total_users),
            ],
            "categories" => $categories,
            "products" => $products,
            "toppings" => $toppings,
            "orders" => $orders,
            "users" => $users
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 2. CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
if ($action === 'update_order_status') {
    $order_id = intval($input['order_id'] ?? 0);
    $new_status = trim($input['status'] ?? '');

    if ($order_id > 0 && !empty($new_status)) {
        try {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            echo json_encode(["status" => "success", "message" => "Đã cập nhật trạng thái đơn #$order_id thành '$new_status'!"]);
            exit;
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Thiếu mã đơn hoặc trạng thái"]);
        exit;
    }
}

// 3. THÊM MÓN MỚI VÀO THỰC ĐƠN
if ($action === 'add_product') {
    $category_id = intval($input['category_id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $price = floatval($input['price'] ?? 0);
    $image_url = trim($input['image_url'] ?? '');
    $description = trim($input['description'] ?? '');

    if (!empty($name) && $price > 0) {
        try {
            $cols_stmt = $conn->query("DESCRIBE products");
            $prod_cols = $cols_stmt->fetchAll(PDO::FETCH_COLUMN);

            $price_col = 'base_price';
            foreach (['base_price', 'price', 'product_price', 'gia'] as $p_cand) {
                if (in_array($p_cand, $prod_cols)) { $price_col = $p_cand; break; }
            }

            $img_col = 'image_url';
            foreach (['image_url', 'image', 'img', 'thumbnail'] as $i_cand) {
                if (in_array($i_cand, $prod_cols)) { $img_col = $i_cand; break; }
            }

            $has_desc = in_array('description', $prod_cols);

            if ($has_desc) {
                $sql = "INSERT INTO products (category_id, name, {$price_col}, {$img_col}, description) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$category_id, $name, $price, $image_url, $description]);
            } else {
                $sql = "INSERT INTO products (category_id, name, {$price_col}, {$img_col}) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$category_id, $name, $price, $image_url]);
            }
            echo json_encode(["status" => "success", "message" => "Đã thêm món '$name' vào thực đơn thành công!"]);
            exit;
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Tên món hoặc giá tiền không hợp lệ!"]);
        exit;
    }
}

// 4. XÓA MÓN
if ($action === 'delete_product') {
    $del_id = intval($input['product_id'] ?? 0);
    if ($del_id > 0) {
        try {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$del_id]);
            echo json_encode(["status" => "success", "message" => "Đã xóa món #$del_id thành công!"]);
            exit;
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit;
        }
    }
}

// 5. THÊM TOPPING
if ($action === 'add_topping') {
    $name = trim($input['name'] ?? '');
    $price = floatval($input['price'] ?? 0);
    if (!empty($name) && $price >= 0) {
        try {
            $stmt = $conn->prepare("INSERT INTO toppings (name, price) VALUES (?, ?)");
            $stmt->execute([$name, $price]);
            echo json_encode(["status" => "success", "message" => "Đã thêm topping '$name' thành công!"]);
            exit;
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit;
        }
    }
}

// Mặc định
echo json_encode(["status" => "error", "message" => "Hành động không hợp lệ!"]);
