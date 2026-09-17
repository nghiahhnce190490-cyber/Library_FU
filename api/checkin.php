<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

define('FINE_PER_DAY', 2000); // VND phạt mỗi ngày trễ

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method không được hỗ trợ"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$loan_id = intval($data['loan_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loan) {
    http_response_code(404);
    echo json_encode(["error" => "Không tìm thấy phiếu mượn"]);
    exit;
}
if ($loan['status'] === 'returned') {
    http_response_code(400);
    echo json_encode(["error" => "Phiếu này đã được trả trước đó"]);
    exit;
}

$return_date = date('Y-m-d');
$fine = 0;
if ($return_date > $loan['due_date']) {
    $lateDays = (int) round((strtotime($return_date) - strtotime($loan['due_date'])) / 86400);
    $fine = $lateDays * FINE_PER_DAY;
}

$pdo->prepare("UPDATE loans SET return_date = ?, status = 'returned', fine = ? WHERE id = ?")
    ->execute([$return_date, $fine, $loan_id]);

$pdo->prepare("UPDATE books SET available_qty = available_qty + 1 WHERE id = ?")
    ->execute([$loan['book_id']]);

echo json_encode([
    "success" => true,
    "fine" => $fine,
    "message" => $fine > 0
        ? "Trả trễ hạn. Tiền phạt: " . number_format($fine) . " VND"
        : "Trả sách đúng hạn.",
]);
