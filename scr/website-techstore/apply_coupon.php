<?php
require_once 'auth.php';
require_once 'coupons.php';

header('Content-Type: application/json; charset=utf-8');
requireLogin();

$u = currentUser();
$username = $u['username'] ?? '';

$code = trim($_POST['code'] ?? '');
$subtotal = (int)($_POST['subtotal'] ?? 0);

list($ok, $msg, $discount, $coupon) = validateCoupon($code, $subtotal, $username);

if ($ok) {
  $_SESSION['applied_coupon'] = [
    'code' => strtoupper(trim($code)),
    'discount' => $discount,
    'subtotal' => $subtotal
  ];
}

echo json_encode([
  'ok' => $ok,
  'message' => $msg,
  'discount' => $discount,
  'final' => max(0, $subtotal - $discount),
  'code' => strtoupper(trim($code))
], JSON_UNESCAPED_UNICODE);
