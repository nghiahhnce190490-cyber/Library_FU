<?php
// GET api/book_options.php -> danh sách mã môn và vị trí kệ đã dùng,
// để gợi ý khi nhập (tránh "Kệ A1" / "ke a1" thành hai kệ khác nhau).
require_once __DIR__ . '/isbn_common.php';
require_admin();

$subjects = $pdo->query("SELECT DISTINCT subject_code FROM books
                         WHERE subject_code IS NOT NULL AND subject_code <> ''
                         ORDER BY subject_code")->fetchAll(PDO::FETCH_COLUMN);
$shelves  = $pdo->query("SELECT DISTINCT shelf_location FROM books
                         WHERE shelf_location IS NOT NULL AND shelf_location <> ''
                         ORDER BY shelf_location")->fetchAll(PDO::FETCH_COLUMN);

json_out(["subjects" => $subjects, "shelves" => $shelves]);
