<?php
// Múi giờ Việt Nam — để ngày mượn, hạn trả, tiền phạt tính đúng theo giờ VN
// (container Docker trên Render mặc định dùng giờ UTC, chậm 7 tiếng)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Thông tin kết nối MySQL/MariaDB (SkySQL).
// Local (XAMPP): nếu không có biến môi trường, sẽ dùng giá trị mặc định bên dưới.
// Trên Render: đặt các biến môi trường DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
// trong phần Environment của Web Service, giá trị đó sẽ được ưu tiên dùng.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'library_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

// Đường dẫn tới file chứng chỉ SSL (cần khi kết nối SkySQL/MariaDB Cloud).
// Trên XAMPP local sẽ không có file này -> tự động bỏ qua SSL.
$caCertPath = __DIR__ . '/ca-cert.pem';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];

    if (file_exists($caCertPath)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $caCertPath;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    // Không in chi tiết lỗi ra ngoài (có thể lộ host/user database)
    error_log("DB connect error: " . $e->getMessage());
    die(json_encode(["error" => "Không thể kết nối cơ sở dữ liệu"]));
}