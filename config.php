<?php
// Thông tin kết nối MySQL.
// Local (XAMPP): nếu không có biến môi trường, sẽ dùng giá trị mặc định bên dưới.
// Trên Render: đặt các biến môi trường DB_HOST, DB_NAME, DB_USER, DB_PASS
// trong phần Environment của Web Service, giá trị đó sẽ được ưu tiên dùng.

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'library_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'exe101_group6';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode(["error" => "Không thể kết nối cơ sở dữ liệu: " . $e->getMessage()]));
}
