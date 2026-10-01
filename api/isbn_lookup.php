<?php
// GET api/isbn_lookup.php?isbn=978...
// Trả về một trong ba trạng thái:
//   exists    : sách đã có trong thư viện -> chỉ cần thêm bản
//   found     : tra được trên mạng -> điền sẵn form
//   not_found : không ai biết -> thủ thư nhập tay (lần sau sẽ thành "exists")
require_once __DIR__ . '/isbn_common.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_out(["error" => "Method không được hỗ trợ"], 405);
}

$isbn = normalize_isbn($_GET['isbn'] ?? '');
if ($isbn === null) {
    json_out(["error" => "Mã ISBN không hợp lệ. Kiểm tra lại mã vạch hoặc chọn \"Sách không có ISBN\"."], 400);
}

// 1. Đã có trong thư viện?
$stmt = $pdo->prepare("SELECT id, isbn, title, author, publisher, publish_year, cover_url,
                              subject_code, shelf_location, book_link, total_qty, available_qty
                       FROM books WHERE isbn = ?");
$stmt->execute([$isbn]);
if ($book = $stmt->fetch(PDO::FETCH_ASSOC)) {
    json_out(["status" => "exists", "book" => $book]);
}

// online=0: chỉ tra trong CSDL, phần tra trên mạng để trình duyệt làm
// (máy chủ Render hay bị Google / Open Library chặn hoặc giới hạn lượt gọi)
if (($_GET['online'] ?? '1') === '0') {
    json_out(["status" => "not_found", "book" => ["isbn" => $isbn]]);
}

// 2. Google Books — cần GOOGLE_BOOKS_KEY trên Render (không có key Google trả lỗi 429 hết lượt)
$key = getenv('GOOGLE_BOOKS_KEY');
$g = fetch_json("https://www.googleapis.com/books/v1/volumes?maxResults=1&q=isbn:$isbn" . ($key ? "&key=" . urlencode($key) : ""));
if (!empty($g['items'][0]['volumeInfo'])) {
    $v = $g['items'][0]['volumeInfo'];
    $gid = urlencode($g['items'][0]['id'] ?? '');
    $view = $g['items'][0]['accessInfo']['viewability'] ?? '';
    $access = $view === 'ALL_PAGES' ? 'full' : ($view === 'PARTIAL' ? 'partial' : 'none');
    $title = trim(($v['title'] ?? '') . (!empty($v['subtitle']) ? ': ' . $v['subtitle'] : ''));
    if ($title !== '') {
        json_out([
            "status" => "found",
            "source" => "Google Books",
            "book" => [
                "isbn"         => $isbn,
                "title"        => $title,
                "author"       => implode(', ', $v['authors'] ?? []),
                "publisher"    => $v['publisher'] ?? '',
                "publish_year" => extract_year($v['publishedDate'] ?? ''),
                "cover_url"    => safe_https($v['imageLinks']['thumbnail'] ?? ''),
                "book_link"    => $gid ? "https://books.google.com/books?id=$gid" . ($access === 'none' ? '' : '&printsec=frontcover') : '',
                "read_access"  => $gid ? $access : '',
            ],
        ]);
    }
}

// 3. Open Library
$o = fetch_json("https://openlibrary.org/api/books?bibkeys=ISBN:$isbn&format=json&jscmd=data");
if (!empty($o["ISBN:$isbn"]['title'])) {
    $b = $o["ISBN:$isbn"];
    json_out([
        "status" => "found",
        "source" => "Open Library",
        "book" => [
            "isbn"         => $isbn,
            "title"        => trim($b['title'] . (!empty($b['subtitle']) ? ': ' . $b['subtitle'] : '')),
            "author"       => implode(', ', array_column($b['authors'] ?? [], 'name')),
            "publisher"    => $b['publishers'][0]['name'] ?? '',
            "publish_year" => extract_year($b['publish_date'] ?? ''),
            "cover_url"    => safe_https($b['cover']['medium'] ?? ''),
            "book_link"    => safe_https($b['url'] ?? ''),
            "read_access"  => !empty($b['url']) ? 'none' : '',
        ],
    ]);
}

// 4. Không tìm thấy ở đâu cả
json_out(["status" => "not_found", "book" => ["isbn" => $isbn]]);


// ---------- Hàm phụ ----------

// Gọi API ngoài, tối đa 6 giây. Lỗi mạng thì trả null để chuyển sang nguồn tiếp theo.
function fetch_json(string $url): ?array {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'LibGo/1.0 (thuvienfpt.id.vn)',
        ]);
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code !== 200) return null;
    } else {
        $ctx = stream_context_create(['http' => [
            'timeout' => 6,
            'header'  => "User-Agent: LibGo/1.0 (thuvienfpt.id.vn)\r\n",
        ]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function extract_year(string $date): ?int {
    return preg_match('/\b(1[5-9]\d{2}|20\d{2})\b/', $date, $m) ? (int) $m[1] : null;
}

// Chỉ nhận link ảnh https (Google hay trả http://) để tránh cảnh báo nội dung không an toàn.
function safe_https(string $url): string {
    $url = preg_replace('#^http://#i', 'https://', trim($url));
    return preg_match('#^https://#i', $url) ? $url : '';
}
