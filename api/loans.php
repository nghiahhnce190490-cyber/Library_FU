<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

// Tự động đánh dấu quá hạn cho các phiếu chưa trả mà đã qua hạn
$today = date('Y-m-d');
$pdo->prepare("UPDATE loans SET status = 'overdue' WHERE status = 'borrowed' AND due_date < ?")
    ->execute([$today]);

$status = trim($_GET['status'] ?? '');

$sql = "SELECT loans.*, books.title AS book_title, books.shelf_location,
               members.name AS member_name, members.student_code
        FROM loans
        JOIN books ON loans.book_id = books.id
        JOIN members ON loans.member_id = members.id";
$params = [];
if ($status !== '') {
    $sql .= " WHERE loans.status = ?";
    $params[] = $status;
}
$sql .= " ORDER BY loans.borrow_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
