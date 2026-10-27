<?php
require_once 'db.php';
header('Content-Type: text/html; charset=UTF-8');

$message = '';
$error = '';

// Tự động nhận diện tên cột tổng tiền trong bảng orders (total_price, total_amount, final_total, total)
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

// 1. Xử lý cập nhật trạng thái đơn hàng
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['status']);
    try {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        $message = "Đã cập nhật trạng thái đơn #$order_id thành '$new_status'!";
    } catch (Exception $e) {
        $error = "Lỗi cập nhật: " . $e->getMessage();
    }
}

// 2. Xử lý thêm món mới vào menu (Tự động nhận diện cột giá, ảnh và mô tả)
if (isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $category_id = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!empty($name) && $price > 0) {
        try {
            // Tự động kiểm tra danh sách cột thực tế của bảng products
            $cols_stmt = $conn->query("DESCRIBE products");
            $prod_cols = $cols_stmt->fetchAll(PDO::FETCH_COLUMN);

            // 1. Tự động tìm tên cột giá: base_price hay price
            $price_col = 'base_price';
            foreach (['base_price', 'price', 'product_price', 'gia'] as $p_cand) {
                if (in_array($p_cand, $prod_cols)) {
                    $price_col = $p_cand;
                    break;
                }
            }

            // 2. Tự động tìm tên cột ảnh: image_url hay image hay img
            $img_col = 'image_url';
            foreach (['image_url', 'image', 'img', 'thumbnail'] as $i_cand) {
                if (in_array($i_cand, $prod_cols)) {
                    $img_col = $i_cand;
                    break;
                }
            }

            // 3. Kiểm tra xem bảng products có cột description không
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

            $message = "🎉 Đã thêm món '$name' vào thực đơn thành công!";
        } catch (Exception $e) {
            $error = "Lỗi thêm món: " . $e->getMessage();
        }
    } else {
        $error = "Vui lòng nhập tên món và giá tiền hợp lệ!";
    }
}

// 3. Xử lý thêm Topping mới
if (isset($_POST['action']) && $_POST['action'] === 'add_topping') {
    $name = trim($_POST['topping_name']);
    $price = floatval($_POST['topping_price']);

    if (!empty($name) && $price >= 0) {
        try {
            $stmt = $conn->prepare("INSERT INTO toppings (name, price) VALUES (?, ?)");
            $stmt->execute([$name, $price]);
            $message = "Đã thêm topping '$name' thành công!";
        } catch (Exception $e) {
            $error = "Lỗi thêm topping: " . $e->getMessage();
        }
    } else {
        $error = "Vui lòng nhập tên và giá topping hợp lệ!";
    }
}

// 4. Lấy dữ liệu thống kê
$total_orders = 0;
$pending_orders = 0;
$total_products = 0;
$total_revenue = 0;

try {
    $total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
    $pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Đang xử lý'")->fetchColumn() ?: 0;
    $total_products = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0;
    $total_revenue = $conn->query("SELECT SUM($total_col) FROM orders WHERE status != 'Đã hủy'")->fetchColumn() ?: 0;
} catch (Exception $e) {
    // Dự phòng an toàn nếu bảng còn thiếu cột
}

