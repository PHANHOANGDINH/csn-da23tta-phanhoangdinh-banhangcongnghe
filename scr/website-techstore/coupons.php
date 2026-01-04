<?php
// coupons.php
// Quản lý mã giảm giá bằng JSON cho TechStore

// ====== FILE PATHS ======
const COUPONS_FILE      = __DIR__ . '/data/coupons.json';
const COUPON_USAGE_FILE = __DIR__ . '/data/coupon_usage.json';

// ====== BASIC IO ======
function loadCoupons(): array {
  if (!file_exists(COUPONS_FILE)) return [];
  $data = json_decode(file_get_contents(COUPONS_FILE), true);
  return is_array($data) ? $data : [];
}

function saveCoupons(array $coupons): void {
  file_put_contents(
    COUPONS_FILE,
    json_encode(array_values($coupons), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
  );
}

function loadCouponUsage(): array {
  if (!file_exists(COUPON_USAGE_FILE)) return [];
  $data = json_decode(file_get_contents(COUPON_USAGE_FILE), true);
  return is_array($data) ? $data : [];
}

function saveCouponUsage(array $usage): void {
  file_put_contents(
    COUPON_USAGE_FILE,
    json_encode($usage, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
  );
}

// ====== HELPERS ======
function normCode(string $code): string {
  return strtoupper(trim($code));
}

function findCouponIndex(array $coupons, string $code): int {
  $code = normCode($code);
  foreach ($coupons as $i => $c) {
    if (normCode($c['code'] ?? '') === $code) return $i;
  }
  return -1;
}

function parseTimeToTs(?string $s): int {
  if (!$s) return 0;
  $ts = strtotime($s);
  return $ts === false ? 0 : $ts;
}

/**
 * Validate coupon payload (admin create/update)
 * @return array [ok(bool), errors(array), cleaned(array)]
 */
function validateCouponPayload(array $in, bool $isUpdate = false, ?array $oldCoupon = null): array {
  $errors = [];

  $code = normCode((string)($in['code'] ?? ''));
  if ($code === '') $errors[] = 'Mã (code) không được để trống.';
  if (strlen($code) < 3) $errors[] = 'Mã phải >= 3 ký tự.';

  $type = (string)($in['type'] ?? 'fixed');
  if (!in_array($type, ['fixed', 'percent'], true)) $errors[] = 'Type chỉ được fixed hoặc percent.';

  $value = (int)($in['value'] ?? 0);
  if ($value <= 0) $errors[] = 'Giá trị giảm (value) phải > 0.';
  if ($type === 'percent' && ($value < 1 || $value > 100)) $errors[] = 'Percent phải trong 1–100.';

  $minOrder = (int)($in['minOrder'] ?? 0);
  if ($minOrder < 0) $errors[] = 'MinOrder không hợp lệ.';

  $maxDiscount = (int)($in['maxDiscount'] ?? 0);
  if ($maxDiscount < 0) $errors[] = 'MaxDiscount không hợp lệ.';
  if ($type === 'fixed') $maxDiscount = 0; // fixed không dùng maxDiscount

  $startAt = trim((string)($in['startAt'] ?? ''));
  $endAt   = trim((string)($in['endAt'] ?? ''));

  $startTs = parseTimeToTs($startAt ?: null);
  $endTs   = parseTimeToTs($endAt ?: null);

  if ($startAt && !$startTs) $errors[] = 'StartAt sai định dạng (vd: 2025-12-31 23:59:59).';
  if ($endAt && !$endTs) $errors[] = 'EndAt sai định dạng (vd: 2025-12-31 23:59:59).';
  if ($startTs && $endTs && $startTs > $endTs) $errors[] = 'StartAt phải <= EndAt.';

  $usageLimit   = (int)($in['usageLimit'] ?? 0);
  $perUserLimit = (int)($in['perUserLimit'] ?? 0);

  if ($usageLimit < 0) $errors[] = 'UsageLimit không hợp lệ.';
  if ($perUserLimit < 0) $errors[] = 'PerUserLimit không hợp lệ.';

  $active = !empty($in['active']);

  // usedCount: nếu update giữ nguyên, nếu create = 0
  $usedCount = 0;
  if ($isUpdate && $oldCoupon) {
    $usedCount = (int)($oldCoupon['usedCount'] ?? 0);
  }

  $cleaned = [
    'code'         => $code,
    'type'         => $type,
    'value'        => $value,
    'minOrder'     => $minOrder,
    'maxDiscount'  => $maxDiscount,
    'startAt'      => $startAt,
    'endAt'        => $endAt,
    'usageLimit'   => $usageLimit,
    'usedCount'    => $usedCount,
    'perUserLimit' => $perUserLimit,
    'active'       => $active
  ];

  return [count($errors) === 0, $errors, $cleaned];
}

/**
 * Validate coupon for checkout/apply_coupon
 * @return array [ok(bool), message(string), discount(int), coupon(array|null)]
 */
function validateCoupon(string $code, int $subtotal, ?string $username = null): array {
  $code = normCode($code);
  if ($code === '') return [false, 'Vui lòng nhập mã giảm giá.', 0, null];

  $coupons = loadCoupons();
  $idx = findCouponIndex($coupons, $code);
  if ($idx < 0) return [false, 'Mã giảm giá không tồn tại.', 0, null];

  $c = $coupons[$idx];

  if (empty($c['active'])) return [false, 'Mã giảm giá đang tạm tắt.', 0, null];

  $now = time();
  $startTs = parseTimeToTs(!empty($c['startAt']) ? $c['startAt'] : null);
  $endTs   = parseTimeToTs(!empty($c['endAt']) ? $c['endAt'] : null);

  if ($startTs && $now < $startTs) return [false, 'Mã chưa đến thời gian áp dụng.', 0, null];
  if ($endTs && $now > $endTs) return [false, 'Mã đã hết hạn.', 0, null];

  $minOrder = (int)($c['minOrder'] ?? 0);
  if ($subtotal < $minOrder) {
    return [false, 'Đơn tối thiểu ' . number_format($minOrder, 0, ',', '.') . '₫ mới dùng được mã.', 0, null];
  }

  $usageLimit = (int)($c['usageLimit'] ?? 0);
  $usedCount  = (int)($c['usedCount'] ?? 0);
  if ($usageLimit > 0 && $usedCount >= $usageLimit) {
    return [false, 'Mã đã hết lượt sử dụng.', 0, null];
  }

  $perUserLimit = (int)($c['perUserLimit'] ?? 0);
  if ($perUserLimit > 0 && $username) {
    $usage = loadCouponUsage();
    $uCount = (int)($usage[$username][$code] ?? 0);
    if ($uCount >= $perUserLimit) {
      return [false, 'Bạn đã dùng mã này đủ số lần cho phép.', 0, null];
    }
  }

  $type = $c['type'] ?? 'fixed'; // fixed|percent
  $value = (int)($c['value'] ?? 0);
  $maxDiscount = (int)($c['maxDiscount'] ?? 0);

  $discount = 0;
  if ($type === 'percent') {
    $discount = (int)floor($subtotal * $value / 100);
    if ($maxDiscount > 0) $discount = min($discount, $maxDiscount);
  } else {
    $discount = $value;
  }

  $discount = max(0, min($discount, $subtotal));
  if ($discount <= 0) return [false, 'Mã không hợp lệ cho đơn này.', 0, null];

  return [true, 'Áp mã thành công.', $discount, $c];
}

/**
 * Commit coupon usage after order created successfully
 */
function commitCouponUsage(string $code, string $username): void {
  $code = normCode($code);
  if ($code === '' || $username === '') return;

  // tăng usedCount trong coupons.json
  $coupons = loadCoupons();
  $idx = findCouponIndex($coupons, $code);
  if ($idx < 0) return;

  $coupons[$idx]['usedCount'] = (int)($coupons[$idx]['usedCount'] ?? 0) + 1;
  saveCoupons($coupons);

  // tăng per-user usage
  $usage = loadCouponUsage();
  if (!isset($usage[$username])) $usage[$username] = [];
  $usage[$username][$code] = (int)($usage[$username][$code] ?? 0) + 1;
  saveCouponUsage($usage);
}
