<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    exit(0);
}

// Cấu hình Database Online dùng chung của nhóm
$host = '103.139.203.43';
$db_name = 'sql_nhom38_itimi';
$username = 'sql_nhom38_itimi';
$password = 'af7d66ef605cd8';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json; charset=UTF-8');
    die(json_encode([
        'status' => 'error',
        'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage()
    ]));
}