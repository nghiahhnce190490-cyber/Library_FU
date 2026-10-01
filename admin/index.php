<?php
// =========================================================
// Trang thủ thư: https://thuvienfpt.id.vn/admin/
// Dùng CHUNG giao diện index.html với trang sinh viên (chỉ cần sửa 1 file),
// thêm 3 thứ:
//   1) <base href="../">  -> mọi đường dẫn (api/, style.css, app.js) trỏ về thư mục gốc
//   2) LIBGO_ADMIN_PAGE   -> app.js biết đây là trang thủ thư
//   3) admin/intake.css + admin/intake.js -> mục "Nhập sách" bằng mã vạch
// Server chặn ngay: chưa đăng nhập thủ thư thì không gửi giao diện, chuyển về trang chính.
// =========================================================
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ../');
    exit;
}

$html = @file_get_contents(__DIR__ . '/../index.html');
if ($html === false) {
    http_response_code(500);
    exit('Không đọc được giao diện.');
}

// Đổi số phiên bản theo ngày sửa file -> trình duyệt tự tải bản mới, không cần Ctrl+F5
$ver = max((int) @filemtime(__DIR__ . '/intake.js'), (int) @filemtime(__DIR__ . '/intake.css'));

$html = preg_replace('/<head(\s[^>]*)?>/i', "$0\n<base href=\"../\" />", $html, 1);
$html = preg_replace('/<title>(.*?)<\/title>/is', '<title>Thủ thư · $1</title>', $html, 1);
$html = preg_replace(
    '/<\/head>/i',
    "<link rel=\"stylesheet\" href=\"admin/intake.css?v=$ver\" />\n"
        . "<script>window.LIBGO_ADMIN_PAGE = true;</script>\n</head>",
    $html,
    1
);
$html = preg_replace('/<\/body>/i', "<script src=\"admin/intake.js?v=$ver\"></script>\n</body>", $html, 1);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
echo $html;
