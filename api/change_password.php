<?php
// Đổi mật khẩu khi đã đăng nhập (sinh viên hoặc thủ thư)
// POST {current_password, new_password}
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method không được hỗ trợ"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$current = $data['current_password'] ?? '';
$new = $data['new_password'] ?? '';

if (isset($_SESSION['admin_id'])) {
    $table = 'admins';
    $id = (int) $_SESSION['admin_id'];
} elseif (isset($_SESSION['member_id'])) {
    $table = 'members';
    $id = (int) $_SESSION['member_id'];
} else {
    http_response_code(401);
    echo json_encode(["error" => "Bạn cần đăng nhập để đổi mật khẩu"]);
    exit;
}

if (strlen($new) < 6) {
    http_response_code(400);
    echo json_encode(["error" => "Mật khẩu mới phải có ít nhất 6 ký tự"]);
    exit;
}
if ($new === $current) {
    http_response_code(400);
    echo json_encode(["error" => "Mật khẩu mới phải khác mật khẩu hiện tại"]);
    exit;
}

$stmt = $pdo->prepare("SELECT password_hash FROM $table WHERE id = ?");
$stmt->execute([$id]);
$hash = $stmt->fetchColumn();

if (!$hash || !password_verify($current, $hash)) {
    http_response_code(400);
    echo json_encode(["error" => "Mật khẩu hiện tại không đúng"]);
    exit;
}

$pdo->prepare("UPDATE $table SET password_hash = ? WHERE id = ?")
    ->execute([password_hash($new, PASSWORD_DEFAULT), $id]);

echo json_encode(["success" => true, "message" => "Đã đổi mật khẩu thành công"]);