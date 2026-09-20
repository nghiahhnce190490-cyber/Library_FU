<?php
// Hồ sơ cá nhân của sinh viên đang đăng nhập (sinh viên tự xem / tự sửa)
// GET : thông tin + thống kê mượn sách của chính mình
// PUT : {name, class_name, contact}  -> sửa họ tên, lớp/khoa, email
// Mã học sinh (tên đăng nhập) và trạng thái khóa KHÔNG sửa được ở đây.
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['member_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Bạn cần đăng nhập bằng tài khoản sinh viên"]);
    exit;
}
$memberId = (int) $_SESSION['member_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare(
        "SELECT m.student_code, m.name, m.class_name, m.contact, m.status,
                (SELECT COUNT(*) FROM loans l WHERE l.member_id = m.id) AS total_loans,
                (SELECT COUNT(*) FROM loans l WHERE l.member_id = m.id AND l.status <> 'returned') AS borrowing,
                (SELECT COUNT(*) FROM loans l WHERE l.member_id = m.id AND l.status <> 'returned' AND l.due_date < ?) AS overdue,
                (SELECT COALESCE(SUM(l.fine), 0) FROM loans l WHERE l.member_id = m.id) AS total_fine
         FROM members m WHERE m.id = ?"
    );
    $stmt->execute([date('Y-m-d'), $memberId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(["error" => "Không tìm thấy tài khoản"]);
        exit;
    }
    echo json_encode($row);
    exit;
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = trim($data['name'] ?? '');
    $class = trim($data['class_name'] ?? '');
    $contact = trim($data['contact'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) {
        http_response_code(400);
        echo json_encode(["error" => "Họ tên không được để trống (tối đa 100 ký tự)"]);
        exit;
    }
    if (mb_strlen($class) > 100) {
        http_response_code(400);
        echo json_encode(["error" => "Lớp / khoa quá dài (tối đa 100 ký tự)"]);
        exit;
    }
    // Email dùng để lấy lại mật khẩu -> bắt buộc đúng định dạng
    if ($contact === '' || !filter_var($contact, FILTER_VALIDATE_EMAIL) || strlen($contact) > 150) {
        http_response_code(400);
        echo json_encode(["error" => "Vui lòng nhập email hợp lệ (dùng để lấy lại mật khẩu khi quên)"]);
        exit;
    }

    $pdo->prepare("UPDATE members SET name = ?, class_name = ?, contact = ? WHERE id = ?")
        ->execute([$name, $class, $contact, $memberId]);
    $_SESSION['member_name'] = $name; // để thanh trên cùng hiện tên mới ngay

    echo json_encode(["success" => true, "message" => "Đã lưu thông tin cá nhân"]);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method không được hỗ trợ"]);