<?php
// Quên mật khẩu (dành cho sinh viên) — 2 bước:
//   1) {action: "request", student_code, email}                  -> email phải trùng email đã lưu, rồi gửi mã 6 số tới đó
//   2) {action: "reset", student_code, code, new_password}       -> kiểm tra mã và đặt mật khẩu mới
// Mã có hiệu lực 10 phút, nhập sai tối đa 5 lần, mỗi 60 giây chỉ gửi lại được 1 lần.
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/mailer.php';
header('Content-Type: application/json; charset=utf-8');

const CODE_TTL_MIN = 10;
const MAX_CODE_ATTEMPTS = 5;
const RESEND_WAIT_SEC = 60;
const MAX_REQUESTS_PER_HOUR = 5;

function fail(int $code, string $msg): void
{
    http_response_code($code);
    echo json_encode(["error" => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, "Method không được hỗ trợ");

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        code_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        attempts INT NOT NULL DEFAULT 0,
        used TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        INDEX idx_member (member_id, created_at)
    )"
);

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';
$studentCode = trim($data['student_code'] ?? '');
if ($studentCode === '') fail(400, "Vui lòng nhập mã học sinh");

$stmt = $pdo->prepare("SELECT id, name, contact FROM members WHERE student_code = ?");
$stmt->execute([$studentCode]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);
$email = $member ? trim((string) $member['contact']) : '';
$hasEmail = $member && filter_var($email, FILTER_VALIDATE_EMAIL);

$now = time();
$fmt = fn(int $t) => date('Y-m-d H:i:s', $t);

// ---------------- BƯỚC 1: GỬI MÃ ----------------
if ($action === 'request') {
    $inputEmail = trim($data['email'] ?? '');
    if ($inputEmail === '') fail(400, "Vui lòng nhập email đã đăng ký với thư viện");

    if (!$hasEmail) {
        fail(404, "Tài khoản này chưa có email trong thư viện. Vui lòng liên hệ thủ thư để được cấp lại mật khẩu.");
    }
    // Email nhập vào phải trùng với email đã lưu (không phân biệt hoa/thường)
    if (mb_strtolower($inputEmail) !== mb_strtolower($email)) {
        fail(400, "Mã học sinh và email không khớp với thông tin trong thư viện.");
    }

    // Chống spam: chờ 60 giây giữa 2 lần gửi, tối đa 5 lần mỗi giờ
    $s = $pdo->prepare("SELECT MAX(created_at) AS last_at, COUNT(*) AS n FROM password_resets WHERE member_id = ? AND created_at > ?");
    $s->execute([$member['id'], $fmt($now - 3600)]);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    if ($r['last_at'] && $now - strtotime($r['last_at']) < RESEND_WAIT_SEC) {
        $wait = RESEND_WAIT_SEC - ($now - strtotime($r['last_at']));
        fail(429, "Vui lòng đợi $wait giây rồi gửi lại mã.");
    }
    if ((int) $r['n'] >= MAX_REQUESTS_PER_HOUR) {
        fail(429, "Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau 1 giờ.");
    }

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Vô hiệu các mã cũ, lưu mã mới (chỉ lưu bản băm)
    $pdo->prepare("UPDATE password_resets SET used = 1 WHERE member_id = ? AND used = 0")->execute([$member['id']]);
    $pdo->prepare("INSERT INTO password_resets (member_id, code_hash, expires_at, created_at) VALUES (?, ?, ?, ?)")
        ->execute([$member['id'], hash_password($code), $fmt($now + CODE_TTL_MIN * 60), $fmt($now)]);

    $name = htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8');
    $html = "
      <div style='font-family:Arial,sans-serif;max-width:480px;margin:auto;color:#1c2433'>
        <h2 style='color:#16335a;margin-bottom:4px'>Thư viện số</h2>
        <p>Xin chào <b>$name</b>,</p>
        <p>Bạn vừa yêu cầu đặt lại mật khẩu. Mã xác nhận của bạn là:</p>
        <p style='font-size:32px;font-weight:bold;letter-spacing:8px;color:#f26f21;margin:16px 0'>$code</p>
        <p>Mã có hiệu lực trong <b>" . CODE_TTL_MIN . " phút</b>. Không chia sẻ mã này cho bất kỳ ai.</p>
        <p style='color:#5b6474;font-size:13px'>Nếu bạn không yêu cầu, hãy bỏ qua email này, mật khẩu của bạn vẫn giữ nguyên.</p>
      </div>";

    if (!send_email($email, $member['name'], "Mã đặt lại mật khẩu Thư viện số: $code", $html)) {
        // Gửi thất bại -> hủy mã vừa tạo để lần sau gửi lại được ngay
        $pdo->prepare("DELETE FROM password_resets WHERE member_id = ? AND created_at = ?")->execute([$member['id'], $fmt($now)]);
        fail(500, "Chưa gửi được email. Vui lòng thử lại sau hoặc liên hệ thủ thư.");
    }

    echo json_encode([
        "success" => true,
        "message" => "Đã gửi mã xác nhận tới $email. Kiểm tra cả hộp thư Spam/Quảng cáo.",
    ]);
    exit;
}

// ---------------- BƯỚC 2: ĐẶT MẬT KHẨU MỚI ----------------
if ($action === 'reset') {
    $code = trim($data['code'] ?? '');
    $newPassword = $data['new_password'] ?? '';

    if (!preg_match('/^\d{6}$/', $code)) fail(400, "Mã xác nhận gồm 6 chữ số");
    if (strlen($newPassword) < 6) fail(400, "Mật khẩu mới phải có ít nhất 6 ký tự");
    if (!$member) fail(400, "Mã xác nhận không đúng hoặc đã hết hạn");

    $s = $pdo->prepare(
        "SELECT * FROM password_resets
         WHERE member_id = ? AND used = 0 AND expires_at > ?
         ORDER BY id DESC LIMIT 1"
    );
    $s->execute([$member['id'], $fmt($now)]);
    $reset = $s->fetch(PDO::FETCH_ASSOC);

    if (!$reset) fail(400, "Mã xác nhận không đúng hoặc đã hết hạn. Vui lòng gửi lại mã mới.");
    if ((int) $reset['attempts'] >= MAX_CODE_ATTEMPTS) {
        $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")->execute([$reset['id']]);
        fail(429, "Bạn đã nhập sai quá nhiều lần. Vui lòng gửi lại mã mới.");
    }

    if (!password_verify($code, $reset['code_hash'])) {
        $pdo->prepare("UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?")->execute([$reset['id']]);
        $left = MAX_CODE_ATTEMPTS - ((int) $reset['attempts'] + 1);
        fail(400, $left > 0 ? "Mã xác nhận không đúng (còn $left lần thử)" : "Mã xác nhận không đúng. Vui lòng gửi lại mã mới.");
    }

    $pdo->prepare("UPDATE members SET password_hash = ? WHERE id = ?")
        ->execute([hash_password($newPassword), $member['id']]);
    $pdo->prepare("UPDATE password_resets SET used = 1 WHERE member_id = ?")->execute([$member['id']]);
    // Gỡ khóa đăng nhập tạm thời (nếu trước đó nhập sai nhiều lần)
    try {
        $pdo->prepare("DELETE FROM login_attempts WHERE username = ?")->execute([mb_strtolower($studentCode)]);
    } catch (PDOException $e) { /* bảng chưa có thì bỏ qua */ }

    echo json_encode(["success" => true, "message" => "Đã đặt mật khẩu mới. Bạn có thể đăng nhập ngay."]);
    exit;
}

fail(400, "Yêu cầu không hợp lệ");