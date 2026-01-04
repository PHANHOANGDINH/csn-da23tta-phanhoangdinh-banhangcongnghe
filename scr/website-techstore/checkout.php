<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/products.php';
require_once __DIR__ . '/coupons.php'; // ✅

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

requireLogin();
$u = currentUser();
$username = $u['username'] ?? '';

date_default_timezone_set('Asia/Ho_Chi_Minh');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* =====================
   ✅ PROFILE JSON HELPERS (FIX)
===================== */
function profilesFilePath(): string {
  return __DIR__ . '/data/user_profiles.json';
}

function ensureDataDir(): void {
  $dir = __DIR__ . '/data';
  if (!is_dir($dir)) @mkdir($dir, 0777, true);
}

function loadProfiles(): array {
  ensureDataDir();
  $file = profilesFilePath();

  if (!file_exists($file)) return [];

  $raw = @file_get_contents($file);
  if ($raw === false || trim($raw) === '') return [];

  $data = json_decode($raw, true);

  // ✅ nếu JSON lỗi / null
  if (!is_array($data)) return [];

  // ✅ nếu bị lưu thành list (0,1,2,...) thì bỏ luôn để tránh checkout chết
  // (vì checkout cần dạng associative: key=username/email)
  $isList = array_keys($data) === range(0, count($data)-1);
  if ($isList) return [];

  return $data;
}

