<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    // Thủ thư (giữ nguyên 2 key cũ để không phá code cũ)
    "loggedIn" => isset($_SESSION['admin_id']),
    "username" => $_SESSION['admin_username'] ?? null,
    // Học sinh
    "student" => isset($_SESSION['member_id']) ? [
        "id" => $_SESSION['member_id'],
        "name" => $_SESSION['member_name'],
        "student_code" => $_SESSION['student_code'],
    ] : null,
]);
