<?php
// File TẠM để kiểm tra server có kết nối ra internet không.
// Mở: https://thuvienfpt.id.vn/api/nettest.php
// Xong việc chẩn đoán thì XÓA file này đi.
header('Content-Type: text/plain; charset=utf-8');

function test(string $label, string $url): void
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,            // chỉ cần thử kết nối, không cần nội dung
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_NOSIGNAL => true,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    printf(
        "%-14s status=%-3d dns=%.1fs connect=%.1fs ssl=%.1fs total=%.1fs %s\n",
        $label, $code,
        curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME),
        curl_getinfo($ch, CURLINFO_CONNECT_TIME),
        curl_getinfo($ch, CURLINFO_APPCONNECT_TIME),
        curl_getinfo($ch, CURLINFO_TOTAL_TIME),
        $err ? "ERR: $err" : 'OK'
    );
    curl_close($ch);
}

echo "curl có sẵn: " . (function_exists('curl_init') ? 'CÓ' : 'KHÔNG') . "\n\n";
test('GitHub', 'https://api.github.com');
test('Google', 'https://www.google.com');
test('Gemini', 'https://generativelanguage.googleapis.com/v1beta/models');
echo "\nKey Gemini đã đặt: " . (getenv('GEMINI_API_KEY') ? 'CÓ' : 'KHÔNG') . "\n";
