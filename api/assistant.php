<?php
// Trợ lý AI LibGo (dùng Gemini API): nhận câu hỏi của sinh viên, cho AI gọi công cụ tra dữ liệu thật, trả câu trả lời.
// AI KHÔNG bao giờ tự ghi dữ liệu: gia hạn / vào hàng chờ chỉ tạo "đề xuất",
// sinh viên bấm Xác nhận thì assistant_confirm.php mới thực hiện.
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/library_rules.php';
header('Content-Type: application/json; charset=utf-8');

// Tên mô hình: đặt biến môi trường GEMINI_MODEL để đổi mà không sửa code.
// Xem danh sách mô hình đang có trong Google AI Studio; nên chọn dòng "flash" (nhanh, rẻ).
// Danh sách mô hình theo thứ tự ưu tiên. Khi một mô hình hết quota (429),
// trợ lý tự chuyển sang mô hình kế tiếp còn quota. Đặt biến môi trường GEMINI_MODELS
// (các tên cách nhau bởi dấu phẩy) để đổi mà không sửa code.
define('AI_MODELS', array_values(array_filter(array_map('trim', explode(
    ',',
    getenv('GEMINI_MODELS') ?: getenv('GEMINI_MODEL') ?: 'gemini-3.1-flash-lite,gemini-2.5-flash-lite,gemini-2.5-flash,gemini-3.5-flash'
)))));
const AI_DAILY_LIMIT = 20;   // số câu hỏi tối đa mỗi sinh viên mỗi ngày
const AI_MAX_ROUNDS = 5;     // số vòng gọi công cụ tối đa cho một câu hỏi
const ACTION_TTL_MIN = 10;   // đề xuất hết hạn sau 10 phút

function out(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(405, ['error' => 'Method không được hỗ trợ']);
}

// ---------- 1. Kiểm tra đăng nhập, cấu hình, giới hạn lượt ----------
$memberId = current_student_id();
if (!$memberId) {
    out(401, ['error' => 'Bạn cần đăng nhập bằng tài khoản sinh viên để dùng trợ lý']);
}
session_write_close(); // nhả khóa session để các request khác không phải chờ AI

$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    out(503, ['error' => 'Trợ lý AI chưa được bật trên máy chủ này']);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$message = trim((string) ($input['message'] ?? ''));
if ($message === '' || mb_strlen($message) > 500) {
    out(400, ['error' => 'Câu hỏi trống hoặc dài quá 500 ký tự']);
}

