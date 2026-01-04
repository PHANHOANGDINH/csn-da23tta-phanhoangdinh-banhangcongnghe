<?php
require_once __DIR__ . '/orders.php';

$id = (int)($_GET['order_id'] ?? 0);
if ($id <= 0) die("Không tìm thấy mã đơn.");

$orders = loadOrders();
$found = false;

foreach ($orders as &$o) {
  if ((int)($o['id'] ?? 0) === $id) {
    // Đảm bảo là đơn QR
    $o['payment_method'] = 'qr_bank';
    $o['payment_status'] = 'waiting_confirm';
    $o['payment_requested_at'] = date('Y-m-d H:i:s'); // optional
    $found = true;
    break;
  }
}
unset($o);

if ($found) saveOrders($orders);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Xác nhận thanh toán - TechStore</title>
  <style>
    body{font-family:system-ui;background:#f6f7fb;margin:0;padding:24px}
    .card{max-width:560px;margin:auto;background:#fff;border-radius:16px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
    .btn{display:inline-block;padding:10px 14px;border-radius:12px;background:#111827;color:#fff;text-decoration:none}
    .muted{color:#6b7280}
  </style>
</head>
<body>
  <div class="card">
    <h2 style="margin:0 0 10px">TechStore</h2>

    <?php if ($found): ?>
      <p>Đã ghi nhận thanh toán cho đơn <b>#<?= htmlspecialchars((string)$id) ?></b></p>
      <p class="muted">Trạng thái: <b>Chờ admin xác nhận</b> (waiting_confirm)</p>
      <a class="btn" href="orders_user.php">Xem đơn hàng của tôi</a>
    <?php else: ?>
      <p>Không tìm thấy đơn: <b>#<?= htmlspecialchars((string)$id) ?></b></p>
      <p class="muted">Kiểm tra lại link QR có đúng <code>order_id</code> không.</p>
      <a class="btn" href="index.php">Về trang chủ</a>
    <?php endif; ?>
  </div>
</body>
</html>