function saveProfiles(array $profiles): bool {
  ensureDataDir();
  $file = profilesFilePath();

  // ✅ atomic write + lock
  $tmp = $file . '.tmp';

  $json = json_encode($profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  if ($json === false) return false;

  $fp = @fopen($tmp, 'wb');
  if (!$fp) return false;

  // lock tmp file
  if (!flock($fp, LOCK_EX)) { fclose($fp); return false; }

  fwrite($fp, $json);
  fflush($fp);
  flock($fp, LOCK_UN);
  fclose($fp);

  return @rename($tmp, $file);
}

function normalizeProfile(array $p): array {
  return [
    'fullname' => trim((string)($p['fullname'] ?? '')),
    'phone'    => trim((string)($p['phone'] ?? '')),
    'address'  => trim((string)($p['address'] ?? '')),
    // bạn có thể mở rộng thêm field nếu muốn:
    'email'    => trim((string)($p['email'] ?? '')),
  ];
}

/**
 * ✅ Cứu dữ liệu khi contact/profile lưu theo key khác:
 * - ưu tiên key = username
 * - fallback key = email (nếu user có email)
 * - fallback key = id (nếu bạn có id)
 */
function getProfileForUser(array $profiles, string $username, array $u): array {
  $email = (string)($u['email'] ?? '');
  $id    = (string)($u['id'] ?? '');

  if ($username !== '' && isset($profiles[$username]) && is_array($profiles[$username])) {
    return normalizeProfile($profiles[$username]);
  }

  if ($email !== '' && isset($profiles[$email]) && is_array($profiles[$email])) {
    return normalizeProfile($profiles[$email]);
  }

  if ($id !== '' && isset($profiles[$id]) && is_array($profiles[$id])) {
    return normalizeProfile($profiles[$id]);
  }

  return normalizeProfile([]);
}

// ✅ để header không lỗi (dù trang checkout không dùng filter)
$keyword        = $_GET['q'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$brandFilter    = $_GET['brand'] ?? '';

/* =====================
   1) LẤY GIỎ HÀNG
===================== */
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
  die("Giỏ hàng trống. <a href='index.php'>Quay lại mua sắm</a>");
}
$cart = $_SESSION['cart'];

// Load sản phẩm + map id => product
$products = loadProducts();
$map = [];
foreach ($products as $p) {
  if (!isset($p['id'])) continue;
  $map[(int)$p['id']] = $p;
}

// Ghép giỏ hàng + sản phẩm
$items = [];
$subtotal = 0;

foreach ($cart as $pid => $item) {
  $pid = (int)$pid;
  if (!isset($map[$pid])) continue;

  $p = $map[$pid];

  if (is_array($item)) {
    $qty   = (int)($item['qty'] ?? 0);
    $color = (string)($item['color'] ?? '');
  } else {
    $qty   = (int)$item;
    $color = '';
  }

  if ($qty <= 0) continue;

  $line = (int)($p['price'] ?? 0) * $qty;

  $items[] = [
    'product_id' => (int)$p['id'],
    'name'       => (string)($p['name'] ?? ''),
    'qty'        => $qty,
    'price'      => (int)($p['price'] ?? 0),
    'color'      => $color
  ];

  $subtotal += $line;
}

if (empty($items)) {
  die("Giỏ hàng không hợp lệ. <a href='index.php'>Về trang chủ</a>");
}

/* =====================
   2) LOAD HỒ SƠ USER (FIX)
===================== */
$profiles = loadProfiles();
$profile  = getProfileForUser($profiles, $username, $u);

$message = '';
$orderId = null;

/* =====================
   2.5) TÍNH GIẢM GIÁ (nếu đã áp mã trong session)
===================== */
$appliedCode = '';
$discountNow = 0;

if (!empty($_SESSION['applied_coupon']['code'])) {
  $tryCode = (string)$_SESSION['applied_coupon']['code'];

  // Validate lại theo subtotal hiện tại
  [$ok, $msgCoupon, $discount, $coupon] = validateCoupon($tryCode, $subtotal, $username);
  if ($ok) {
    $appliedCode = strtoupper(trim($tryCode));
    $discountNow = (int)$discount;

    $_SESSION['applied_coupon']['discount'] = $discountNow;
    $_SESSION['applied_coupon']['subtotal'] = $subtotal;
  } else {
    unset($_SESSION['applied_coupon']);
  }
}

$finalNow = max(0, $subtotal - $discountNow);

/* =====================
   3) XỬ LÝ ĐẶT HÀNG
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullname = trim($_POST['fullname'] ?? '');
  $phone    = trim($_POST['phone'] ?? '');
  $address  = trim($_POST['address'] ?? '');
  $pay      = $_POST['payment_method'] ?? 'cod'; // qr_bank | cod

  if ($fullname === '' || $phone === '' || $address === '') {
    $message = 'Vui lòng nhập đầy đủ họ tên, số điện thoại và địa chỉ.';
  } elseif (!in_array($pay, ['qr_bank', 'cod'], true)) {
    $message = 'Phương thức thanh toán không hợp lệ.';
  } else {

    /* 0) TÍNH LẠI COUPON TRƯỚC KHI TẠO ĐƠN */
    $couponCode = '';
    $discount = 0;

    if (!empty($_SESSION['applied_coupon']['code'])) {
      $tryCode = (string)$_SESSION['applied_coupon']['code'];
      [$ok, $msgCoupon2, $discount2, $coupon2] = validateCoupon($tryCode, $subtotal, $username);
      if ($ok) {
        $couponCode = strtoupper(trim($tryCode));
        $discount   = (int)$discount2;
      } else {
        unset($_SESSION['applied_coupon']);
      }
    }

    $finalTotal = max(0, $subtotal - $discount);

    /* 1) KIỂM TRA TỒN KHO */
    $currentProducts = loadProducts();
    $prodMap = [];
    foreach ($currentProducts as $p) {
      if (!isset($p['id'])) continue;
      $prodMap[(int)$p['id']] = $p;
    }

    $stockError = '';
    foreach ($cart as $pid => $item) {
      $pid = (int)$pid;
      $qty = is_array($item) ? (int)($item['qty'] ?? 0) : (int)$item;
      if ($qty <= 0) continue;

      if (!isset($prodMap[$pid])) {
        $stockError = "Sản phẩm ID {$pid} không tồn tại.";
        break;
      }

      $p = $prodMap[$pid];
      $currentStock = isset($p['stock']) ? (int)$p['stock'] : 0;

      if ($currentStock < $qty) {
        $pname = $p['name'] ?? "ID {$pid}";
        $stockError = "Sản phẩm '{$pname}' chỉ còn {$currentStock} cái, bạn đặt {$qty} cái.";
        break;
      }
    }

    if ($stockError !== '') {
      $message = $stockError;
    } else {

      /* 2) TRỪ TỒN KHO & LƯU */
      foreach ($cart as $pid => $item) {
        $pid = (int)$pid;
        $qty = is_array($item) ? (int)($item['qty'] ?? 0) : (int)$item;
        if ($qty <= 0) continue;
        if (!isset($prodMap[$pid])) continue;

        $currentStock = isset($prodMap[$pid]['stock']) ? (int)$prodMap[$pid]['stock'] : 0;
        $newStock = $currentStock - $qty;
        if ($newStock < 0) $newStock = 0;

        $prodMap[$pid]['stock'] = $newStock;
      }

      saveProducts(array_values($prodMap));

      /* 3) LƯU HỒ SƠ + TẠO ĐƠN (FIX: luôn lưu theo username) */
      $profiles[$username] = normalizeProfile([
        'fullname' => $fullname,
        'phone'    => $phone,
        'address'  => $address,
        'email'    => (string)($u['email'] ?? ''),
      ]);

      saveProfiles($profiles);

      $shipping = [
        'fullname' => $fullname,
        'phone'    => $phone,
        'address'  => $address,
        'coupon'   => $couponCode ? ['code' => $couponCode, 'discount' => $discount] : null,
        'subtotal' => $subtotal,
        'discount' => $discount
      ];

      // total = sau giảm
      $orderId = createOrder($username, $items, $finalTotal, $pay, $shipping);

      // commit lượt dùng mã
      if ($orderId && $couponCode) {
        commitCouponUsage($couponCode, $username);
        unset($_SESSION['applied_coupon']);
      }

      // Xóa giỏ
      unset($_SESSION['cart']);

      // QR -> sang trang QR
      if ($pay === 'qr_bank') {
        header("Location: pay_qr.php?order_id=" . urlencode($orderId) . "&amount=" . (int)$finalTotal);
        exit;
      }

      // COD -> hiển thị thành công
      $message = "Đặt hàng thành công! Mã đơn #{$orderId}. Bạn có thể xem trong 'Đơn hàng của tôi'.";
    }
  }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thanh toán - TechStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="style.css">
