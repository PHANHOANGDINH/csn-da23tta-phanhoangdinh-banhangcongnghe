<?php
require_once __DIR__ . '/config_vietqr.php';

/**
 * Gọi VietQR API để tạo ảnh QR (data URI).
 * Trả về chuỗi dạng: data:image/png;base64,....
 */
function vietqr_generate_dataurl(array $payload): string {
    $ch = curl_init('https://api.vietqr.io/v2/generate');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'x-client-id: ' . VIETQR_CLIENT_ID,
            'x-api-key: ' . VIETQR_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 15,
    ]);

    $raw = curl_exec($ch);

    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error: $err");
    }

    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($raw, true);

    if ($http < 200 || $http >= 300) {
        $msg = is_array($json) ? ($json['message'] ?? 'Unknown error') : $raw;
        throw new Exception("VietQR HTTP $http: $msg");
    }

    $qr = $json['data']['qrDataURL'] ?? '';
    if (!$qr) throw new Exception("Không thấy data.qrDataURL trong response.");

    return $qr;
}
