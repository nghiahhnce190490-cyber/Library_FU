<?php
// Hàm dùng chung cho isbn_lookup.php, isbn_save.php, book_options.php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function json_out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Chỉ thủ thư được gọi. Nếu auth_login.php của bạn lưu vai trò bằng key khác
// (vd: $_SESSION['role'] === 'admin'), sửa đúng điều kiện ở hàm này.
function require_admin(): void {
    if (empty($_SESSION['admin_id'])) {
        json_out(["error" => "Bạn cần đăng nhập với quyền thủ thư"], 401);
    }
}

// Chuẩn hóa ISBN về 13 số. Trả null nếu mã không hợp lệ.
function normalize_isbn(string $raw): ?string {
    $s = strtoupper(preg_replace('/[^0-9Xx]/', '', $raw));

    if (strlen($s) === 10) {
        if (!preg_match('/^\d{9}[\dX]$/', $s)) return null;
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $v = ($s[$i] === 'X') ? 10 : (int) $s[$i];
            $sum += $v * (10 - $i);
        }
        if ($sum % 11 !== 0) return null;
        $s = '978' . substr($s, 0, 9);
        return $s . ean13_check_digit($s);
    }

    if (strlen($s) === 13 && ctype_digit($s)) {
        if (substr($s, 0, 3) !== '978' && substr($s, 0, 3) !== '979') return null;
        if (ean13_check_digit(substr($s, 0, 12)) !== $s[12]) return null;
        return $s;
    }

    return null;
}

function ean13_check_digit(string $first12): string {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int) $first12[$i] * ($i % 2 === 0 ? 1 : 3);
    }
    return (string) ((10 - $sum % 10) % 10);
}
