<?php
session_start();
require_once 'db.php';
header('Content-Type: text/html; charset=UTF-8');

// Xử lý Đăng xuất Admin
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_username']);
    header('Location: admin.php');
    exit;
}

$login_error = '';

// Xử lý Đăng nhập Admin
if (isset($_POST['action']) && $_POST['action'] === 'admin_login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $is_authenticated = false;
    $admin_display_name = 'Quản Trị Viên';

    // 1. Kiểm tra tài khoản admin mặc định thuận tiện demo đồ án
    if (($username === 'admin' && in_array($password, ['123456', 'admin123', 'admin'])) ||
        ($username === 'quanly' && in_array($password, ['123456', 'admin123']))) {
        $is_authenticated = true;
        $admin_display_name = ($username === 'admin') ? 'Quản Trị Viên (Admin)' : 'Quản Lý Cửa Hàng';
    } else {
        // 2. Kiểm tra trong database (bảng users)
        try {
            $stmt = $conn->prepare('SELECT * FROM users WHERE (phone = ? OR name = ?) LIMIT 1');
            $stmt->execute([$username, $username]);
            $user_row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user_row && isset($user_row['password'])) {
                if (password_verify($password, $user_row['password']) || $password === $user_row['password']) {
                    $is_authenticated = true;
                    $admin_display_name = $user_row['name'];
                }
            }
        } catch (Exception $e) {}
    }

    if ($is_authenticated) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin_display_name;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Tài khoản hoặc mật khẩu quản trị không chính xác!';
    }
}

// NẾU CHƯA ĐĂNG NHẬP ADMIN -> HIỂN THỊ GIAO DIỆN ĐĂNG NHẬP SANG TRỌNG
if (empty($_SESSION['admin_logged_in'])) {
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Quản Trị - Cozy Milk Tea</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #3E2723 0%, #4E342E 50%, #271612 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px;
            margin: 0;
        }
        .login-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);
            overflow: hidden;
            width: 100%;
            max-width: 440px;
            transition: transform 0.3s ease;
        }
        .login-card:hover { transform: translateY(-4px); }
        .login-header {
            background: linear-gradient(135deg, #d35400 0%, #e67e22 100%);
            color: #fff;
            padding: 34px 24px;
            text-align: center;
        }
        .login-header .icon-brand {
            font-size: 52px;
            display: inline-block;
            margin-bottom: 6px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        .login-body { padding: 32px 28px; }
        .form-control {
            border-radius: 12px;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid #e0e0e0;
            font-size: 14px;
            transition: all 0.25s ease;
        }
        .form-control:focus {
            border-color: #d35400;
            box-shadow: 0 0 0 4px rgba(211, 84, 0, 0.15);
        }
        .input-group-custom { position: relative; margin-bottom: 18px; }
        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 18px;
            pointer-events: none;
            z-index: 5;
        }
        .btn-login {
            background: linear-gradient(135deg, #d35400 0%, #e67e22 100%);
            border: none;
            color: white;
            font-weight: 700;
            padding: 13px;
            border-radius: 12px;
            width: 100%;
            font-size: 15px;
            box-shadow: 0 6px 18px rgba(211, 84, 0, 0.35);
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(211, 84, 0, 0.45);
            color: #fff;
        }
        .quick-hint {
            background: #fff8f0;
            border: 1px dashed #f5cba7;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 12.5px;
            color: #7e5109;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="icon-brand">🧋</div>
        <h3 class="fw-bold mb-1">Cổng Quản Trị Hệ Thống</h3>
        <p class="mb-0 small opacity-75">Cozy Milk Tea • Trang Dành Riêng Cho Quản Lý & Admin</p>
    </div>
    <div class="login-body">
        <?php if (!empty($login_error)): ?>
            <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                <div><?= htmlspecialchars($login_error) ?></div>
            </div>
        <?php endif; ?>
        <form method="POST" action="admin.php">
            <input type="hidden" name="action" value="admin_login">
            <div class="input-group-custom">
                <i class="bi bi-person-fill input-icon"></i>
                <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập admin" required autofocus value="admin">
            </div>
            <div class="input-group-custom">
                <i class="bi bi-shield-lock-fill input-icon"></i>
                <input type="password" name="password" class="form-control" placeholder="Mật khẩu quản trị" required value="123456">
            </div>
            <button type="submit" class="btn btn-login">
                <i class="bi bi-box-arrow-in-right me-1"></i> Đăng Nhập Quản Trị
            </button>
        </form>
        <div class="quick-hint">
            <div class="fw-bold mb-1"><i class="bi bi-info-circle-fill text-warning me-1"></i> Tài khoản quản trị mặc định:</div>
            <div>• Tài khoản: <strong>admin</strong> | Mật khẩu: <strong>123456</strong> (hoặc <strong>admin123</strong>)</div>
            <div>• Quản lý: <strong>quanly</strong> | Mật khẩu: <strong>123456</strong></div>
        </div>
    </div>
</div>
</body>
</html>
<?php
    exit;
}

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

// 1. Cập nhật trạng thái đơn hàng
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

// 2. Thêm món mới vào menu
if (isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $category_id = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');
    $description = trim($_POST['description'] ?? '');

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
            $message = "🎉 Đã thêm món '$name' vào thực đơn thành công!";
        } catch (Exception $e) {
            $error = "Lỗi thêm món: " . $e->getMessage();
        }
    } else {
        $error = "Vui lòng nhập tên món và giá tiền hợp lệ!";
    }
}

