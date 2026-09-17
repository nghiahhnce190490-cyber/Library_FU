<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM members ORDER BY name ASC");
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

    if ($student_code === '' || $name === '') {
        http_response_code(400);
        echo json_encode(["error" => "Thiếu mã học sinh hoặc tên"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO members (student_code, name, class_name, contact, status) VALUES (?, ?, ?, ?, 'active')"
        );
        $stmt->execute([$student_code, $name, $class_name, $contact]);
        echo json_encode(["id" => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(["error" => "Mã học sinh đã tồn tại"]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method không được hỗ trợ"]);
