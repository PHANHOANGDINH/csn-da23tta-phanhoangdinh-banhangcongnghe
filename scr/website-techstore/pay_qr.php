<?php
require_once __DIR__ . '/config_vietqr.php';
require_once __DIR__ . '/vietqr_generate.php';

/**
 * Nhận order_id + amount qua query string:
 * pay_qr.php?order_id=TS123&amount=249000
 */

$orderId = preg_replace('/[^A-Za-z0-9_-]/', '', $_GET['order_id'] ?? '');
$amount  = (int)($_GET['amount'] ?? 0);

// fallback demo
if (!$orderId) $orderId = 'TS_' . date('Ymd_His');
if ($amount <= 0) $amount = 10000;

// Nội dung CK (ngắn gọn, dễ đối soát)
$addInfo = TECHSTORE_ADDINFO_PREFIX . ' ' . $orderId;

// Payload gửi VietQR
$payload = [
  "accountNo"   => TECHSTORE_ACCOUNT,
  "accountName" => TECHSTORE_NAME,
  "acqId"       => TECHSTORE_BANK_BIN,
  "addInfo"     => $addInfo,
  "amount"      => (string)$amount,
  "template"    => TECHSTORE_QR_TEMPLATE,
];

$qrDataURL = '';
$error = '';

try {
  // Nếu bạn chưa điền API key đúng -> sẽ báo lỗi rõ ở UI
  $qrDataURL = vietqr_generate_dataurl($payload);
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Thanh toán QR - TechStore</title>
  <style>
    body{font-family:system-ui,Segoe UI,Arial; background:#f6f7fb; margin:0; padding:24px}
    .card{max-width:560px; margin:auto; background:#fff; border-radius:16px; padding:18px; box-shadow:0 10px 30px rgba(0,0,0,.08)}
    .row{display:flex; justify-content:space-between; gap:12px; margin:10px 0}
    .muted{color:#6b7280}
    .qr{display:flex; justify-content:center; margin:14px 0}
    img{max-width:100%; border-radius:12px}
    .btn{display:inline-block; padding:10px 14px; border-radius:12px; background:#111827; color:#fff; text-decoration:none}
    .btn2{display:inline-block; padding:10px 14px; border-radius:12px; background:#e5e7eb; color:#111827; text-decoration:none; margin-left:8px}
    .danger{background:#fee2e2; color:#991b1b; padding:10px 12px; border-radius:12px; line-height:1.5}
    code{background:#f3f4f6; padding:2px 6px; border-radius:8px}
    .small{font-size:14px}
  </style>
</head>
<body>
  <div class="card">
    <h2 style="margin:0 0 6px">Quét QR để thanh toán (TechStore)</h2>
    <div class="muted small">Dùng app ngân hàng/Ví hỗ trợ VietQR để quét</div>

    <?php if ($error): ?>
      <div class="danger" style="margin-top:14px">
        <b>Không tạo được QR</b><br>
        <?= htmlspecialchars($error) ?><br><br>
        Kiểm tra:
        <ul style="margin:6px 0 0 18px">
          <li><code>VIETQR_CLIENT_ID</code>, <code>VIETQR_API_KEY</code> trong <code>config_vietqr.php</code></li>
          <li><code>TECHSTORE_BANK_BIN</code>, <code>TECHSTORE_ACCOUNT</code>, <code>TECHSTORE_NAME</code></li>
        </ul>
      </div>
    <?php else: ?>
      <div style="margin-top:14px">
        <div class="row"><span class="muted">Mã đơn</span><b><?= htmlspecialchars($orderId) ?></b></div>
        <div class="row"><span class="muted">Số tiền</span><b><?= number_format($amount, 0, ',', '.') ?> ₫</b></div>
        <div class="row"><span class="muted">Nội dung CK</span><b><?= htmlspecialchars($addInfo) ?></b></div>
      </div>

      <div class="qr">
        <img src="<?= htmlspecialchars($qrDataURL) ?>" alt="VietQR TechStore" />
      </div>

      <p class="muted small" style="margin:0 0 12px">
        Sau khi chuyển khoản xong, bấm “Tôi đã thanh toán” để website chuyển sang trạng thái chờ xác nhận.
      </p>

      <a class="btn" href="payment_pending.php?order_id=<?= urlencode($orderId) ?>">Tôi đã thanh toán</a>
      <a class="btn2" href="javascript:history.back()">Quay lại</a>
    <?php endif; ?>
  </div>
</body>
</html>