$today = date('Y-m-d');
$pdo->prepare("INSERT INTO ai_usage (member_id, usage_date, count) VALUES (?, ?, 1)
               ON DUPLICATE KEY UPDATE count = count + 1")->execute([$memberId, $today]);
$st = $pdo->prepare("SELECT count FROM ai_usage WHERE member_id = ? AND usage_date = ?");
$st->execute([$memberId, $today]);
if ((int) $st->fetchColumn() > AI_DAILY_LIMIT) {
    out(429, ['error' => 'Bạn đã dùng hết ' . AI_DAILY_LIMIT . ' câu hỏi hôm nay. Bạn vẫn tìm sách được bằng ô tìm kiếm.']);
}

// ---------- 2. Lịch sử hội thoại (chỉ nhận text, không nhận kết quả công cụ từ trình duyệt) ----------
// Gemini dùng vai trò "user" và "model"; mỗi tin là một mảng "parts".
function build_contents(array $history, string $message): array
{
    $raw = [];
    foreach (array_slice($history, -10) as $h) {
        if (!is_array($h)) continue;
        $role = $h['role'] ?? '';
        $text = $h['text'] ?? '';
        if (in_array($role, ['user', 'assistant'], true) && is_string($text) && trim($text) !== '') {
            $raw[] = ['role' => $role === 'assistant' ? 'model' : 'user', 'text' => mb_substr($text, 0, 1000)];
        }
    }
    $raw[] = ['role' => 'user', 'text' => $message];

    // Gộp các tin liền nhau cùng vai trò, bắt đầu bằng tin của sinh viên
    $out = [];
    foreach ($raw as $m) {
        $last = count($out) - 1;
        if ($last >= 0 && $out[$last]['role'] === $m['role']) {
            $out[$last]['parts'][0]['text'] .= "\n" . $m['text'];
        } else {
            $out[] = ['role' => $m['role'], 'parts' => [['text' => $m['text']]]];
        }
    }
    while ($out && $out[0]['role'] !== 'user') array_shift($out);
    return $out;
}
$contents = build_contents($input['history'] ?? [], $message);

// ---------- 3. Khai báo công cụ cho AI ----------
$tools = [[
    'functionDeclarations' => [
        [
            'name' => 'search_books',
            'description' => 'Tìm sách trong kho thư viện theo từ khóa (tên sách, tác giả) và/hoặc mã môn học. Trả về số bản còn, vị trí kệ và số người đang chờ.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'keyword' => ['type' => 'string', 'description' => 'Từ khóa trong tên sách hoặc tác giả, có thể để trống'],
                    'subject_code' => ['type' => 'string', 'description' => 'Mã môn học, ví dụ MAS291, có thể để trống'],
                ],
            ],
        ],
        [
            'name' => 'my_loans',
            'description' => 'Xem các sách sinh viên đang mượn (chưa trả), hạn trả và số lần đã gia hạn. Không cần tham số.',
        ],
        [
            'name' => 'renew_loan',
            'description' => 'Đề xuất gia hạn một phiếu mượn của sinh viên. Công cụ KHÔNG gia hạn ngay mà tạo đề xuất; sinh viên phải bấm nút Xác nhận.',
            'parameters' => [
                'type' => 'object',
                'properties' => ['loan_id' => ['type' => 'integer', 'description' => 'ID phiếu mượn lấy từ my_loans']],
                'required' => ['loan_id'],
            ],
        ],
        [
            'name' => 'join_waitlist',
            'description' => 'Đề xuất đăng ký hàng chờ cho một cuốn sách đang hết. Công cụ KHÔNG đăng ký ngay mà tạo đề xuất; sinh viên phải bấm nút Xác nhận.',
            'parameters' => [
                'type' => 'object',
                'properties' => ['book_id' => ['type' => 'integer', 'description' => 'ID sách lấy từ search_books']],
                'required' => ['book_id'],
            ],
        ],
        [
            'name' => 'checkout_book',
            'description' => 'Đề xuất cho sinh viên mượn một cuốn sách đang còn. Công cụ KHÔNG mượn ngay mà tạo đề xuất; sinh viên phải bấm nút Xác nhận.',
            'parameters' => [
                'type' => 'object',
                'properties' => ['book_id' => ['type' => 'integer', 'description' => 'ID sách lấy từ search_books']],
                'required' => ['book_id'],
            ],
        ],
    ],
]];

$system = "Bạn là trợ lý thư viện LibGo của trường. Hôm nay là " . date('d/m/Y') . ".
Quy tắc:
- Luôn trả lời bằng tiếng Việt, RẤT ngắn gọn, thân thiện, xưng \"mình\" và gọi sinh viên là \"bạn\".
- KHÔNG dùng ký hiệu định dạng markdown: không dùng dấu sao (* hoặc **), không gạch đầu dòng, không tiêu đề. Chỉ viết chữ thuần.
- Khi tìm được sách: trả lời một câu ngắn, ví dụ \"Có, thư viện còn sách cho môn này nhé:\". TUYỆT ĐỐI KHÔNG liệt kê tên sách, số lượng hay vị trí kệ trong câu trả lời, vì các thông tin đó đã được hiển thị sẵn ở thẻ sách ngay bên dưới. Khi không tìm thấy thì nói rõ là không có.
- Chỉ dùng thông tin trả về từ công cụ. Tuyệt đối không bịa tên sách, số lượng hay vị trí kệ.
- Mượn sách, gia hạn và đăng ký hàng chờ: gọi công cụ tương ứng (checkout_book / renew_loan / join_waitlist), công cụ chỉ tạo đề xuất. Sau đó mời sinh viên bấm nút \"Xác nhận\" bên dưới bằng một câu ngắn. Không bao giờ nói là đã mượn/gia hạn/đăng ký xong.
- Khi sinh viên muốn mượn một cuốn đang còn, gọi checkout_book. Nếu cuốn đó đang hết, gọi join_waitlist thay vì checkout_book.
- Nếu yêu cầu mơ hồ (ví dụ đang mượn nhiều cuốn giống nhau), hỏi lại ngắn gọn trước khi đề xuất.
- Trả sách, tiền phạt, tài khoản: hướng dẫn ngắn gọn sinh viên đến quầy thủ thư, bạn không làm được các việc đó.
- Câu hỏi ngoài phạm vi thư viện (làm bài tập, chuyện phiếm...): từ chối nhẹ nhàng bằng một câu.";

