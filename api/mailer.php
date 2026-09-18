<?php
// Gửi email qua Brevo (https://www.brevo.com) — gói miễn phí 300 email/ngày.
// Cần đặt biến môi trường trên Render:
//   BREVO_API_KEY   : khóa API lấy trong Brevo (SMTP & API → API Keys)
//   MAIL_FROM       : email người gửi ĐÃ XÁC MINH trong Brevo (Senders)
//   MAIL_FROM_NAME  : tên hiển thị, ví dụ "Thư viện số"

function send_email(string $toEmail, string $toName, string $subject, string $html): bool
{
    $apiKey = getenv('BREVO_API_KEY') ?: '';
    $from = getenv('MAIL_FROM') ?: '';
    $fromName = getenv('MAIL_FROM_NAME') ?: 'Thư viện số';
    $url = getenv('BREVO_API_URL') ?: 'https://api.brevo.com/v3/smtp/email';

    if ($apiKey === '' || $from === '') {
        error_log('send_email: chưa cấu hình BREVO_API_KEY / MAIL_FROM');
        return false;
    }

    $payload = json_encode([
        'sender' => ['name' => $fromName, 'email' => $from],
        'to' => [['email' => $toEmail, 'name' => $toName]],
        'subject' => $subject,
        'htmlContent' => $html,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'api-key: ' . $apiKey,
            'content-type: application/json',
            'accept: application/json',
        ],
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        error_log("send_email: Brevo trả về $status $err " . substr((string) $body, 0, 300));
        return false;
    }
    return true;
}

// Che bớt email để hiển thị: nghiahhn.ce190490@gmail.com -> ng************@gmail.com
function mask_email(string $email): string
{
    [$user, $domain] = array_pad(explode('@', $email, 2), 2, '');
    $keep = mb_substr($user, 0, 2);
    return $keep . str_repeat('*', max(3, mb_strlen($user) - 2)) . '@' . $domain;
}