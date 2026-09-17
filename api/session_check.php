<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    "loggedIn" => isset($_SESSION['admin_id']),
    "username" => $_SESSION['admin_username'] ?? null,
]);