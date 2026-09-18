<?php
// Quản lý thành viên — CHỈ thủ thư
// GET  : danh sách thành viên (không trả về mật khẩu), kèm số sách đang mượn / quá hạn / tiền phạt
// POST : thêm thành viên mới, kèm mật khẩu ban đầu
// PUT  : khóa / mở khóa quyền mượn {id, status: "active" | "locked"}
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Bạn cần đăng nhập với quyền thủ thư để thực hiện thao tác này"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Kèm số sách đang mượn, số phiếu quá hạn và tổng tiền phạt của từng sinh viên
    $stmt = $pdo->prepare(
        "SELECT m.id, m.student_code, m.name, m.class_name, m.contact, m.status,
                (m.password_hash IS NOT NULL) AS has_password,
                (SELECT COUNT(*) FROM loans l WHERE l.member_id = m.id AND l.status <> 'returned') AS borrowing,
                (SELECT COUNT(*) FROM loans l WHERE l.member_id = m.id AND l.status <> 'returned' AND l.due_date < ?) AS overdue,
                (SELECT COALESCE(SUM(l.fine), 0) FROM loans l WHERE l.member_id = m.id) AS total_fine
         FROM members m ORDER BY m.name ASC"
    );
    $stmt->execute([date('Y-m-d')]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// PUT {id, status: "active" | "locked"} — khóa / mở khóa quyền mượn sách
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    $status = $data['status'] ?? '';

    if ($id < 1 || !in_array($status, ['active', 'locked'], true)) {
        http_response_code(400);
        echo json_encode(["error" => "Dữ liệu không hợp lệ"]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE members SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    $check = $pdo->prepare("SELECT name FROM members WHERE id = ?");
    $check->execute([$id]);
    $name = $check->fetchColumn();
    if ($name === false) {
        http_response_code(404);
        echo json_encode(["error" => "Không tìm thấy sinh viên"]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => $status === 'locked' ? "Đã khóa quyền mượn của $name" : "Đã mở khóa cho $name",
    ]);
    exit;
}

if ($method === 'POST') {
    // Trong thực tế, dữ liệu thành viên nên được đồng bộ tự động từ API của trường.
    // Endpoint này dùng để thêm thủ công trong lúc chưa có kết nối đó.
    $data = json_decode(file_get_contents('php://input'), true);
    $student_code = trim($data['student_code'] ?? '');
    $name = trim($data['name'] ?? '');
    $class_name = trim($data['class_name'] ?? '');
    $contact = trim($data['contact'] ?? '');
    $password = $data['password'] ?? '';

    if ($student_code === '' || $name === '') {
        http_response_code(400);
        echo json_encode(["error" => "Thiếu mã học sinh hoặc tên"]);
        exit;
    }
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(["error" => "Mật khẩu ban đầu phải có ít nhất 6 ký tự"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO members (student_code, name, class_name, contact, status, password_hash)
             VALUES (?, ?, ?, ?, 'active', ?)"
        );
        $stmt->execute([$student_code, $name, $class_name, $contact, password_hash($password, PASSWORD_DEFAULT)]);
        echo json_encode(["id" => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(["error" => "Mã học sinh đã tồn tại"]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method không được hỗ trợ"]);