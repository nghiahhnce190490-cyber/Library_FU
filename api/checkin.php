<?php
// Trả sách — CHỈ thủ thư được xác nhận (nhận sách tận tay rồi mới bấm)
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

define('FINE_PER_DAY', 2000); // VND phạt mỗi ngày trễ

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method không được hỗ trợ"]);
    exit;
}

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Chỉ thủ thư mới được xác nhận trả sách"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$loan_id = intval($data['loan_id'] ?? 0);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ? FOR UPDATE");
    $stmt->execute([$loan_id]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loan) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["error" => "Không tìm thấy phiếu mượn"]);
        exit;
    }
    if ($loan['status'] === 'returned') {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(["error" => "Phiếu này đã được trả trước đó"]);
        exit;
    }

    $today = new DateTime('today');
    $due = new DateTime($loan['due_date']);
    $fine = 0;
    if ($today > $due) {
        $lateDays = $due->diff($today)->days;
        $fine = $lateDays * FINE_PER_DAY;
    }
    $return_date = $today->format('Y-m-d');

    $pdo->prepare("UPDATE loans SET return_date = ?, status = 'returned', fine = ? WHERE id = ?")
        ->execute([$return_date, $fine, $loan_id]);

    $pdo->prepare("UPDATE books SET available_qty = LEAST(available_qty + 1, total_qty) WHERE id = ?")
        ->execute([$loan['book_id']]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Có lỗi khi trả sách, vui lòng thử lại"]);
    exit;
}

echo json_encode([
    "success" => true,
    "fine" => $fine,
    "message" => $fine > 0
        ? "Trả trễ hạn. Tiền phạt: " . number_format($fine) . " VND"
        : "Trả sách đúng hạn.",
]);
