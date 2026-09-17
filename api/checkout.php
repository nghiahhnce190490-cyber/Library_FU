<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

define('LOAN_DAYS', 14); // số ngày mượn mặc định

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method không được hỗ trợ"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$book_id = intval($data['book_id'] ?? 0);
$member_id = intval($data['member_id'] ?? 0);

$bookStmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$bookStmt->execute([$book_id]);
$book = $bookStmt->fetch(PDO::FETCH_ASSOC);

$memberStmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$memberStmt->execute([$member_id]);
$member = $memberStmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    http_response_code(404);
    echo json_encode(["error" => "Không tìm thấy sách"]);
    exit;
}
if (!$member) {
    http_response_code(404);
    echo json_encode(["error" => "Không tìm thấy thành viên"]);
    exit;
}
if ($member['status'] !== 'active') {
    http_response_code(400);
    echo json_encode(["error" => "Tài khoản đang bị khóa mượn (do phạt hoặc nghỉ học)"]);
    exit;
}
if ($book['available_qty'] < 1) {
    http_response_code(400);
    echo json_encode(["error" => "Sách hiện đã hết, vui lòng chọn sách khác"]);
    exit;
}

$borrow_date = date('Y-m-d');
$due_date = date('Y-m-d', strtotime("+" . LOAN_DAYS . " days"));

$insert = $pdo->prepare(
    "INSERT INTO loans (book_id, member_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')"
);
$insert->execute([$book_id, $member_id, $borrow_date, $due_date]);
$loan_id = $pdo->lastInsertId();

$pdo->prepare("UPDATE books SET available_qty = available_qty - 1 WHERE id = ?")->execute([$book_id]);

echo json_encode([
    "loan_id" => $loan_id,
    "due_date" => $due_date,
    "shelf_location" => $book['shelf_location'],
    "message" => "Mượn thành công. Hạn trả: $due_date. Vị trí kệ: {$book['shelf_location']}",
]);