// 3. Thêm Topping mới
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

// 4. Xóa món đồ uống khỏi menu
if (isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    $del_id = intval($_POST['product_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$del_id]);
        $message = "Đã xóa món #$del_id khỏi thực đơn!";
    } catch (Exception $e) {
        $error = "Lỗi khi xóa món: " . $e->getMessage();
    }
}

// 5. Lấy dữ liệu thống kê
$total_orders = 0;
$pending_orders = 0;
$processing_orders = 0;
$completed_orders = 0;
$total_products = 0;
$total_revenue = 0;
$total_users = 0;
$users_list = [];

try {
    $total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
    $pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Đang xử lý'")->fetchColumn() ?: 0;
    $processing_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Đang pha chế'")->fetchColumn() ?: 0;
    $completed_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Đã hoàn thành'")->fetchColumn() ?: 0;
    $total_products = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0;
    $total_revenue = $conn->query("SELECT SUM($total_col) FROM orders WHERE status != 'Đã hủy'")->fetchColumn() ?: 0;
    $total_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
    $users_list = $conn->query("SELECT id, name, phone, address, created_at FROM users ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Lấy danh mục đồ uống
$categories = [];
try {
    $categories = $conn->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Lấy danh sách toàn bộ món
$all_products = [];
try {
    $all_products = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Lấy danh sách toppings
$toppings_list = [];
try {
    $toppings_list = $conn->query("SELECT * FROM toppings ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Lấy danh sách 50 đơn hàng mới nhất
$orders = [];
try {
    $orders = $conn->query("SELECT * FROM orders ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cozy Milk Tea • Hệ Thống Quản Trị POS Chuyên Nghiệp</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Quicksand:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-dark: #3E2723;
            --primary-brown: #5D4037;
            --accent-orange: #d35400;
            --accent-orange-light: #fff3e0;
            --bg-sidebar: #2B1810;
            --bg-body: #F7F5F2;
            --card-shadow: 0 10px 30px rgba(93, 64, 55, 0.07);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #2c3e50;
            min-height: 100vh;
            margin: 0;
            display: flex;
        }

        /* 1. SIDEBAR HIỆN ĐẠI CỐ ĐỊNH */
        .sidebar {
            width: 270px;
            background: linear-gradient(180deg, #2A170F 0%, #1A0D08 100%);
            color: #fff;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.25);
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #d35400, #e67e22);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(211, 84, 0, 0.35);
        }

        .sidebar-nav {
            padding: 18px 12px;
            flex: 1;
            overflow-y: auto;
        }

        .nav-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255, 255, 255, 0.4);
            padding: 10px 14px 6px;
            font-weight: 700;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 6px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .sidebar-link i { font-size: 18px; width: 22px; text-align: center; }

        .sidebar-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(4px);
        }

        .sidebar-link.active {
            background: linear-gradient(135deg, #d35400 0%, #e67e22 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(211, 84, 0, 0.4);
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.15);
        }

        /* 2. MAIN CONTENT WRAPPER */
        .main-wrapper {
            margin-left: 270px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: calc(100% - 270px);
        }

        .top-navbar {
            height: 72px;
            background: #ffffff;
            border-bottom: 1px solid #ede7df;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .content-area {
            padding: 28px;
            flex: 1;
        }

        /* 3. THẺ STAT CARD THỐNG KÊ */
        .stat-box {
            background: #ffffff;
            border-radius: 18px;
            padding: 22px;
            border: 1px solid #ede7df;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.25s, box-shadow 0.25s;
        }
        .stat-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 36px rgba(93, 64, 55, 0.12);
        }
        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        /* 4. CARD BẢNG VÀ NỘI DUNG */
        .dashboard-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #ede7df;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .dashboard-card-header {
            padding: 18px 24px;
            border-bottom: 1px solid #ede7df;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        /* 5. TABS ĐƠN HÀNG */
        .order-filter-pill {
            padding: 7px 16px;
            border-radius: 20px;
            background: #f0ece6;
            color: #666;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .order-filter-pill:hover { background: #e5dfd7; }
        .order-filter-pill.active {
            background: var(--accent-orange);
            color: #fff;
        }

        .preview-img-box {
            width: 90px;
            height: 90px;
            border-radius: 12px;
            border: 1.5px dashed #ccc;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
        }

        /* IN PHIẾU CHẾ BIẾN */
        @media print {
            body * { visibility: hidden; }
            #receipt-print-area, #receipt-print-area * { visibility: visible; }
            #receipt-print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
                padding: 10px;
                background: #fff;
                color: #000;
                font-family: monospace;
            }
        }
    </style>
</head>
<body>

<!-- ====================================================================
     1. SIDEBAR ĐIỀU HƯỚNG BÊN TRÁI
     ==================================================================== -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🧋</div>
        <div>
            <div style="font-family: 'Quicksand', sans-serif; font-size: 17px; font-weight: 800; line-height: 1.2;">Cozy Milk Tea</div>
            <div style="font-size: 11px; opacity: 0.6; text-transform: uppercase; letter-spacing: 0.05em;">Hệ Thống Quản Trị POS</div>
        </div>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section-title">Nghiệp vụ chính</div>
        <div class="sidebar-link active" onclick="switchTab('tab-dashboard', this)">
            <i class="bi bi-pie-chart-fill"></i>
            <span>Tổng Quan & Doanh Thu</span>
        </div>
        <div class="sidebar-link" onclick="switchTab('tab-orders', this)">
            <i class="bi bi-receipt"></i>
            <span>Quản Lý Đơn Hàng</span>
            <?php if ($pending_orders > 0): ?>
                <span class="badge bg-danger rounded-pill ms-auto"><?= $pending_orders ?></span>
            <?php endif; ?>
        </div>
        <div class="sidebar-link" onclick="switchTab('tab-menu', this)">
            <i class="bi bi-cup-straw"></i>
            <span>Thực Đơn & Topping</span>
        </div>
        <div class="sidebar-link" onclick="switchTab('tab-customers', this)">
            <i class="bi bi-people-fill"></i>
            <span>Khách Hàng Thành Viên</span>
        </div>

        <div class="nav-section-title mt-3">Truy cập nhanh</div>
        <a href="http://localhost:5173" target="_blank" class="sidebar-link">
            <i class="bi bi-browser-chrome"></i>
            <span>Mở Cửa Hàng Web</span>
            <i class="bi bi-arrow-up-right ms-auto small"></i>
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 34px; height: 34px; border-radius: 50%; background: #4e342e; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                    A
                </div>
                <div>
                    <div style="font-size: 13px; font-weight: 700; line-height: 1.2;"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></div>
                    <div style="font-size: 11px; color: #a1887f;">Quản trị viên</div>
                </div>
            </div>
            <a href="admin.php?action=logout" class="btn btn-sm btn-outline-danger" title="Đăng xuất" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất trang quản trị?')">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</aside>

<!-- ====================================================================
     2. KHU VỰC NỘI DUNG CHÍNH (MAIN WRAPPER)
     ==================================================================== -->
<div class="main-wrapper">
    <!-- Top Navbar -->
    <header class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <h4 class="fw-bold mb-0 text-dark" id="page-title">
                <i class="bi bi-pie-chart-fill text-danger me-2"></i>Tổng Quan Quán Trà Sữa
            </h4>
        </div>

        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3">
                <i class="bi bi-broadcast me-1"></i> Máy chủ: Online (Port 8080)
            </span>
            <span class="text-muted small">
                <i class="bi bi-clock me-1"></i> <?= date('d/m/Y') ?>
            </span>
        </div>
    </header>

    <div class="content-area">
        <!-- Thông báo thành công / lỗi -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ============================================================
             TAB 1: TỔNG QUAN & DOANH THU (DASHBOARD)
             ============================================================ -->
        <div id="tab-dashboard" class="tab-pane-content">
            <!-- 4 Thẻ Thống Kê Nổi Bật -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-sm-6">
                    <div class="stat-box">
                        <div>
                            <div class="text-muted small fw-semibold">DOANH THU ĐÃ THU</div>
                            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($total_revenue) ?> <small class="fs-6">đ</small></div>
                            <div class="small text-success mt-1"><i class="bi bi-arrow-up-right me-1"></i>Đồng bộ tự động</div>
                        </div>
                        <div class="stat-icon" style="background: #e8f5e9; color: #2e7d32;">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="stat-box">
                        <div>
                            <div class="text-muted small fw-semibold">TỔNG ĐƠN HÀNG</div>
                            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($total_orders) ?></div>
                            <div class="small text-muted mt-1">Từ App & Website</div>
                        </div>
                        <div class="stat-icon" style="background: #e3f2fd; color: #1565c0;">
                            <i class="bi bi-receipt-cutoff"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="stat-box">
                        <div>
                            <div class="text-muted small fw-semibold">ĐƠN CHỜ PHA CHẾ</div>
                            <div class="fs-3 fw-bold text-danger mt-1"><?= number_format($pending_orders) ?></div>
                            <div class="small text-danger mt-1"><i class="bi bi-hourglass-split me-1"></i>Cần xử lý ngay</div>
                        </div>
                        <div class="stat-icon" style="background: #ffebee; color: #c62828;">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="stat-box">
                        <div>
                            <div class="text-muted small fw-semibold">MÓN TRÊN MENU</div>
                            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($total_products) ?> <small class="fs-6">món</small></div>
                            <div class="small text-muted mt-1"><?= count($categories) ?> danh mục món</div>
                        </div>
                        <div class="stat-icon" style="background: #fff3e0; color: #e65100;">
                            <i class="bi bi-cup-straw"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bảng Đơn Hàng Mới Nhất -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="fw-bold fs-5 text-dark">
                        <i class="bi bi-lightning-charge-fill text-warning me-2"></i>Đơn Hàng Vừa Nhận (Cần Xử Lý)
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="switchTab('tab-orders', document.querySelectorAll('.sidebar-link')[1])">
                        Xem tất cả đơn hàng →
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Chi tiết thức uống</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Thao tác nhanh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recent_orders = array_slice($orders, 0, 8);
                            if (empty($recent_orders)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Chưa có đơn hàng nào được đặt.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_orders as $o): 
                                    $amount = $o[$total_col] ?? 0;
                                    $b_class = 'bg-secondary';
                                    if ($o['status'] === 'Đang xử lý') $b_class = 'bg-warning text-dark';
                                    elseif ($o['status'] === 'Đang pha chế') $b_class = 'bg-info text-dark';
                                    elseif ($o['status'] === 'Đang giao') $b_class = 'bg-primary';
                                    elseif ($o['status'] === 'Đã hoàn thành') $b_class = 'bg-success';
                                    elseif ($o['status'] === 'Đã hủy') $b_class = 'bg-danger';
                                ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-danger">#<?= $o['id'] ?></td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($o['customer_name'] ?? 'Khách hàng') ?></div>
                                            <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($o['customer_phone'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <small class="text-truncate d-inline-block" style="max-width: 280px;">
                                                <?= htmlspecialchars($o['delivery_address'] ?? 'Tại quán') ?>
                                            </small>
                                        </td>
                                        <td class="fw-bold text-success fs-6"><?= number_format($amount) ?> đ</td>
                                        <td><span class="badge <?= $b_class ?> badge-status"><?= htmlspecialchars($o['status']) ?></span></td>
                                        <td class="text-center">
                                            <form method="POST" action="admin.php" class="d-inline-flex gap-1">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                <select name="status" class="form-select form-select-sm" style="width: 130px;">
                                                    <option value="Đang xử lý" <?= $o['status'] === 'Đang xử lý' ? 'selected' : '' ?>>Đang xử lý</option>
                                                    <option value="Đang pha chế" <?= $o['status'] === 'Đang pha chế' ? 'selected' : '' ?>>Đang pha chế</option>
                                                    <option value="Đang giao" <?= $o['status'] === 'Đang giao' ? 'selected' : '' ?>>Đang giao</option>
                                                    <option value="Đã hoàn thành" <?= $o['status'] === 'Đã hoàn thành' ? 'selected' : '' ?>>Đã hoàn thành</option>
                                                    <option value="Đã hủy" <?= $o['status'] === 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-dark" title="Lưu trạng thái"><i class="bi bi-check-lg"></i></button>
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

        <!-- ============================================================
             TAB 2: QUẢN LÝ ĐƠN HÀNG (LIVE ORDERS)
             ============================================================ -->
        <div id="tab-orders" class="tab-pane-content" style="display: none;">
            <div class="dashboard-card">
                <div class="dashboard-card-header flex-wrap gap-2">
                    <!-- Bộ lọc trạng thái đơn -->
                    <div class="d-flex align-items-center gap-2 flex-wrap" id="order-status-tabs">
                        <button class="order-filter-pill active" onclick="filterOrderStatus('all', this)">Tất cả đơn (<?= count($orders) ?>)</button>
                        <button class="order-filter-pill" onclick="filterOrderStatus('Đang xử lý', this)">Chờ xử lý (<?= $pending_orders ?>)</button>
                        <button class="order-filter-pill" onclick="filterOrderStatus('Đang pha chế', this)">Đang pha chế (<?= $processing_orders ?>)</button>
                        <button class="order-filter-pill" onclick="filterOrderStatus('Đã hoàn thành', this)">Hoàn thành (<?= $completed_orders ?>)</button>
                        <button class="order-filter-pill" onclick="filterOrderStatus('Đã hủy', this)">Đã hủy</button>
                    </div>

                    <!-- Tìm kiếm đơn hàng -->
                    <div style="width: 260px;">
                        <input type="text" id="orderSearchInput" onkeyup="searchOrders()" class="form-control form-control-sm" placeholder="🔍 Tìm theo SĐT, Tên khách...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="ordersTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 80px;">Mã đơn</th>
                                <th>Khách hàng & SĐT</th>
                                <th>Địa chỉ giao</th>
                                <th>Chi tiết các ly đã đặt</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có đơn hàng nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                        $order_amount = $order[$total_col] ?? 0;
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
                                    <tr class="order-row" data-status="<?= htmlspecialchars($order['status']) ?>">
                                        <td class="ps-3 fw-bold text-danger">#<?= $order['id'] ?></td>
                                        <td>
                                            <strong class="customer-name"><?= htmlspecialchars($order['customer_name'] ?? 'Khách hàng') ?></strong><br>
                                            <small class="text-muted customer-phone"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['customer_phone'] ?? '') ?></small>
                                        </td>
                                        <td style="max-width: 180px;">
                                            <small class="text-truncate d-block" title="<?= htmlspecialchars($order['delivery_address'] ?? '') ?>">
                                                <?= htmlspecialchars($order['delivery_address'] ?? 'Tại quán') ?>
                                            </small>
                                        </td>
                                        <td style="font-size: 0.88rem;">
                                            <?php foreach ($items as $it): ?>
                                                <div class="mb-1">
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars($it['product_name'] ?? '') ?></span>
                                                    <span class="badge bg-danger">x<?= $it['quantity'] ?? 1 ?></span>
                                                    <span class="badge bg-light text-dark border"><?= number_format($it['subtotal'] ?? 0) ?>đ</span>
                                                    <br>
                                                    <small class="text-muted fst-italic">↳ <?= htmlspecialchars($it['toppings_text'] ?? 'Mặc định') ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                        <td class="fw-bold text-success fs-6"><?= number_format($order_amount) ?> đ</td>
                                        <td>
                                            <span class="badge <?= $badge_class ?> badge-status">
                                                <?= htmlspecialchars($order['status'] ?? 'Đang xử lý') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <form method="POST" action="admin.php" class="d-inline-flex gap-1">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <select name="status" class="form-select form-select-sm" style="width: 125px;">
                                                    <option value="Đang xử lý" <?= ($order['status'] ?? '') === 'Đang xử lý' ? 'selected' : '' ?>>Đang xử lý</option>
                                                    <option value="Đang pha chế" <?= ($order['status'] ?? '') === 'Đang pha chế' ? 'selected' : '' ?>>Đang pha chế</option>
                                                    <option value="Đang giao" <?= ($order['status'] ?? '') === 'Đang giao' ? 'selected' : '' ?>>Đang giao</option>
                                                    <option value="Đã hoàn thành" <?= ($order['status'] ?? '') === 'Đã hoàn thành' ? 'selected' : '' ?>>Đã hoàn thành</option>
                                                    <option value="Đã hủy" <?= ($order['status'] ?? '') === 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-dark" title="Cập nhật"><i class="bi bi-check-lg"></i></button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-secondary ms-1" title="In phiếu chế biến" onclick='printBill(<?= json_encode($order) ?>, <?= json_encode($items) ?>, <?= $order_amount ?>)'>
                                                <i class="bi bi-printer"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================
             TAB 3: THỰC ĐƠN & TOPPING (MENU MANAGEMENT)
             ============================================================ -->
        <div id="tab-menu" class="tab-pane-content" style="display: none;">
            <!-- Hai Form: Thêm Món & Thêm Topping -->
            <div class="row g-4 mb-4">
                <!-- Form Thêm Món Mới -->
                <div class="col-lg-8">
                    <div class="dashboard-card h-100">
                        <div class="dashboard-card-header">
                            <span class="fw-bold text-primary fs-5"><i class="bi bi-plus-circle-fill me-2"></i>Thêm Món Mới Vào Menu App & Web</span>
                        </div>
                        <div class="p-4">
                            <form method="POST" action="admin.php">
                                <input type="hidden" name="action" value="add_product">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Danh mục thức uống</label>
                                        <select name="category_id" class="form-select" required>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-semibold">Tên món đồ uống</label>
                                        <input type="text" name="name" class="form-control" placeholder="VD: Trà Sữa Oolong Nướng Kem Trứng" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Giá cơ bản (VNĐ)</label>
                                        <input type="number" name="price" class="form-control" placeholder="38000" step="1000" required>
                                    </div>
                                    <div class="col-md-9">
                                        <label class="form-label small fw-semibold">Đường dẫn ảnh (URL)</label>
                                        <input type="url" name="image_url" id="image_url_input" class="form-control" placeholder="https://images.unsplash.com/..." oninput="previewImage(this.value)" required>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-center">
                                        <div class="preview-img-box mt-3" id="img-preview-container">
                                            <span class="text-muted small">Ảnh xem trước</span>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-semibold">Mô tả hương vị món</label>
                                        <input type="text" name="description" class="form-control" placeholder="Hương vị nướng thơm lừng đặc trưng, vị ngọt thanh kết hợp kem béo ngậy...">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3 px-4">
                                    <i class="bi bi-save me-1"></i> Lưu Món Mới Vào Thực Đơn
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Form Thêm Topping -->
                <div class="col-lg-4">
                    <div class="dashboard-card h-100">
                        <div class="dashboard-card-header">
                            <span class="fw-bold text-success fs-5"><i class="bi bi-plus-circle-fill me-2"></i>Thêm Topping Mới</span>
                        </div>
                        <div class="p-4">
                            <form method="POST" action="admin.php">
                                <input type="hidden" name="action" value="add_topping">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Tên Topping</label>
                                    <input type="text" name="topping_name" class="form-control" placeholder="VD: Trân Châu Trắng 3Q" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Giá phụ thu (VNĐ)</label>
                                    <input type="number" name="topping_price" class="form-control" placeholder="8000" step="1000" required>
                                </div>
                                <button type="submit" class="btn btn-success w-100 mt-2">
                                    <i class="bi bi-save me-1"></i> Thêm Topping Ngay
                                </button>
                            </form>

                            <hr class="my-3">
                            <div class="small fw-bold text-muted mb-2">Topping hiện có (<?= count($toppings_list) ?>):</div>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($toppings_list as $tp): ?>
                                    <span class="badge bg-light text-dark border p-2">
                                        <?= htmlspecialchars($tp['name']) ?> (+<?= number_format($tp['price']) ?>đ)
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danh Sách Toàn Bộ Món Hiện Tại -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="fw-bold fs-5 text-dark"><i class="bi bi-list-stars me-2 text-danger"></i>Danh Sách Món Trong Thực Đơn (<?= count($all_products) ?> món)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 70px;">ID</th>
                                <th style="width: 80px;">Hình ảnh</th>
                                <th>Tên món đồ uống</th>
                                <th>Danh mục</th>
                                <th>Giá bán</th>
                                <th>Mô tả</th>
                                <th class="text-center" style="width: 100px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_products as $prod): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-muted">#<?= $prod['id'] ?></td>
                                    <td>
                                        <img src="<?= htmlspecialchars($prod['image_url'] ?? $prod['image'] ?? 'https://images.unsplash.com/photo-1558857563-b37cf5b62b1a?w=100') ?>" 
                                             alt="<?= htmlspecialchars($prod['name']) ?>" 
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    </td>
                                    <td><strong><?= htmlspecialchars($prod['name']) ?></strong></td>
                                    <td><span class="badge bg-secondary-subtle text-secondary border"><?= htmlspecialchars($prod['category_name'] ?? 'Trà Sữa') ?></span></td>
                                    <td class="fw-bold text-danger"><?= number_format($prod['base_price'] ?? $prod['price'] ?? 0) ?> đ</td>
                                    <td><small class="text-muted d-inline-block text-truncate" style="max-width: 250px;"><?= htmlspecialchars($prod['description'] ?? '') ?></small></td>
                                    <td class="text-center">
                                        <form method="POST" action="admin.php" onsubmit="return confirm('Bạn có chắc muốn xóa món <?= htmlspecialchars($prod['name']) ?> khỏi thực đơn?')">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa món"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================
             TAB 4: KHÁCH HÀNG THÀNH VIÊN (CUSTOMERS)
             ============================================================ -->
        <div id="tab-customers" class="tab-pane-content" style="display: none;">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="fw-bold fs-5 text-dark"><i class="bi bi-people-fill text-primary me-2"></i>Tài Khoản Khách Hàng (<?= count($users_list) ?> thành viên)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 80px;">ID</th>
                                <th>Khách hàng</th>
                                <th>Số điện thoại</th>
                                <th>Địa chỉ giao hàng</th>
                                <th>Ngày tạo</th>
                                <th class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users_list)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Chưa có thành viên đăng ký.</td></tr>
                            <?php else: ?>
                                <?php foreach ($users_list as $u): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted">#<?= $u['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #d35400; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                                    <?= strtoupper(substr($u['name'] ?? 'U', 0, 1)) ?>
                                                </div>
                                                <strong><?= htmlspecialchars($u['name'] ?? '') ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($u['phone']) ?>
                                            </span>
                                        </td>
                                        <td><small class="text-muted"><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($u['address'] ?: 'Chưa cập nhật') ?></small></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($u['created_at']) ?></small></td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-check-circle me-1"></i>Hoạt động
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================
     MODAL & KHU VỰC IN PHIẾU PHA CHẾ (RECEIPT PRINT)
     ============================================================ -->
<div id="receipt-print-area" style="display: none;">
    <div style="text-align: center; border-bottom: 1px dashed #000; padding-bottom: 8px; margin-bottom: 8px;">
        <h3 style="margin: 0; font-size: 18px; font-weight: bold;">COZY MILK TEA</h3>
        <p style="margin: 2px 0; font-size: 11px;">PHIẾU PHA CHẾ & GIAO HÀNG</p>
        <p style="margin: 0; font-size: 11px;">Mã đơn: <strong id="print-order-id">#</strong></p>
    </div>
    <div style="font-size: 12px; margin-bottom: 8px;">
        <div>Khách: <strong id="print-customer-name"></strong></div>
        <div>SĐT: <span id="print-customer-phone"></span></div>
        <div>Địa chỉ: <span id="print-customer-addr"></span></div>
    </div>
    <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 6px 0; margin-bottom: 8px; font-size: 12px;" id="print-items-list">
    </div>
    <div style="text-align: right; font-weight: bold; font-size: 14px;">
        TỔNG CỘNG: <span id="print-total-amount"></span> đ
    </div>
    <div style="text-align: center; margin-top: 12px; font-size: 11px;">
        *** Xin cảm ơn quý khách! ***
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Chuyển Tab trong Sidebar
    function switchTab(tabId, element) {
        document.querySelectorAll('.tab-pane-content').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.sidebar-link').forEach(el => el.classList.remove('active'));

        const target = document.getElementById(tabId);
        if (target) target.style.display = 'block';
        if (element) element.classList.add('active');

        // Cập nhật tiêu đề trang
        const titleMap = {
            'tab-dashboard': '<i class="bi bi-pie-chart-fill text-danger me-2"></i>Tổng Quan Quán Trà Sữa',
            'tab-orders': '<i class="bi bi-receipt text-warning me-2"></i>Điều Phối & Xử Lý Đơn Hàng',
            'tab-menu': '<i class="bi bi-cup-straw text-primary me-2"></i>Quản Lý Thực Đơn & Topping',
            'tab-customers': '<i class="bi bi-people-fill text-success me-2"></i>Danh Sách Khách Hàng Thành Viên'
        };
        document.getElementById('page-title').innerHTML = titleMap[tabId] || 'Quản Trị Hệ Thống';
    }

    // Lọc đơn hàng theo trạng thái
    function filterOrderStatus(status, btn) {
        document.querySelectorAll('.order-filter-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const rows = document.querySelectorAll('.order-row');
        rows.forEach(r => {
            if (status === 'all' || r.getAttribute('data-status') === status) {
                r.style.display = '';
            } else {
                r.style.display = 'none';
            }
        });
    }

    // Tìm kiếm đơn hàng tức thì
    function searchOrders() {
        const q = document.getElementById('orderSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.order-row');
        rows.forEach(r => {
            const name = r.querySelector('.customer-name')?.innerText.toLowerCase() || '';
            const phone = r.querySelector('.customer-phone')?.innerText.toLowerCase() || '';
            if (name.includes(q) || phone.includes(q)) {
                r.style.display = '';
            } else {
                r.style.display = 'none';
            }
        });
    }

    // Xem trước ảnh khi dán URL
    function previewImage(url) {
        const box = document.getElementById('img-preview-container');
        if (url && url.trim().length > 5) {
            box.innerHTML = `<img src="${url}" style="width:100%;height:100%;object-fit:cover;border-radius:10px;" onerror="this.src='https://images.unsplash.com/photo-1558857563-b37cf5b62b1a?w=100'">`;
        } else {
            box.innerHTML = '<span class="text-muted small">Ảnh xem trước</span>';
        }
    }

    // In Phiếu chế biến đơn hàng
    function printBill(order, items, total) {
        document.getElementById('print-order-id').innerText = '#' + order.id;
        document.getElementById('print-customer-name').innerText = order.customer_name || 'Khách hàng';
        document.getElementById('print-customer-phone').innerText = order.customer_phone || '---';
        document.getElementById('print-customer-addr').innerText = order.delivery_address || 'Tại quán';
        document.getElementById('print-total-amount').innerText = Number(total).toLocaleString('vi-VN');

        let html = '';
        items.forEach(it => {
            html += `<div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <span>${it.product_name} x${it.quantity}</span>
                <span>${Number(it.subtotal).toLocaleString('vi-VN')}đ</span>
            </div>
            <div style="font-size:10px; color:#555; margin-bottom:6px;">↳ ${it.toppings_text || 'Mặc định'}</div>`;
        });
        document.getElementById('print-items-list').innerHTML = html;

        document.getElementById('receipt-print-area').style.display = 'block';
        window.print();
        document.getElementById('receipt-print-area').style.display = 'none';
    }
</script>
</body>
</html>
