<?php
// Mượn sách
// - Học sinh đã đăng nhập: mượn cho chính mình (member_id lấy từ session, không tin dữ liệu gửi lên)
// - Thủ thư đã đăng nhập: được mượn hộ, gửi kèm member_id
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

define('LOAN_DAYS', 90); // số ngày mượn mặc định (3 tháng)
define('MAX_ACTIVE_LOANS', 5); // mỗi sinh viên mượn tối đa 5 cuốn cùng lúc

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method không được hỗ trợ"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$book_id = intval($data['book_id'] ?? 0);

if (isset($_SESSION['member_id'])) {
    $member_id = (int) $_SESSION['member_id'];
} elseif (isset($_SESSION['admin_id'])) {
    // Thủ thư mượn hộ: nhận mã học sinh (hoặc member_id)
    $student_code = trim($data['student_code'] ?? '');
    if ($student_code !== '') {
        $s = $pdo->prepare("SELECT id FROM members WHERE student_code = ?");
        $s->execute([$student_code]);
        $member_id = (int) $s->fetchColumn();
    } else {
        $member_id = intval($data['member_id'] ?? 0);
    }
} else {
    http_response_code(401);
    echo json_encode(["error" => "Bạn cần đăng nhập để mượn sách"]);
    exit;
}

$memberStmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$memberStmt->execute([$member_id]);
$member = $memberStmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    http_response_code(404);
    echo json_encode(["error" => "Không tìm thấy thành viên"]);
    exit;
}
if ($member['status'] !== 'active') {
    http_response_code(400);
    echo json_encode(["error" => isset($_SESSION['member_id'])
        ? "Tài khoản của bạn đang bị khóa quyền mượn. Vui lòng liên hệ thủ thư."
        : "Sinh viên {$member['name']} ({$member['student_code']}) đang bị khóa quyền mượn."]);
    exit;
}

ensure_loan_time_columns($pdo);

try {
    $pdo->beginTransaction();

    // Khóa dòng sinh viên -> 1 người bấm mượn nhiều lần cùng lúc cũng không vượt quá giới hạn
    $pdo->prepare("SELECT id FROM members WHERE id = ? FOR UPDATE")->execute([$member_id]);

    // Chữ "Bạn" khi sinh viên tự mượn, tên sinh viên khi thủ thư mượn hộ
    $who = isset($_SESSION['member_id']) ? "Bạn" : "Sinh viên {$member['name']} ({$member['student_code']})";

    // Quy định 1: đang có sách quá hạn thì phải trả trước rồi mới được mượn tiếp
    $od = $pdo->prepare(
        "SELECT COUNT(*) FROM loans WHERE member_id = ? AND status <> 'returned' AND due_date < ?"
    );
    $od->execute([$member_id, date('Y-m-d')]);
    $overdue = (int) $od->fetchColumn();
    if ($overdue > 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(["error" => "$who đang có $overdue cuốn quá hạn. Cần trả sách quá hạn (và đóng phạt) trước khi mượn thêm."]);
        exit;
    }

    // Quy định 2: tối đa MAX_ACTIVE_LOANS cuốn cùng lúc
    $ac = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE member_id = ? AND status <> 'returned'");
    $ac->execute([$member_id]);
    $active = (int) $ac->fetchColumn();
    if ($active >= MAX_ACTIVE_LOANS) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(["error" => "$who đang mượn $active/" . MAX_ACTIVE_LOANS . " cuốn (tối đa " . MAX_ACTIVE_LOANS . "). Cần trả bớt sách trước khi mượn thêm."]);
        exit;
    }

    // Khóa dòng sách lại trong lúc xử lý -> 2 người bấm cùng lúc sẽ phải xếp hàng
    $bookStmt = $pdo->prepare("SELECT * FROM books WHERE id = ? FOR UPDATE");
    $bookStmt->execute([$book_id]);
    $book = $bookStmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["error" => "Không tìm thấy sách"]);
        exit;
    }

    // Không cho mượn trùng 1 cuốn khi chưa trả cuốn trước
    $dupStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM loans WHERE book_id = ? AND member_id = ? AND status <> 'returned'"
    );
    $dupStmt->execute([$book_id, $member_id]);
    if ($dupStmt->fetchColumn() > 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(["error" => "Bạn đang mượn cuốn này rồi, hãy trả trước khi mượn lại"]);
        exit;
    }

    // Trừ kho có điều kiện: chỉ trừ khi còn sách
    $upd = $pdo->prepare("UPDATE books SET available_qty = available_qty - 1 WHERE id = ? AND available_qty > 0");
    $upd->execute([$book_id]);
    if ($upd->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(["error" => "Sách hiện đã hết, vui lòng chọn sách khác"]);
        exit;
    }

    $borrow_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime("+" . LOAN_DAYS . " days"));

    $insert = $pdo->prepare(
        "INSERT INTO loans (book_id, member_id, borrow_date, borrow_at, due_date, status) VALUES (?, ?, ?, ?, ?, 'borrowed')"
    );
    $insert->execute([$book_id, $member_id, $borrow_date, date('Y-m-d H:i:s'), $due_date]);
    $loan_id = $pdo->lastInsertId();

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Có lỗi khi mượn sách, vui lòng thử lại"]);
    exit;
}

echo json_encode([
    "loan_id" => $loan_id,
    "due_date" => $due_date,
    "shelf_location" => $book['shelf_location'],
    "borrowing" => $active + 1,
    "max_loans" => MAX_ACTIVE_LOANS,
    "message" => "Mượn thành công lúc " . date('H:i') . " (" . ($active + 1) . "/" . MAX_ACTIVE_LOANS . " cuốn). Hạn trả: " . date('d/m/Y', strtotime($due_date)) . ". Vị trí kệ: {$book['shelf_location']}",
]);