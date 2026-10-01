<?php
// POST api/isbn_save.php  (JSON)
// { isbn, title, author, publisher, publish_year, cover_url,
//   subject_code, shelf_location, book_link, qty }
// - ISBN đã có  -> cộng thêm qty bản vào đầu sách đó
// - Chưa có     -> tạo đầu sách mới
// - isbn rỗng   -> sách không có ISBN, luôn tạo mới
require_once __DIR__ . '/isbn_common.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(["error" => "Method không được hỗ trợ"], 405);
}
// Chỉ nhận JSON: chặn form giả mạo gửi từ trang khác (CSRF)
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
    json_out(["error" => "Yêu cầu không hợp lệ"], 415);
}

$d = json_decode(file_get_contents('php://input'), true);
if (!is_array($d)) json_out(["error" => "Dữ liệu không hợp lệ"], 400);

// Cắt chuỗi theo số ký tự (an toàn với tiếng Việt, không cần extension mbstring)
$str = fn($k, $max) => preg_replace('/^(.{0,' . $max . '}).*$/us', '$1', trim((string) ($d[$k] ?? '')));

$isbnRaw        = $str('isbn', 20);
$title          = $str('title', 255);
$author         = $str('author', 255);
$publisher      = $str('publisher', 255);
$subject_code   = strtoupper($str('subject_code', 50));
$shelf_location = $str('shelf_location', 100);
$book_link      = $str('book_link', 500);
$cover_url      = $str('cover_url', 500);
$read_access    = in_array($d['read_access'] ?? '', ['full', 'partial', 'none'], true) ? $d['read_access'] : null;
$year           = ($d['publish_year'] ?? '') === '' ? null : (int) $d['publish_year'];
$qty            = (int) ($d['qty'] ?? 0);

$isbn = null;
if ($isbnRaw !== '') {
    $isbn = normalize_isbn($isbnRaw);
    if ($isbn === null) json_out(["error" => "Mã ISBN không hợp lệ"], 400);
}
if ($qty < 1 || $qty > 500) json_out(["error" => "Số lượng phải từ 1 đến 500"], 400);
if ($year !== null && ($year < 1500 || $year > (int) date('Y') + 1)) $year = null;
if ($book_link !== '' && !preg_match('#^https?://#i', $book_link)) {
    json_out(["error" => "Link sách phải bắt đầu bằng http:// hoặc https://"], 400);
}
if ($cover_url !== '' && !preg_match('#^https://#i', $cover_url)) $cover_url = '';

try {
    $pdo->beginTransaction();

    // ISBN đã có -> thêm bản (khóa dòng để 2 người cùng nhập không bị lệch số)
    if ($isbn !== null) {
        $stmt = $pdo->prepare("SELECT id, title, total_qty FROM books WHERE isbn = ? FOR UPDATE");
        $stmt->execute([$isbn]);
        if ($existing = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdo->prepare("UPDATE books SET total_qty = total_qty + ?, available_qty = available_qty + ? WHERE id = ?")
                ->execute([$qty, $qty, $existing['id']]);
            $pdo->commit();
            json_out([
                "action"    => "added_copies",
                "book_id"   => (int) $existing['id'],
                "title"     => $existing['title'],
                "added"     => $qty,
                "total_qty" => (int) $existing['total_qty'] + $qty,
                "message"   => "Đã thêm $qty bản. Hiện có " . ((int) $existing['total_qty'] + $qty) . " bản.",
            ]);
        }
    }

    if ($title === '') {
        $pdo->rollBack();
        json_out(["error" => "Chưa có tên sách"], 400);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO books (isbn, title, author, publisher, publish_year, cover_url,
                            subject_code, shelf_location, book_link, read_access, total_qty, available_qty)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $isbn, $title, $author ?: null, $publisher ?: null, $year, $cover_url ?: null,
        $subject_code ?: null, $shelf_location ?: null, $book_link ?: null,
        $book_link ? $read_access : null, $qty, $qty,
    ]);
    $id = (int) $pdo->lastInsertId();
    $pdo->commit();

    json_out([
        "action"    => "created",
        "book_id"   => $id,
        "title"     => $title,
        "added"     => $qty,
        "total_qty" => $qty,
        "message"   => "Đã lưu sách mới ($qty bản).",
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e->getCode() === '23000') {
        json_out(["error" => "ISBN này vừa được thêm ở máy khác. Quét lại để thêm bản."], 409);
    }
    error_log('isbn_save: ' . $e->getMessage());
    json_out(["error" => "Không lưu được sách. Thử lại sau."], 500);
}
