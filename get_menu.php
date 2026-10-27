<?php
require_once "db.php";

try {
    // 1. Lấy danh mục
    $stmt_cat = $conn->prepare("SELECT * FROM categories");
    $stmt_cat->execute();
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    // 2. Lấy danh sách sản phẩm
    $stmt_prod = $conn->prepare("SELECT * FROM products");
    $stmt_prod->execute();
    $products = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

    // 3. Lấy danh sách topping
    $stmt_top = $conn->prepare("SELECT * FROM toppings");
    $stmt_top->execute();
    $toppings = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => [
            "categories" => $categories,
            "products" => $products,
            "toppings" => $toppings
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}