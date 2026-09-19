<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

// Link (tài liệu điện tử / ảnh bìa) chỉ chấp nhận http:// hoặc https://
// -> chặn các link độc hại kiểu "javascript:..."
function clean_url(?string $url, string $label): string
{
    $url = trim((string) $url);
    if ($url === '') return '';
    if (strlen($url) > 500 || !preg_match('#^https?://#i', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(["error" => "$label phải là đường link bắt đầu bằng http:// hoặc https://"]);
        exit;
    }
    return $url;
}

// Tự thêm cột ảnh bìa nếu database cũ chưa có (chạy khi thêm / sửa sách)
function ensure_cover_column(PDO $pdo): void
{
    try {
        $pdo->exec("ALTER TABLE books ADD COLUMN IF NOT EXISTS cover_url VARCHAR(500) NULL");
        $pdo->exec("ALTER TABLE books ADD COLUMN IF NOT EXISTS read_access VARCHAR(10) NULL");
    } catch (PDOException $e) { /* bỏ qua */ }
}

// Mức đọc online của link sách: full = đọc toàn bộ, partial = đọc thử, none = chỉ có thông tin
function clean_access($v): ?string
{
    $v = (string) $v;
    return in_array($v, ['full', 'partial', 'none'], true) ? $v : null;
}

// Link do web tự tìm (Google Books / Open Library) -> được phép thay bằng link đọc được tốt hơn
const AUTO_LINK_SQL = "(book_link IS NULL OR book_link = '' OR book_link LIKE 'https://books.google.com/%'
                        OR book_link LIKE 'https://play.google.com/%' OR book_link LIKE 'https://openlibrary.org/%')";

