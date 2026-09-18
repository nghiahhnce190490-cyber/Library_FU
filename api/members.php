<?php
// Quản lý thành viên — CHỈ thủ thư
// GET  : danh sách thành viên (không trả về mật khẩu)
// POST : thêm thành viên mới, kèm mật khẩu ban đầu
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Bạn cần đăng nhập với quyền thủ thư để thực hiện thao tác này"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query(
        "SELECT id, student_code, name, class_name, contact, status,
                (password_hash IS NOT NULL) AS has_password
         FROM members ORDER BY name ASC"
    );
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($method === 'POST') {
    // Trong thực tế, dữ liệu thành viên nên được đồng bộ tự động từ API của trường.
    // Endpoint này dùng để thêm thủ công trong lúc chưa có kết nối đó.
    $data = json_decode(file_get_contents('php://input'), true);
    $student_code = trim($data['student_code'] ?? '');
    $name = trim($data['name'] ?? '');
    $class_name = trim($data['class_name'] ?? '');
    $contact = trim($data['contact'] ?? '');
    $password = $data['password'] ?? '';

    if ($student_code === '' || $name === '') {
        http_response_code(400);
        echo json_encode(["error" => "Thiếu mã học sinh hoặc tên"]);
        exit;
    }
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(["error" => "Mật khẩu ban đầu phải có ít nhất 6 ký tự"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO members (student_code, name, class_name, contact, status, password_hash)
             VALUES (?, ?, ?, ?, 'active', ?)"
        );
        $stmt->execute([$student_code, $name, $class_name, $contact, password_hash($password, PASSWORD_DEFAULT)]);
        echo json_encode(["id" => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(["error" => "Mã học sinh đã tồn tại"]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method không được hỗ trợ"]);