// Lấy danh mục đồ uống
$categories = [];
try {
    $categories = $conn->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Lấy danh sách 30 đơn hàng mới nhất
$orders = [];
try {
    $orders = $conn->query("SELECT * FROM orders ORDER BY id DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trà Sữa Tea & Coffee - Quản Trị Hệ Thống</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .stat-card { border-radius: 12px; border: none; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .badge-status { font-size: 0.85rem; padding: 6px 12px; border-radius: 20px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
                <h2 class="h3 fw-bold text-dark"><i class="bi bi-cup-hot-fill text-danger me-2"></i>Bảng Điều Khiển Quản Trị Trà Sữa</h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="badge bg-success py-2 px-3"><i class="bi bi-broadcast me-1"></i> Máy chủ: Online (Port 8080)</span>
                </div>
            </div>

            <!-- Thông báo -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Thẻ Thống Kê Nhanh -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm bg-primary text-white p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-white-50">Tổng Đơn Hàng</h6>
                                <h3 class="fw-bold mb-0"><?= number_format($total_orders) ?></h3>
                            </div>
                            <i class="bi bi-receipt fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm bg-warning text-dark p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted">Đang Chờ Xử Lý</h6>
                                <h3 class="fw-bold mb-0"><?= number_format($pending_orders) ?></h3>
                            </div>
                            <i class="bi bi-hourglass-split fs-1 text-black-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm bg-success text-white p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-white-50">Doanh Thu Ghi Nhận</h6>
                                <h3 class="fw-bold mb-0"><?= number_format($total_revenue) ?> đ</h3>
                            </div>
                            <i class="bi bi-cash-stack fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm bg-info text-white p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-white-50">Món Trong Thực Đơn</h6>
                                <h3 class="fw-bold mb-0"><?= number_format($total_products) ?> món</h3>
                            </div>
                            <i class="bi bi-cup-straw fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Thêm Món & Thêm Topping -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3 fw-bold text-primary">
                            <i class="bi bi-plus-circle-fill me-2"></i>Thêm Món Mới Vào Menu App
                        </div>
                        <div class="card-body">
                            <form method="POST" action="admin.php">
                                <input type="hidden" name="action" value="add_product">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Danh mục</label>
                                        <select name="category_id" class="form-select" required>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-semibold">Tên món đồ uống</label>
                                        <input type="text" name="name" class="form-control" placeholder="VD: Trà Xoài Nhiệt Đới" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Giá bán (VNĐ)</label>
                                        <input type="number" name="price" class="form-control" placeholder="VD: 35000" step="1000" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Đường dẫn ảnh (URL)</label>
                                        <input type="url" name="image_url" class="form-control" placeholder="https://images.unsplash.com/..." required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Mô tả ngắn</label>
                                        <input type="text" name="description" class="form-control" placeholder="Mô tả hương vị món...">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3 px-4">
                                    <i class="bi bi-save me-1"></i> Lưu Vào Menu Ngay
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3 fw-bold text-success">
                            <i class="bi bi-plus-circle-fill me-2"></i>Thêm Topping Mới
                        </div>
                        <div class="card-body">
                            <form method="POST" action="admin.php">
                                <input type="hidden" name="action" value="add_topping">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Tên Topping</label>
                                    <input type="text" name="topping_name" class="form-control" placeholder="VD: Thạch Hạt Lựu" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Giá phụ thu (VNĐ)</label>
                                    <input type="number" name="topping_price" class="form-control" placeholder="VD: 7000" step="1000" required>
                                </div>
                                <button type="submit" class="btn btn-success w-100 mt-2">
                                    <i class="bi bi-save me-1"></i> Thêm Topping
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bảng Đơn Hàng -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5 text-dark"><i class="bi bi-clock-history me-2"></i>Danh Sách Đơn Hàng Từ App Mobile</span>
                    <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
                        <i class="bi bi-arrow-clockwise me-1"></i> Tải lại danh sách
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Mã đơn</th>
                                    <th>Khách hàng & SĐT</th>
                                    <th>Địa chỉ nhận</th>
                                    <th>Chi tiết các ly đã đặt</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái hiện tại</th>
                                    <th class="text-center">Thao tác đổi trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Chưa có đơn hàng nào được gửi lên từ App.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <?php
                                            $order_amount = $order[$total_col] ?? 0;

                                            // Lấy chi tiết món
                                            $items = [];
                                            try {
                                                $stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
                                                $stmt_items->execute([$order['id']]);
                                                $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
                                            } catch (Exception $e) {}

                                            $badge_class = 'bg-secondary';
                                            if ($order['status'] === 'Đang xử lý') $badge_class = 'bg-warning text-dark';
                                            elseif ($order['status'] === 'Đang pha chế') $badge_class = 'bg-info text-dark';
                                            elseif ($order['status'] === 'Đang giao') $badge_class = 'bg-primary';
                                            elseif ($order['status'] === 'Đã hoàn thành') $badge_class = 'bg-success';
                                            elseif ($order['status'] === 'Đã hủy') $badge_class = 'bg-danger';
                                        ?>
                                        <tr>
                                            <td class="ps-3 fw-bold text-danger">#<?= $order['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($order['customer_name'] ?? 'Khách vãng lai') ?></strong><br>
                                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['customer_phone'] ?? '') ?></small>
                                            </td>
                                            <td style="max-width: 180px;">
                                                <small class="text-truncate d-block" title="<?= htmlspecialchars($order['delivery_address'] ?? '') ?>">
                                                    <?= htmlspecialchars($order['delivery_address'] ?? 'Tại quán') ?>
                                                </small>
                                            </td>
                                            <td style="font-size: 0.9rem;">
                                                <?php foreach ($items as $it): ?>
                                                    <div>
                                                        <strong><?= htmlspecialchars($it['product_name'] ?? '') ?></strong> 
                                                        <span class="text-danger">x<?= $it['quantity'] ?? 1 ?></span>
                                                        <span class="badge bg-light text-dark border"><?= number_format($it['subtotal'] ?? 0) ?>đ</span>
                                                        <br>
                                                        <small class="text-muted fst-italic">↳ <?= htmlspecialchars($it['toppings_text'] ?? 'Mặc định') ?></small>
                                                    </div>
                                                <?php endforeach; ?>
                                            </td>
                                            <td class="fw-bold text-success fs-6">
                                                <?= number_format($order_amount) ?> đ
                                            </td>
                                            <td>
                                                <span class="badge <?= $badge_class ?> badge-status">
                                                    <?= htmlspecialchars($order['status'] ?? 'Đang xử lý') ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <form method="POST" action="admin.php" class="d-inline-flex gap-1">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <select name="status" class="form-select form-select-sm" style="width: 130px;">
                                                        <option value="Đang xử lý" <?= ($order['status'] ?? '') === 'Đang xử lý' ? 'selected' : '' ?>>Đang xử lý</option>
                                                        <option value="Đang pha chế" <?= ($order['status'] ?? '') === 'Đang pha chế' ? 'selected' : '' ?>>Đang pha chế</option>
                                                        <option value="Đang giao" <?= ($order['status'] ?? '') === 'Đang giao' ? 'selected' : '' ?>>Đang giao</option>
                                                        <option value="Đã hoàn thành" <?= ($order['status'] ?? '') === 'Đã hoàn thành' ? 'selected' : '' ?>>Đã hoàn thành</option>
                                                        <option value="Đã hủy" <?= ($order['status'] ?? '') === 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-dark" title="Cập nhật">
                                                        <i class="bi bi-check2"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>