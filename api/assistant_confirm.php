<?php
// Thực hiện đề xuất của trợ lý AI SAU KHI sinh viên bấm "Xác nhận".
// Kiểm tra lại toàn bộ quy tắc trong transaction, vì tình trạng có thể đã đổi kể từ lúc AI đề xuất.
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/library_rules.php';
header('Content-Type: application/json; charset=utf-8');

function out(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(405, ['error' => 'Method không được hỗ trợ']);
}
$memberId = current_student_id();
if (!$memberId) {
    out(401, ['error' => 'Bạn cần đăng nhập bằng tài khoản sinh viên']);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$actionId = (int) ($input['action_id'] ?? 0);

$pdo->beginTransaction();
try {
    // Chỉ lấy đề xuất của CHÍNH sinh viên này
    $st = $pdo->prepare("SELECT * FROM ai_pending_actions WHERE id = ? AND member_id = ? FOR UPDATE");
    $st->execute([$actionId, $memberId]);
    $act = $st->fetch(PDO::FETCH_ASSOC);

    if (!$act || $act['status'] !== 'pending') {
        throw new InvalidArgumentException('Đề xuất này không còn hiệu lực');
    }
    if ($act['expires_at'] < date('Y-m-d H:i:s')) {
        $pdo->prepare("UPDATE ai_pending_actions SET status = 'expired' WHERE id = ?")->execute([$actionId]);
        $pdo->commit();
        out(400, ['error' => 'Đề xuất đã hết hạn, bạn hỏi lại trợ lý nhé']);
    }

    $payload = json_decode($act['payload'], true) ?: [];

    if ($act['action'] === 'renew_loan') {
        $chk = check_renew($pdo, $memberId, (int) ($payload['loan_id'] ?? 0), true); // khóa dòng phiếu mượn
        if (!$chk['ok']) throw new InvalidArgumentException($chk['reason']);
        $pdo->prepare("UPDATE loans SET due_date = ?, renew_count = renew_count + 1 WHERE id = ? AND member_id = ?")
            ->execute([$chk['new_due_date'], $chk['loan']['id'], $memberId]);
        $message = 'Đã gia hạn "' . $chk['loan']['title'] . '" tới ngày ' . date('d/m/Y', strtotime($chk['new_due_date']));
    } elseif ($act['action'] === 'join_waitlist') {
        $bookId = (int) ($payload['book_id'] ?? 0);
        $pdo->prepare("SELECT id FROM books WHERE id = ? FOR UPDATE")->execute([$bookId]); // tránh đăng ký trùng khi bấm 2 lần
        $chk = check_waitlist($pdo, $memberId, $bookId);
        if (!$chk['ok']) throw new InvalidArgumentException($chk['reason']);
        $pdo->prepare("INSERT INTO waitlist (book_id, member_id, status) VALUES (?, ?, 'waiting')")
            ->execute([$bookId, $memberId]);
        $message = 'Đã vào hàng chờ "' . $chk['book']['title'] . '", bạn đang ở vị trí số ' . $chk['position'];
    } elseif ($act['action'] === 'checkout_book') {
        $bookId = (int) ($payload['book_id'] ?? 0);
        $chk = check_checkout($pdo, $memberId, $bookId, true); // khóa dòng sách khi kiểm tra
        if (!$chk['ok']) throw new InvalidArgumentException($chk['reason']);
        $borrow_date = date('Y-m-d');
        $pdo->prepare("INSERT INTO loans (book_id, member_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')")
            ->execute([$bookId, $memberId, $borrow_date, $chk['due_date']]);
        $pdo->prepare("UPDATE books SET available_qty = available_qty - 1 WHERE id = ?")->execute([$bookId]);
        $shelf = $chk['book']['shelf_location'] ?: 'chưa cập nhật';
        $message = 'Đã mượn "' . $chk['book']['title'] . '". Hạn trả ' . date('d/m/Y', strtotime($chk['due_date'])) . ', lấy sách ở kệ ' . $shelf;
    } else {
        throw new InvalidArgumentException('Loại đề xuất không hợp lệ');
    }

    $pdo->prepare("UPDATE ai_pending_actions SET status = 'done' WHERE id = ?")->execute([$actionId]);
    $pdo->commit();
    out(200, ['success' => true, 'message' => $message]);
} catch (InvalidArgumentException $e) {
    $pdo->rollBack();
    out(400, ['error' => $e->getMessage()]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Confirm error: ' . $e->getMessage());
    out(500, ['error' => 'Không thực hiện được, bạn thử lại sau']);
}