if ($method === 'GET') {
    // Phải đăng nhập (học sinh hoặc thủ thư) mới xem được danh sách sách
    if (!isset($_SESSION['admin_id']) && !isset($_SESSION['member_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Bạn cần đăng nhập để xem sách"]);
        exit;
    }

    $search = trim($_GET['search'] ?? '');
    $subject = trim($_GET['subject'] ?? '');

    $sql = "SELECT * FROM books WHERE 1=1";
    $params = [];

    if ($subject !== '') {
        $sql .= " AND subject_code LIKE :subject";
        $params[':subject'] = "%$subject%";
    }
    if ($search !== '') {
        $sql .= " AND (title LIKE :search OR author LIKE :search2)";
        $params[':search'] = "%$search%";
        $params[':search2'] = "%$search%";
    }
    $sql .= " ORDER BY title ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($method === 'POST') {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Bạn cần đăng nhập với quyền thủ thư để thực hiện thao tác này"]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $title = trim($data['title'] ?? '');
    $author = trim($data['author'] ?? '');
    $subject_code = trim($data['subject_code'] ?? '');
    $book_link = clean_url($data['book_link'] ?? '', 'Link tài liệu điện tử');
    $cover_url = clean_url($data['cover_url'] ?? '', 'Link ảnh bìa');
    $shelf_location = trim($data['shelf_location'] ?? '');
    $total_qty = intval($data['total_qty'] ?? 0);

    if ($title === '' || $total_qty < 1) {
        http_response_code(400);
        echo json_encode(["error" => "Thiếu tên sách hoặc số lượng"]);
        exit;
    }

    ensure_cover_column($pdo);
    $stmt = $pdo->prepare(
        "INSERT INTO books (title, author, subject_code, book_link, cover_url, read_access, shelf_location, total_qty, available_qty)
         VALUES (:title, :author, :subject_code, :book_link, :cover_url, :read_access, :shelf_location, :total_qty, :total_qty)"
    );
    $stmt->execute([
        ':title' => $title,
        ':author' => $author,
        ':subject_code' => $subject_code,
        ':book_link' => $book_link,
        ':cover_url' => $cover_url !== '' ? $cover_url : null,
        ':read_access' => $book_link !== '' ? clean_access($data['read_access'] ?? '') : null,
        ':shelf_location' => $shelf_location,
        ':total_qty' => $total_qty,
    ]);

    echo json_encode(["id" => $pdo->lastInsertId()]);
    exit;
}

// ---------- SỬA SÁCH (thủ thư) ----------
// PUT {id, title, author, subject_code, book_link, cover_url, shelf_location, total_qty}
if ($method === 'PUT') {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Chỉ thủ thư mới được sửa sách"]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = intval($data['id'] ?? 0);

    // Chỉ cập nhật ảnh bìa / link sách: PUT {id, cover_url?, book_link?, read_access?, only_cover: true}
    // (dùng cho nút "Tự tìm ảnh bìa & link sách"). Không ghi đè cái đã có sẵn.
    if (!empty($data['only_cover'])) {
        $cover_url = clean_url($data['cover_url'] ?? '', 'Link ảnh bìa');
        $book_link = clean_url($data['book_link'] ?? '', 'Link sách');
        ensure_cover_column($pdo);
        if ($cover_url !== '') {
            $pdo->prepare("UPDATE books SET cover_url = ? WHERE id = ? AND (cover_url IS NULL OR cover_url = '')")
                ->execute([$cover_url, $id]);
        }
        if ($book_link !== '') {
            // Chỉ thay link trống hoặc link do web tự tìm; link thủ thư tự dán thì giữ nguyên
            $pdo->prepare("UPDATE books SET book_link = ?, read_access = ? WHERE id = ? AND " . AUTO_LINK_SQL)
                ->execute([$book_link, clean_access($data['read_access'] ?? '') ?? 'none', $id]);
        }
        echo json_encode(["success" => true]);
        exit;
    }

    $title = trim($data['title'] ?? '');
    $total_qty = intval($data['total_qty'] ?? 0);

    if ($id < 1 || $title === '' || $total_qty < 1) {
        http_response_code(400);
        echo json_encode(["error" => "Thiếu tên sách hoặc số lượng không hợp lệ"]);
        exit;
    }
    $book_link = clean_url($data['book_link'] ?? '', 'Link tài liệu điện tử');
    $cover_url = clean_url($data['cover_url'] ?? '', 'Link ảnh bìa');
    ensure_cover_column($pdo);

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT total_qty, available_qty FROM books WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$book) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(["error" => "Không tìm thấy sách"]);
            exit;
        }

        // Số cuốn đang được mượn không đổi -> số còn lại = tổng mới - đang mượn
        $borrowed = (int) $book['total_qty'] - (int) $book['available_qty'];
        if ($total_qty < $borrowed) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(["error" => "Không thể giảm số lượng xuống dưới $borrowed (số cuốn đang được mượn)"]);
            exit;
        }

        $pdo->prepare(
            "UPDATE books SET title = ?, author = ?, subject_code = ?, book_link = ?, cover_url = ?, read_access = ?, shelf_location = ?,
                              total_qty = ?, available_qty = ?
             WHERE id = ?"
        )->execute([
            $title,
            trim($data['author'] ?? ''),
            trim($data['subject_code'] ?? ''),
            $book_link,
            $cover_url !== '' ? $cover_url : null,
            $book_link !== '' ? clean_access($data['read_access'] ?? '') : null,
            trim($data['shelf_location'] ?? ''),
            $total_qty,
            $total_qty - $borrowed,
            $id,
        ]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Có lỗi khi sửa sách, vui lòng thử lại"]);
        exit;
    }

    echo json_encode(["success" => true, "message" => "Đã cập nhật sách"]);
    exit;
}

// ---------- XÓA SÁCH (thủ thư) ----------
// DELETE api/books.php?id=...
// Không cho xóa khi còn người đang mượn. Lịch sử mượn đã trả của cuốn này cũng bị xóa theo.
if ($method === 'DELETE') {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Chỉ thủ thư mới được xóa sách"]);
        exit;
    }

    $id = intval($_GET['id'] ?? 0);

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT id FROM books WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(["error" => "Không tìm thấy sách"]);
            exit;
        }

        $active = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE book_id = ? AND status <> 'returned'");
        $active->execute([$id]);
        $count = (int) $active->fetchColumn();
        if ($count > 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(["error" => "Sách đang có $count người mượn, cần trả hết trước khi xóa"]);
            exit;
        }

        $pdo->prepare("DELETE FROM loans WHERE book_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM books WHERE id = ?")->execute([$id]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Có lỗi khi xóa sách, vui lòng thử lại"]);
        exit;
    }

    echo json_encode(["success" => true, "message" => "Đã xóa sách"]);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method không được hỗ trợ"]);