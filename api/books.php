<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Phải đăng nhập (học sinh hoặc thủ thư) mới xem được danh sách sách
    if (!isset($_SESSION['admin_id']) && !isset($_SESSION['member_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Bạn cần đăng nhập để xem sách"]);
        exit;
    }

    $search = trim($_GET['search'] ?? '');
    $subject = trim($_GET['subject'] ?? '');

    $sql = "SELECT * FROM books WHERE 1=1";
    $params = [];

    if ($subject !== '') {
        $sql .= " AND subject_code LIKE :subject";
        $params[':subject'] = "%$subject%";
    }
    if ($search !== '') {
        $sql .= " AND (title LIKE :search OR author LIKE :search2)";
        $params[':search'] = "%$search%";
        $params[':search2'] = "%$search%";
    }
    $sql .= " ORDER BY title ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($method === 'POST') {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Bạn cần đăng nhập với quyền thủ thư để thực hiện thao tác này"]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $title = trim($data['title'] ?? '');
    $author = trim($data['author'] ?? '');
    $subject_code = trim($data['subject_code'] ?? '');
    $book_link = trim($data['book_link'] ?? '');
    $shelf_location = trim($data['shelf_location'] ?? '');
    $total_qty = intval($data['total_qty'] ?? 0);

    if ($title === '' || $total_qty < 1) {
        http_response_code(400);
        echo json_encode(["error" => "Thiếu tên sách hoặc số lượng"]);
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO books (title, author, subject_code, book_link, shelf_location, total_qty, available_qty)
         VALUES (:title, :author, :subject_code, :book_link, :shelf_location, :total_qty, :total_qty)"
    );
    $stmt->execute([
        ':title' => $title,
        ':author' => $author,
        ':subject_code' => $subject_code,
        ':book_link' => $book_link,
        ':shelf_location' => $shelf_location,
        ':total_qty' => $total_qty,
    ]);

    echo json_encode(["id" => $pdo->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method không được hỗ trợ"]);