// ---------- 4. Thực thi công cụ (luôn dưới quyền của sinh viên đang đăng nhập) ----------
function create_pending(PDO $pdo, int $memberId, string $action, array $payload): int
{
    $now = date('Y-m-d H:i:s');
    $exp = date('Y-m-d H:i:s', time() + ACTION_TTL_MIN * 60);
    $pdo->prepare("INSERT INTO ai_pending_actions (member_id, action, payload, created_at, expires_at)
                   VALUES (?, ?, ?, ?, ?)")
        ->execute([$memberId, $action, json_encode($payload), $now, $exp]);
    return (int) $pdo->lastInsertId();
}

function run_tool(PDO $pdo, int $memberId, string $name, array $in, array &$actions, array &$books): array
{
    switch ($name) {
        case 'search_books':
            $sql = "SELECT b.id, b.title, b.author, b.subject_code, b.shelf_location, b.book_link,
                           b.available_qty, b.total_qty,
                           (SELECT COUNT(*) FROM waitlist w WHERE w.book_id = b.id AND w.status = 'waiting') AS waiting
                    FROM books b WHERE 1=1";
            $params = [];
            $subject = trim((string) ($in['subject_code'] ?? ''));
            if ($subject !== '') {
                $sql .= " AND b.subject_code LIKE ?";
                $params[] = "%$subject%";
            }
            // Mỗi từ khóa phải xuất hiện trong tên sách hoặc tác giả
            $words = array_slice(array_filter(preg_split('/\s+/u', trim((string) ($in['keyword'] ?? ''))),
                fn($w) => mb_strlen($w) >= 2), 0, 5);
            foreach ($words as $w) {
                $sql .= " AND (b.title LIKE ? OR b.author LIKE ?)";
                $params[] = "%$w%";
                $params[] = "%$w%";
            }
            if (!$params) return ['error' => 'Cần ít nhất một từ khóa hoặc mã môn'];
            $sql .= " ORDER BY b.available_qty > 0 DESC, b.title LIMIT 8";
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            $books = $rows; // gửi kèm cho giao diện để hiện thẻ sách
            return ['count' => count($rows), 'books' => $rows];

        case 'my_loans':
            $st = $pdo->prepare("SELECT l.id AS loan_id, b.title, l.borrow_date, l.due_date, l.renew_count,
                                        CASE WHEN l.due_date < CURDATE() THEN 'overdue' ELSE l.status END AS status
                                 FROM loans l JOIN books b ON b.id = l.book_id
                                 WHERE l.member_id = ? AND l.status IN ('borrowed','overdue')
                                 ORDER BY l.due_date");
            $st->execute([$memberId]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            return ['count' => count($rows), 'loans' => $rows, 'max_renews' => MAX_RENEWS];

        case 'renew_loan':
            $chk = check_renew($pdo, $memberId, (int) ($in['loan_id'] ?? 0));
            if (!$chk['ok']) return $chk;
            $id = create_pending($pdo, $memberId, 'renew_loan', ['loan_id' => (int) $chk['loan']['id']]);
            $actions[] = ['id' => $id, 'label' => 'Xác nhận gia hạn "' . $chk['loan']['title'] . '" tới ' . date('d/m/Y', strtotime($chk['new_due_date']))];
            return ['ok' => true, 'status' => 'Đã tạo đề xuất, chờ sinh viên bấm Xác nhận', 'new_due_date' => $chk['new_due_date']];

        case 'join_waitlist':
            $chk = check_waitlist($pdo, $memberId, (int) ($in['book_id'] ?? 0));
            if (!$chk['ok']) return $chk;
            $id = create_pending($pdo, $memberId, 'join_waitlist', ['book_id' => (int) $chk['book']['id']]);
            $actions[] = ['id' => $id, 'label' => 'Xác nhận vào hàng chờ "' . $chk['book']['title'] . '"'];
            return ['ok' => true, 'status' => 'Đã tạo đề xuất, chờ sinh viên bấm Xác nhận', 'position_if_confirmed' => $chk['position']];

        case 'checkout_book':
            $chk = check_checkout($pdo, $memberId, (int) ($in['book_id'] ?? 0));
            if (!$chk['ok']) return $chk;
            $id = create_pending($pdo, $memberId, 'checkout_book', ['book_id' => (int) $chk['book']['id']]);
            $actions[] = ['id' => $id, 'label' => 'Xác nhận mượn "' . $chk['book']['title'] . '" (hạn trả ' . date('d/m/Y', strtotime($chk['due_date'])) . ')'];
            return ['ok' => true, 'status' => 'Đã tạo đề xuất, chờ sinh viên bấm Xác nhận', 'due_date_if_confirmed' => $chk['due_date']];
    }
    return ['error' => 'Công cụ không tồn tại'];
}

// ---------- 5. Gọi Gemini API ----------
// Thử lần lượt từng mô hình trong AI_MODELS. Mô hình nào hết quota (429) hoặc
// không dùng được (404) thì bỏ qua, chuyển sang mô hình kế tiếp.
function call_gemini(string $apiKey, array $payload): array
{
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $lastDiag = '';

    foreach (AI_MODELS as $model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent';

        // Mạng ra ngoài của Render gói Free hay chập chờn: thử tối đa 3 lần cho mỗi mô hình.
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_NOSIGNAL => true,                       // để giới hạn thời gian có hiệu lực
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,         // tránh treo do IPv6 không có đường ra
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,  // tránh treo do HTTP/2
                CURLOPT_FORBID_REUSE => true,                   // mỗi lần thử là kết nối mới
                CURLOPT_FRESH_CONNECT => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . $apiKey,
                ],
                CURLOPT_POSTFIELDS => $body,
            ]);
            $raw = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);

            if ($raw !== false && $status === 200) {
                curl_close($ch);
                return json_decode($raw, true);
            }

            $lastDiag = sprintf(
                'model=%s attempt=%d status=%d total=%.1fs err=%s',
                $model, $attempt, $status,
                curl_getinfo($ch, CURLINFO_TOTAL_TIME),
                $err ?: 'none'
            );
            error_log("Gemini API try failed [$lastDiag]: " . mb_substr((string) $raw, 0, 200));
            curl_close($ch);

            // 429 (hết quota) hoặc 404 (mô hình không dùng được) -> chuyển sang mô hình khác luôn
            if ($status === 429 || $status === 404) break;
            // Lỗi thật khác (400, 403...) -> thử lại cũng vô ích, chuyển mô hình khác
            if ($status >= 400) break;
            usleep(500000); // timeout/mạng chập chờn -> nghỉ 0,5 giây rồi thử lại cùng mô hình
        }
        // sang mô hình kế tiếp trong danh sách
    }

    error_log("Gemini API error, tất cả mô hình đều lỗi [$lastDiag]");
    throw new RuntimeException('AI request failed');
}

