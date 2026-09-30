<?php
// Quy tắc nghiệp vụ dùng chung cho trợ lý AI (assistant.php) và bước xác nhận (assistant_confirm.php).
// Đặt file này trong thư mục api/ cùng với các API khác.

const RENEW_DAYS = 14; // số ngày cộng thêm mỗi lần gia hạn — chỉnh theo quy định thư viện
const MAX_RENEWS = 1;  // số lần gia hạn tối đa cho mỗi phiếu

// Quy tắc mượn — PHẢI khớp với api/checkout.php của bạn. Chỉnh 2 số này nếu checkout.php dùng số khác.
const CHECKOUT_LOAN_DAYS = 90;   // số ngày được mượn
const MAX_ACTIVE_LOANS = 10;     // số sách được mượn cùng lúc tối đa

/**
 * Lấy ID sinh viên đang đăng nhập.
 * Khớp với api/auth_login.php: học sinh có $_SESSION['member_id'],
 * thủ thư có $_SESSION['admin_id']. Chỉ tính là sinh viên khi có member_id
 * và KHÔNG phải phiên thủ thư.
 */
function current_student_id(): ?int
{
    if (!empty($_SESSION['member_id']) && empty($_SESSION['admin_id'])) {
        return (int) $_SESSION['member_id'];
    }
    return null;
}

function member_is_active(PDO $pdo, int $memberId): bool
{
    $st = $pdo->prepare("SELECT status FROM members WHERE id = ?");
    $st->execute([$memberId]);
    return $st->fetchColumn() === 'active';
}

/** Kiểm tra có được gia hạn phiếu không. Trả về ['ok'=>true, ...] hoặc ['ok'=>false, 'reason'=>...] */
function check_renew(PDO $pdo, int $memberId, int $loanId, bool $lock = false): array
{
    $sql = "SELECT loans.*, books.title FROM loans
            JOIN books ON books.id = loans.book_id
            WHERE loans.id = ? AND loans.member_id = ?" . ($lock ? " FOR UPDATE" : "");
    $st = $pdo->prepare($sql);
    $st->execute([$loanId, $memberId]);
    $loan = $st->fetch(PDO::FETCH_ASSOC);

    if (!$loan) {
        return ['ok' => false, 'reason' => 'Không tìm thấy phiếu mượn này của bạn'];
    }
    if ($loan['status'] === 'returned') {
        return ['ok' => false, 'reason' => 'Phiếu này đã trả sách rồi'];
    }
    if ($loan['status'] === 'overdue' || $loan['due_date'] < date('Y-m-d')) {
        return ['ok' => false, 'reason' => 'Phiếu đã quá hạn nên không gia hạn được, bạn vui lòng mang sách đến quầy'];
    }
    if ((int) $loan['renew_count'] >= MAX_RENEWS) {
        return ['ok' => false, 'reason' => 'Phiếu này đã hết lượt gia hạn'];
    }
    if (!member_is_active($pdo, $memberId)) {
        return ['ok' => false, 'reason' => 'Tài khoản đang bị khóa mượn'];
    }
    $w = $pdo->prepare("SELECT COUNT(*) FROM waitlist WHERE book_id = ? AND status = 'waiting'");
    $w->execute([$loan['book_id']]);
    if ((int) $w->fetchColumn() > 0) {
        return ['ok' => false, 'reason' => 'Đang có bạn khác chờ cuốn này nên không gia hạn được'];
    }

    $newDue = date('Y-m-d', strtotime($loan['due_date'] . ' +' . RENEW_DAYS . ' days'));
    return ['ok' => true, 'loan' => $loan, 'new_due_date' => $newDue];
}

/** Kiểm tra có được vào hàng chờ sách không. */
function check_waitlist(PDO $pdo, int $memberId, int $bookId): array
{
    $st = $pdo->prepare("SELECT id, title, available_qty FROM books WHERE id = ?");
    $st->execute([$bookId]);
    $book = $st->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        return ['ok' => false, 'reason' => 'Không tìm thấy sách'];
    }
    if ((int) $book['available_qty'] > 0) {
        return ['ok' => false, 'reason' => 'Sách đang còn, bạn có thể mượn ngay không cần chờ'];
    }
    if (!member_is_active($pdo, $memberId)) {
        return ['ok' => false, 'reason' => 'Tài khoản đang bị khóa mượn'];
    }
    $st = $pdo->prepare("SELECT COUNT(*) FROM waitlist WHERE book_id = ? AND member_id = ? AND status = 'waiting'");
    $st->execute([$bookId, $memberId]);
    if ((int) $st->fetchColumn() > 0) {
        return ['ok' => false, 'reason' => 'Bạn đã ở trong hàng chờ cuốn này rồi'];
    }
    $st = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE book_id = ? AND member_id = ? AND status IN ('borrowed','overdue')");
    $st->execute([$bookId, $memberId]);
    if ((int) $st->fetchColumn() > 0) {
        return ['ok' => false, 'reason' => 'Bạn đang mượn cuốn này rồi'];
    }
    $st = $pdo->prepare("SELECT COUNT(*) FROM waitlist WHERE book_id = ? AND status = 'waiting'");
    $st->execute([$bookId]);
    $position = (int) $st->fetchColumn() + 1;

    return ['ok' => true, 'book' => $book, 'position' => $position];
}

/** Kiểm tra có được mượn sách không. */
function check_checkout(PDO $pdo, int $memberId, int $bookId, bool $lock = false): array
{
    $sql = "SELECT id, title, available_qty, shelf_location FROM books WHERE id = ?" . ($lock ? " FOR UPDATE" : "");
    $st = $pdo->prepare($sql);
    $st->execute([$bookId]);
    $book = $st->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        return ['ok' => false, 'reason' => 'Không tìm thấy sách'];
    }
    if ((int) $book['available_qty'] < 1) {
        return ['ok' => false, 'reason' => 'Sách đang hết, bạn có thể vào hàng chờ'];
    }
    if (!member_is_active($pdo, $memberId)) {
        return ['ok' => false, 'reason' => 'Tài khoản đang bị khóa mượn'];
    }
    // Đang mượn chính cuốn này rồi?
    $st = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE book_id = ? AND member_id = ? AND status IN ('borrowed','overdue')");
    $st->execute([$bookId, $memberId]);
    if ((int) $st->fetchColumn() > 0) {
        return ['ok' => false, 'reason' => 'Bạn đang mượn cuốn này rồi'];
    }
    // Có sách quá hạn thì phải trả trước
    $st = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE member_id = ? AND status = 'overdue'");
    $st->execute([$memberId]);
    if ((int) $st->fetchColumn() > 0) {
        return ['ok' => false, 'reason' => 'Bạn đang có sách quá hạn, vui lòng trả sách đó trước khi mượn thêm'];
    }
    // Đã đạt số sách mượn tối đa
    $st = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE member_id = ? AND status IN ('borrowed','overdue')");
    $st->execute([$memberId]);
    if ((int) $st->fetchColumn() >= MAX_ACTIVE_LOANS) {
        return ['ok' => false, 'reason' => 'Bạn đã mượn tối đa ' . MAX_ACTIVE_LOANS . ' cuốn, vui lòng trả bớt trước khi mượn thêm'];
    }

    $due_date = date('Y-m-d', strtotime('+' . CHECKOUT_LOAN_DAYS . ' days'));
    return ['ok' => true, 'book' => $book, 'due_date' => $due_date];
}