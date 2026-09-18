<?php
// Đăng nhập chung cho cả thủ thư và học sinh
// - Nếu trùng tài khoản thủ thư (bảng admins) -> role = admin
// - Nếu không, kiểm tra mã học sinh (bảng members) -> role = student
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method không được hỗ trợ"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(["error" => "Vui lòng nhập tên đăng nhập và mật khẩu"]);
    exit;
}

// Xóa phiên cũ để 1 trình duyệt chỉ đăng nhập 1 vai trò tại 1 thời điểm
$_SESSION = [];
session_regenerate_id(true);

// 1) Thử tài khoản thủ thư
$stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin && password_verify($password, $admin['password_hash'])) {
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    echo json_encode(["success" => true, "role" => "admin", "name" => $admin['username']]);
    exit;
}

// 2) Thử tài khoản học sinh
$stmt = $pdo->prepare("SELECT * FROM members WHERE student_code = ?");
$stmt->execute([$username]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if ($member && !empty($member['password_hash']) && password_verify($password, $member['password_hash'])) {
    $_SESSION['member_id'] = (int) $member['id'];
    $_SESSION['member_name'] = $member['name'];
    $_SESSION['student_code'] = $member['student_code'];
    echo json_encode(["success" => true, "role" => "student", "name" => $member['name']]);
    exit;
}

http_response_code(401);
echo json_encode(["error" => "Sai tên đăng nhập hoặc mật khẩu"]);
