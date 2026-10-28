<?php
require_once 'db.php';
header('Content-Type: application/json; charset=UTF-8');

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

$name     = trim($data['name'] ?? '');
$phone    = trim($data['phone'] ?? '');
$password = trim($data['password'] ?? '');
$address  = trim($data['address'] ?? '');

if (empty($name) || empty($phone) || empty($password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Vui lòng điền đầy đủ Họ tên, Số điện thoại và Mật khẩu!'
    ]);
    exit();
}

try {
    // 1. Kiểm tra số điện thoại đã đăng ký trước đó chưa
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $checkStmt->execute([$phone]);
    if ($checkStmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Số điện thoại này đã được đăng ký tài khoản!'
        ]);
        exit();
    }

    // 2. Mã hóa mật khẩu an toàn bằng bcrypt
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // 3. Lưu tài khoản mới vào database
    $stmt = $conn->prepare("INSERT INTO users (name, phone, password, address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $phone, $hashedPassword, $address]);
    $newId = $conn->lastInsertId();

    echo json_encode([
        'status' => 'success',
        'message' => 'Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay.',
        'user' => [
            'id' => (int)$newId,
            'name' => $name,
            'phone' => $phone,
            'address' => $address
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi máy chủ: ' . $e->getMessage()
    ]);
}
?>