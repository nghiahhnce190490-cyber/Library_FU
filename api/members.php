<?php
// Quản lý thành viên — CHỈ thủ thư
// GET  : danh sách thành viên (không trả về mật khẩu), kèm số sách đang mượn / quá hạn / tiền phạt
// POST : thêm thành viên mới, kèm mật khẩu ban đầu
// PUT  : khóa / mở khóa quyền mượn {id, status} hoặc sửa thông tin {id, student_code, name, class_name, contact}
// DELETE ?id= : xóa sinh viên (không được khi còn sách chưa trả)
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

// PUT có 2 kiểu:
//  - {id, status: "active" | "locked"}                     -> khóa / mở khóa quyền mượn
//  - {id, student_code, name, class_name, contact}         -> sửa thông tin sinh viên
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);

    // ---- Sửa thông tin ----
    if (!array_key_exists('status', $data ?? [])) {
        $student_code = trim($data['student_code'] ?? '');
        $name = trim($data['name'] ?? '');
        if ($id < 1 || $student_code === '' || $name === '') {
            http_response_code(400);
            echo json_encode(["error" => "Thiếu mã học sinh hoặc họ tên"]);
            exit;
        }

        $dup = $pdo->prepare("SELECT COUNT(*) FROM members WHERE student_code = ? AND id <> ?");
        $dup->execute([$student_code, $id]);
        if ($dup->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(["error" => "Mã học sinh $student_code đã được dùng cho sinh viên khác"]);
            exit;
        }

        $exists = $pdo->prepare("SELECT COUNT(*) FROM members WHERE id = ?");
        $exists->execute([$id]);
        if ($exists->fetchColumn() == 0) {
            http_response_code(404);
            echo json_encode(["error" => "Không tìm thấy sinh viên"]);
            exit;
        }

        $pdo->prepare("UPDATE members SET student_code = ?, name = ?, class_name = ?, contact = ? WHERE id = ?")
            ->execute([$student_code, $name, trim($data['class_name'] ?? ''), trim($data['contact'] ?? ''), $id]);

        echo json_encode(["success" => true, "message" => "Đã cập nhật thông tin $name"]);
        exit;
    }

    // ---- Khóa / mở khóa ----
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

// DELETE api/members.php?id=... — xóa sinh viên
// Không cho xóa khi sinh viên còn sách chưa trả. Lịch sử mượn đã trả cũng bị xóa theo.
if ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT name FROM members WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $name = $stmt->fetchColumn();
        if ($name === false) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(["error" => "Không tìm thấy sinh viên"]);
            exit;
        }

        $active = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE member_id = ? AND status <> 'returned'");
        $active->execute([$id]);
        $count = (int) $active->fetchColumn();
        if ($count > 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(["error" => "$name còn $count cuốn chưa trả, cần trả hết trước khi xóa"]);
            exit;
        }

        $pdo->prepare("DELETE FROM loans WHERE member_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM members WHERE id = ?")->execute([$id]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Có lỗi khi xóa sinh viên, vui lòng thử lại"]);
        exit;
    }

    echo json_encode(["success" => true, "message" => "Đã xóa sinh viên $name"]);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method không được hỗ trợ"]);