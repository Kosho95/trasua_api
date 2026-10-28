<?php
require_once 'db.php';
header('Content-Type: application/json; charset=UTF-8');

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

$phone    = trim($data['phone'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($phone) || empty($password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Vui lòng nhập đầy đủ Số điện thoại và Mật khẩu!'
    ]);
    exit();
}

try {
    // 1. Tìm tài khoản theo số điện thoại
    $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. So khớp mật khẩu đã băm (hash) hoặc plain text fallback
    $isMatch = false;
    if ($user) {
        if (password_verify($password, $user['password'])) {
            $isMatch = true;
        } elseif ($password === $user['password']) {
            $isMatch = true;
            // Tự động nâng cấp hash bcrypt
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $conn->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $user['id']]);
        }
    }

    if ($isMatch) {
        // Đăng nhập thành công, loại bỏ mật khẩu trước khi trả về app
        unset($user['password']);
        echo json_encode([
            'status' => 'success',
            'message' => 'Đăng nhập thành công!',
            'user' => $user
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Số điện thoại hoặc mật khẩu không chính xác!'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi kết nối: ' . $e->getMessage()
    ]);
}
?>