<?php
// Thủ thư đặt lại mật khẩu cho học sinh (dùng cho học sinh cũ chưa có mật khẩu, hoặc quên mật khẩu)
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method không được hỗ trợ"]);
    exit;
}

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Chỉ thủ thư mới được đặt lại mật khẩu"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$student_code = trim($data['student_code'] ?? '');
$password = $data['password'] ?? '';

if ($student_code === '' || strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(["error" => "Cần mã học sinh và mật khẩu mới (ít nhất 6 ký tự)"]);
    exit;
}

$stmt = $pdo->prepare("UPDATE members SET password_hash = ? WHERE student_code = ?");
$stmt->execute([password_hash($password, PASSWORD_DEFAULT), $student_code]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(["error" => "Không tìm thấy học sinh với mã này"]);
    exit;
}

echo json_encode(["success" => true, "message" => "Đã đặt lại mật khẩu cho $student_code"]);