</head>

<body class="d-flex flex-column min-vh-100 bg-light">
  <!-- TOPBAR -->
  <div class="topbar small">
    <div class="container d-flex justify-content-between">
      <div>Hệ thống cửa hàng công nghệ TechStore</div>
      <div class="d-none d-md-block">Miễn phí giao hàng nội thành • Hỗ trợ: 0967 492 242</div>
    </div>
  </div>

  <!-- HEADER -->
  <header class="ts-headerSticky">
    <div class="header py-2">
      <div class="container d-flex align-items-center gap-3">
        <a href="index.php" class="brand text-decoration-none d-flex align-items-center gap-2">
          <img src="img/logo/logo.png" alt="TechStore" class="ts-logo">
        </a>

        <form class="search flex-grow-1 d-flex" action="index.php" method="get">
          <div class="input-group w-100">
            <span class="input-group-text">
              <i class="bi bi-search"></i>
            </span>
            <input
              class="form-control"
              type="search"
              name="q"
              value="<?php echo h($keyword); ?>"
              placeholder="Nhập tên điện thoại, laptop, phụ kiện... cần tìm">

            <?php if ($categoryFilter !== ''): ?>
              <input type="hidden" name="category" value="<?php echo h($categoryFilter); ?>">
            <?php endif; ?>
            <?php if ($brandFilter !== ''): ?>
              <input type="hidden" name="brand" value="<?php echo h($brandFilter); ?>">
            <?php endif; ?>
          </div>
        </form>

        <div class="d-none d-md-flex align-items-center gap-2 ms-2">
          <a href="cart.php" class="btn-cart-main">
            <i class="bi bi-cart-fill"></i><span class="d-none d-lg-inline">Giỏ hàng</span>
          </a>

          <?php if ($u): ?>
            <div class="dropdown">
              <button class="btn-account-main dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle"></i>
                <span><?php echo h($u['username'] ?? ''); ?></span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="profile.php">Hồ sơ của tôi</a></li>
                <li><a class="dropdown-item" href="orders_user.php">Đơn hàng của tôi</a></li>

                <?php if (($u['role'] ?? '') === 'admin'): ?>
                  <li><hr class="dropdown-divider"></li>
                  <li class="dropdown-header small text-muted">Khu vực quản trị</li>
                  <li><a class="dropdown-item" href="admin_orders.php">QL đơn hàng</a></li>
                  <li><a class="dropdown-item" href="admin_products.php">QL sản phẩm</a></li>
                  <li><a class="dropdown-item" href="admin_users.php">QL người dùng</a></li>
                  <li><a class="dropdown-item" href="admin_revenue.php">Báo cáo doanh thu</a></li>
                  <li><a class="dropdown-item" href="admin_coupons.php">QL mã giảm giá</a></li>
                <?php endif; ?>

                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
              </ul>
            </div>
          <?php else: ?>
            <a href="login.php" class="btn-account-main">
              <i class="bi bi-box-arrow-in-right"></i><span>Đăng nhập</span>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <main class="flex-grow-1">
    <div class="container py-3">
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item"><a href="cart.php">Giỏ hàng</a></li>
          <li class="breadcrumb-item active" aria-current="page">Thanh toán</li>
        </ol>
      </nav>

      <?php if ($orderId && $message && (($_POST['payment_method'] ?? '') === 'cod')): ?>
        <div class="card shadow-sm border-0">
          <div class="card-body text-center py-5">
            <div class="mb-3 fs-1 text-success">
              <i class="bi bi-check-circle-fill"></i>
            </div>
            <h1 class="h5 mb-2">Đặt hàng thành công!</h1>
            <p class="mb-2">
              Mã đơn hàng của bạn là <span class="fw-bold">#<?php echo (int)$orderId; ?></span>.
            </p>
            <p class="text-muted small mb-4">
              Bạn có thể theo dõi trạng thái đơn trong mục <b>Đơn hàng của tôi</b>.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
              <a href="orders_user.php" class="btn btn-primary">Xem đơn hàng của tôi</a>
              <a href="index.php" class="btn btn-outline-secondary">Tiếp tục mua sắm</a>
            </div>
          </div>
        </div>
      <?php else: ?>

        <div class="row g-3">
          <!-- Tóm tắt đơn -->
          <div class="col-12 col-lg-6">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-body">
                <h2 class="h6 mb-3">Tóm tắt đơn hàng</h2>

                <div class="table-responsive">
                  <table class="table align-middle">
                    <thead class="table-light">
                      <tr>
                        <th>Sản phẩm</th>
                        <th class="text-end">Giá</th>
                        <th class="text-center">SL</th>
                        <th class="text-end">Thành tiền</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($items as $it): ?>
                        <tr>
                          <td style="max-width:260px;">
                            <div class="small fw-semibold"><?php echo h($it['name']); ?></div>
                            <?php if (!empty($it['color'])): ?>
                              <div class="text-muted small">Màu: <?php echo h($it['color']); ?></div>
                            <?php endif; ?>
                          </td>
                          <td class="text-end"><?php echo number_format((int)$it['price'], 0, ',', '.'); ?>₫</td>
                          <td class="text-center"><?php echo (int)$it['qty']; ?></td>
                          <td class="text-end"><?php echo number_format(((int)$it['price'] * (int)$it['qty']), 0, ',', '.'); ?>₫</td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <hr>

                <!-- Coupon box -->
                <div class="mb-2">
                  <label class="form-label fw-semibold mb-1">Mã giảm giá</label>
                  <div class="d-flex gap-2">
                    <input id="couponCode" class="form-control" placeholder="Nhập mã" value="<?php echo h($appliedCode); ?>">
                    <button id="btnApplyCoupon" type="button" class="btn btn-dark">Áp mã</button>
                  </div>
                  <div id="couponMsg" class="small mt-2"></div>
                  <?php if ($appliedCode): ?>
                    <div class="small text-success mt-1">Đang áp mã: <b><?php echo h($appliedCode); ?></b></div>
                  <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between">
                  <span class="fw-semibold">Tạm tính</span>
                  <span class="fw-bold"><?php echo number_format((int)$subtotal, 0, ',', '.'); ?>₫</span>
                </div>

                <div class="d-flex justify-content-between mt-1">
                  <span class="fw-semibold">Giảm giá</span>
                  <span class="fw-bold text-success" id="discountValue"><?php echo number_format((int)$discountNow, 0, ',', '.'); ?>₫</span>
                </div>

                <div class="d-flex justify-content-between mt-2">
                  <span class="fw-semibold">Thành tiền</span>
                  <span class="fw-bold text-danger" id="finalValue"><?php echo number_format((int)$finalNow, 0, ',', '.'); ?>₫</span>
                </div>

              </div>
            </div>
          </div>

          <!-- Form giao hàng + thanh toán -->
          <div class="col-12 col-lg-6">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-body">
                <h2 class="h6 mb-3">Thông tin giao hàng</h2>

                <?php if ($message): ?>
                  <div class="alert alert-danger py-2">
                    <?php echo h($message); ?>
                  </div>
                <?php endif; ?>

                <form method="post" class="vstack gap-3">
                  <div>
                    <label class="form-label">Họ tên người nhận</label>
                    <input type="text" name="fullname" class="form-control"
                           value="<?php echo h($profile['fullname'] ?? ''); ?>" required>
                  </div>

                  <div>
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?php echo h($profile['phone'] ?? ''); ?>" required>
                  </div>

                  <div>
                    <label class="form-label">Địa chỉ giao hàng</label>
                    <textarea name="address" rows="3" class="form-control" required><?php
                      echo h($profile['address'] ?? '');
                    ?></textarea>
                  </div>

                  <div class="mb-2">
                    <label class="form-label">Phương thức thanh toán</label>

                    <div class="d-flex flex-wrap gap-3">
                      <label class="d-flex align-items-start gap-2">
                        <input type="radio" name="payment_method" id="pm_qr" value="qr_bank" checked>
                        <span>
                          <b>Chuyển khoản / Quét QR (VietQR)</b><br>
                          <span class="text-muted small">Sau khi đặt hàng, hệ thống sẽ chuyển sang trang QR để bạn quét thanh toán.</span>
                        </span>
                      </label>

                      <label class="d-flex align-items-start gap-2">
                        <input type="radio" name="payment_method" id="pm_cod" value="cod">
                        <span>
                          <b>Thanh toán khi nhận hàng (COD)</b><br>
                          <span class="text-muted small">Thanh toán tiền mặt khi nhận hàng.</span>
                        </span>
                      </label>
                    </div>

                    <div id="qrHint" class="alert alert-info py-2 small mt-2 mb-0" style="display:none;">
                      Bạn chọn thanh toán QR. Sau khi bấm <b>"Xác nhận đặt hàng"</b> sẽ chuyển sang trang QR để quét.
                    </div>
                  </div>

                  <div class="d-flex flex-wrap justify-content-between gap-2 mt-2">
                    <a href="cart.php" class="btn btn-outline-secondary">
                      <i class="bi bi-arrow-left"></i> Quay lại giỏ hàng
                    </a>
                    <button class="btn btn-danger" type="submit">Xác nhận đặt hàng</button>
                  </div>
                </form>

                <p class="small text-muted mt-3 mb-0">
                  Thông tin hồ sơ của bạn sẽ được lưu lại để sử dụng cho các lần mua hàng tiếp theo.
                </p>
              </div>
            </div>
          </div>
        </div>

      <?php endif; ?>
    </div>
  </main>

  <!-- FOOTER -->
  <footer class="footer border-top py-4 mt-3">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="title h5 mb-2">TechStore</div>
          <p class="mb-0">
            Hệ thống cửa hàng công nghệ TechStore.<br>
            Mang đến cho bạn trải nghiệm mua sắm hiện đại.
          </p>
        </div>

        <div>
          <div class="title fw-bold mb-2">Về chúng tôi</div>
          <ul class="list-unstyled mb-0">
            <li><a href="ft_about.php">Giới thiệu</a></li>
            <li><a href="ft_stores.php">Hệ thống cửa hàng</a></li>
            <li><a href="ft_careers.php">Tuyển dụng</a></li>
            <li><a href="contact.php">Liên hệ</a></li>
          </ul>
        </div>

        <div>
          <div class="title fw-bold mb-2">Hỗ trợ</div>
          <ul class="list-unstyled mb-0">
            <li><a href="policy_return.php">Chính sách đổi trả</a></li>
            <li><a href="policy_warranty.php">Bảo hành</a></li>
            <li><a href="policy_shipping.php">Giao hàng</a></li>
            <li><a href="policy_payment.php">Thanh toán</a></li>
          </ul>
        </div>

        <div>
          <div class="title fw-bold mb-2">Liên hệ</div>
          <p class="mb-1 small">
            Email:
            <a href="mailto:phanhoangdinh106@gmail.com" class="text-break">
              phanhoangdinh106@gmail.com
            </a>
          </p>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function formatVND(n){
      return new Intl.NumberFormat('vi-VN', {style:'currency', currency:'VND'}).format(n || 0);
    }

    document.addEventListener('DOMContentLoaded', function () {
      const pmQr  = document.getElementById('pm_qr');
      const pmCod = document.getElementById('pm_cod');
      const hint  = document.getElementById('qrHint');

      function updateHint() {
        if (!hint) return;
        hint.style.display = (pmQr && pmQr.checked) ? 'block' : 'none';
      }

      if (pmQr)  pmQr.addEventListener('change', updateHint);
      if (pmCod) pmCod.addEventListener('change', updateHint);
      updateHint();

      // Apply coupon
      const btn = document.getElementById('btnApplyCoupon');
      const input = document.getElementById('couponCode');
      const msg = document.getElementById('couponMsg');

      btn?.addEventListener('click', async () => {
        const code = (input?.value || '').trim();
        const subtotal = <?php echo (int)$subtotal; ?>;

        const fd = new FormData();
        fd.append('code', code);
        fd.append('subtotal', subtotal);

        try{
          const res = await fetch('apply_coupon.php', { method:'POST', body: fd });
          const data = await res.json();

          msg.textContent = data.message || '';
          msg.className = 'small mt-2 ' + (data.ok ? 'text-success' : 'text-danger');

          if (data.ok) {
            document.getElementById('discountValue').textContent = formatVND(data.discount);
            document.getElementById('finalValue').textContent = formatVND(data.final);
          } else {
            document.getElementById('discountValue').textContent = formatVND(0);
            document.getElementById('finalValue').textContent = formatVND(subtotal);
          }
        }catch(e){
          msg.textContent = 'Không thể áp mã lúc này. Vui lòng thử lại.';
          msg.className = 'small mt-2 text-danger';
        }
      });
    });
  </script>
</body>
</html>
