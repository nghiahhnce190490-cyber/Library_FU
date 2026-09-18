<?php
// Danh sách phiếu mượn
// - Thủ thư: xem tất cả
// - Học sinh: chỉ xem phiếu của chính mình
// - Chưa đăng nhập: không xem được (tránh lộ tên/mã học sinh)
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$isAdmin = isset($_SESSION['admin_id']);
$memberId = $_SESSION['member_id'] ?? null;

if (!$isAdmin && !$memberId) {
    http_response_code(401);
    echo json_encode(["error" => "Bạn cần đăng nhập để xem phiếu mượn"]);
    exit;
}

// Tự động đánh dấu quá hạn cho các phiếu chưa trả mà đã qua hạn
$today = date('Y-m-d');
$pdo->prepare("UPDATE loans SET status = 'overdue' WHERE status = 'borrowed' AND due_date < ?")
    ->execute([$today]);

// status có thể là 1 giá trị hoặc nhiều giá trị cách nhau bằng dấu phẩy: borrowed,overdue
$allowed = ['borrowed', 'overdue', 'returned'];
$statuses = array_values(array_intersect(
    array_map('trim', explode(',', $_GET['status'] ?? '')),
    $allowed
));

$sql = "SELECT loans.*, books.title AS book_title, books.shelf_location,
               members.name AS member_name, members.student_code
        FROM loans
        JOIN books ON loans.book_id = books.id
        JOIN members ON loans.member_id = members.id
        WHERE 1=1";
$params = [];

if (!$isAdmin) {
    $sql .= " AND loans.member_id = ?";
    $params[] = $memberId;
}
if ($statuses) {
    $sql .= " AND loans.status IN (" . implode(',', array_fill(0, count($statuses), '?')) . ")";
    $params = array_merge($params, $statuses);
}
$sql .= " ORDER BY loans.borrow_date DESC, loans.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
