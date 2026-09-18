<?php
// Học sinh đăng nhập bằng mã học sinh + mật khẩu
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method không được hỗ trợ"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$student_code = trim($data['student_code'] ?? '');
$password = $data['password'] ?? '';

if ($student_code === '' || $password === '') {
    http_response_code(400);
    echo json_encode(["error" => "Thiếu mã học sinh hoặc mật khẩu"]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM members WHERE student_code = ?");
$stmt->execute([$student_code]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member || empty($member['password_hash']) || !password_verify($password, $member['password_hash'])) {
    http_response_code(401);
    echo json_encode(["error" => "Sai mã học sinh hoặc mật khẩu"]);
    exit;
}

// Tạo session ID mới sau khi đăng nhập (chống chiếm phiên)
session_regenerate_id(true);
$_SESSION['member_id'] = (int) $member['id'];
$_SESSION['member_name'] = $member['name'];
$_SESSION['student_code'] = $member['student_code'];

echo json_encode([
    "success" => true,
    "name" => $member['name'],
    "student_code" => $member['student_code'],
]);