<?php
// Đăng nhập chung cho cả thủ thư và học sinh
// - Nếu trùng tài khoản thủ thư (bảng admins) -> role = admin
// - Nếu không, kiểm tra mã học sinh (bảng members) -> role = student
// - Sai quá MAX_FAILS lần trong LOCK_MINUTES phút -> tạm khóa tài khoản đó
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

define('MAX_FAILS', 5);      // số lần sai tối đa
define('LOCK_MINUTES', 15);  // thời gian tính và thời gian khóa (phút)

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

// Bảng ghi lần đăng nhập sai (tự tạo nếu chưa có)
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        attempted_at DATETIME NOT NULL,
        INDEX idx_user_time (username, attempted_at)
    )"
);
$key = mb_strtolower($username);
$since = date('Y-m-d H:i:s', time() - LOCK_MINUTES * 60);

// Đang bị khóa?
$stmt = $pdo->prepare("SELECT COUNT(*), MIN(attempted_at) FROM login_attempts WHERE username = ? AND attempted_at > ?");
$stmt->execute([$key, $since]);
[$fails, $firstFail] = $stmt->fetch(PDO::FETCH_NUM);
if ((int) $fails >= MAX_FAILS) {
    $unlockAt = strtotime($firstFail) + LOCK_MINUTES * 60;
    $minutes = max(1, (int) ceil(($unlockAt - time()) / 60));
    http_response_code(429);
    echo json_encode(["error" => "Bạn đã nhập sai quá " . MAX_FAILS . " lần. Vui lòng thử lại sau $minutes phút."]);
    exit;
}

// Xóa phiên cũ để 1 trình duyệt chỉ đăng nhập 1 vai trò tại 1 thời điểm
$_SESSION = [];
session_regenerate_id(true);

function loginSuccess(PDO $pdo, string $key, array $payload): void {
    $pdo->prepare("DELETE FROM login_attempts WHERE username = ?")->execute([$key]);
    echo json_encode(array_merge(["success" => true], $payload));
    exit;
}

// 1) Thử tài khoản thủ thư
$stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin && password_verify($password, $admin['password_hash'])) {
    if (password_needs_rehash($admin['password_hash'], PASSWORD_BCRYPT, PASSWORD_OPTIONS)) {
        $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")->execute([hash_password($password), $admin['id']]);
    }
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    loginSuccess($pdo, $key, ["role" => "admin", "name" => $admin['username']]);
}

// 2) Thử tài khoản học sinh
$stmt = $pdo->prepare("SELECT * FROM members WHERE student_code = ?");
$stmt->execute([$username]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if ($member && !empty($member['password_hash']) && password_verify($password, $member['password_hash'])) {
    if (password_needs_rehash($member['password_hash'], PASSWORD_BCRYPT, PASSWORD_OPTIONS)) {
        $pdo->prepare("UPDATE members SET password_hash = ? WHERE id = ?")->execute([hash_password($password), $member['id']]);
    }
    $_SESSION['member_id'] = (int) $member['id'];
    $_SESSION['member_name'] = $member['name'];
    $_SESSION['student_code'] = $member['student_code'];
    loginSuccess($pdo, $key, ["role" => "student", "name" => $member['name']]);
}

// Sai -> ghi lại, dọn các bản ghi cũ hơn 1 ngày
$pdo->prepare("INSERT INTO login_attempts (username, attempted_at) VALUES (?, ?)")
    ->execute([$key, date('Y-m-d H:i:s')]);
$pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < ?")
    ->execute([date('Y-m-d H:i:s', time() - 86400)]);

$left = MAX_FAILS - ((int) $fails + 1);
http_response_code(401);
echo json_encode([
    "error" => $left > 0
        ? "Sai tên đăng nhập hoặc mật khẩu (còn $left lần thử)"
        : "Sai tên đăng nhập hoặc mật khẩu. Tài khoản tạm khóa " . LOCK_MINUTES . " phút.",
]);