// PHP đọc JSON {} thành mảng rỗng []; khi gửi lại cho Gemini phải đổi về object
function fix_parts(array $parts): array
{
    foreach ($parts as &$part) {
        if (isset($part['functionCall']) && empty($part['functionCall']['args'])) {
            $part['functionCall']['args'] = new stdClass();
        }
    }
    return $parts;
}

// ---------- 6. Vòng lặp: AI chọn công cụ -> PHP chạy -> gửi kết quả -> AI trả lời ----------
$actions = [];
$books = [];
try {
    for ($round = 0; $round < AI_MAX_ROUNDS; $round++) {
        $resp = call_gemini($apiKey, [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => $contents,
            'tools' => $tools,
        ]);
        $parts = $resp['candidates'][0]['content']['parts'] ?? null;
        if (!is_array($parts)) {
            // Bị chặn bởi bộ lọc an toàn hoặc không có câu trả lời
            error_log('Gemini empty response: ' . json_encode($resp));
            out(200, ['reply' => 'Mình chưa trả lời được câu này, bạn hỏi cách khác giúp mình nhé.', 'actions' => $actions, 'books' => $books]);
        }

        $calls = array_values(array_filter($parts, fn($p) => isset($p['functionCall'])));
        if (!$calls) {
            $reply = '';
            foreach ($parts as $p) {
                if (isset($p['text']) && empty($p['thought'])) $reply .= $p['text'];
            }
            out(200, ['reply' => trim($reply), 'actions' => $actions, 'books' => $books]);
        }

        // Gửi lại nguyên phần trả lời của mô hình (giữ cả thoughtSignature nếu có)
        $contents[] = ['role' => 'model', 'parts' => fix_parts($parts)];
        $responses = [];
        foreach ($calls as $p) {
            $call = $p['functionCall'];
            $result = run_tool($pdo, $memberId, $call['name'], (array) ($call['args'] ?? []), $actions, $books);
            $fr = ['name' => $call['name'], 'response' => ['result' => $result]];
            if (!empty($call['id'])) $fr['id'] = $call['id'];
            $responses[] = ['functionResponse' => $fr];
        }
        $contents[] = ['role' => 'user', 'parts' => $responses];
    }
    out(200, ['reply' => 'Mình chưa xử lý xong yêu cầu này, bạn thử hỏi ngắn gọn hơn nhé.', 'actions' => $actions, 'books' => $books]);
} catch (Throwable $e) {
    error_log('Assistant error: ' . $e->getMessage());
    out(502, ['error' => 'Trợ lý đang bận, bạn thử lại sau ít phút hoặc dùng ô tìm kiếm.']);
}