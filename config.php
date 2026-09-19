<?php
// Múi giờ Việt Nam — để ngày mượn, hạn trả, tiền phạt tính đúng theo giờ VN
// (container Docker trên Render mặc định dùng giờ UTC, chậm 7 tiếng)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// ---------------------------------------------------------------
// 1) KẾT NỐI CƠ SỞ DỮ LIỆU
// Local (XAMPP): nếu không có biến môi trường, dùng giá trị mặc định bên dưới.
// Trên Render: đặt DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS trong phần Environment.
// ---------------------------------------------------------------
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'library_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

// Chứng chỉ SSL (cần khi kết nối SkySQL). Chạy XAMPP ở máy (localhost) thì tự bỏ qua SSL.
$caCertPath = __DIR__ . '/ca-cert.pem';
$isLocal = in_array($host, ['localhost', '127.0.0.1'], true);

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];

    if (!$isLocal && file_exists($caCertPath)) {
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

// Băm mật khẩu: bcrypt cost 10. Cost cao hơn (12) chậm gấp 4 lần, trên Render Free
// (khoảng 0,1 CPU) sẽ làm đăng nhập rất chậm khi nhiều người vào cùng lúc.
const PASSWORD_OPTIONS = ['cost' => 10];
function hash_password(string $plain): string
{
    return password_hash($plain, PASSWORD_BCRYPT, PASSWORD_OPTIONS);
}

// ---------------------------------------------------------------
// 2) PHIÊN ĐĂNG NHẬP LƯU TRONG DATABASE
// Mặc định PHP lưu phiên thành file trong máy chủ. Trên Render, mỗi lần deploy
// hoặc máy chủ "ngủ" rồi dậy, các file này mất -> mọi người bị đăng xuất.
// Lưu vào bảng `sessions` để phiên vẫn còn sau khi deploy / khởi động lại.
// ---------------------------------------------------------------
const SESSION_TTL = 8 * 3600; // phiên hết hạn sau 8 giờ không hoạt động

class DbSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    public function __construct(private PDO $pdo, private int $ttl) {}

    public function open(string $path, string $name): bool
    {
        // Tự tạo bảng nếu chưa có (chỉ tốn 1 truy vấn nhẹ ở lần đầu)
        try {
            $this->pdo->query("SELECT 1 FROM sessions LIMIT 1");
        } catch (PDOException $e) {
            $this->pdo->exec(
                "CREATE TABLE IF NOT EXISTS sessions (
                    id VARCHAR(128) NOT NULL PRIMARY KEY,
                    data MEDIUMTEXT NOT NULL,
                    last_access INT UNSIGNED NOT NULL,
                    INDEX idx_last_access (last_access)
                )"
            );
        }
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = ? AND last_access > ?");
        $stmt->execute([$id, time() - $this->ttl]);
        $data = $stmt->fetchColumn();
        return $data === false ? '' : $data;
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->pdo->prepare("REPLACE INTO sessions (id, data, last_access) VALUES (?, ?, ?)");
        return $stmt->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool
    {
        return $this->pdo->prepare("DELETE FROM sessions WHERE id = ?")->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_access < ?");
        $stmt->execute([time() - $this->ttl]);
        return $stmt->rowCount();
    }

    // Chế độ strict: chỉ chấp nhận mã phiên do máy chủ tạo ra
    public function validateId(string $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM sessions WHERE id = ? AND last_access > ?");
        $stmt->execute([$id, time() - $this->ttl]);
        return (bool) $stmt->fetchColumn();
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->pdo->prepare("UPDATE sessions SET last_access = ? WHERE id = ?")->execute([time(), $id]);
    }
}

if (session_status() === PHP_SESSION_NONE) {
    // Bảo vệ cookie đăng nhập:
    // - httponly: JavaScript không đọc được cookie (giảm thiệt hại nếu bị XSS)
    // - secure: chỉ gửi cookie qua HTTPS (Render đứng sau proxy nên xem thêm X-Forwarded-Proto)
    // - samesite=Lax: trang web khác không gửi kèm cookie khi gọi API của mình (chống CSRF)
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string) SESSION_TTL);
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_set_save_handler(new DbSessionHandler($pdo, SESSION_TTL), true);
    session_start();
}