<?php
require_once 'auth.php';

requireLogin();
$user = currentUser();

$keyword        = trim($_GET['q'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$brandFilter    = trim($_GET['brand'] ?? '');

$profilesFile = __DIR__ . '/data/user_profiles.json';
$profiles = [];
if (file_exists($profilesFile)) {
  $profiles = json_decode(file_get_contents($profilesFile), true);
  if (!is_array($profiles)) $profiles = [];
}

/**
 * ✅ KEY PROFILE:
 * - Ưu tiên email (login bằng email)
 * - Nếu chưa có email thì dùng username (để giữ dữ liệu cũ)
 */
$email = trim((string)($user['email'] ?? ''));
$username = (string)($user['username'] ?? '');
$profileKey = $email !== '' ? strtolower($email) : $username;

$profile = $profiles[$profileKey] ?? [
  'fullname' => '',
  'phone'    => '',
  'address'  => ''
];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $profile['fullname'] = trim($_POST['fullname'] ?? '');
  $profile['phone']    = trim($_POST['phone'] ?? '');
  $profile['address']  = trim($_POST['address'] ?? '');

  $profiles[$profileKey] = $profile;

  file_put_contents(
    $profilesFile,
    json_encode($profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
  );

  $message = 'Lưu hồ sơ thành công.';
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Hồ sơ của tôi - TechStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="logo.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

  <div class="topbar small">
    <div class="container d-flex justify-content-between">
      <div>Hệ thống cửa hàng công nghệ TechStore</div>
      <div class="d-none d-md-block">Miễn phí giao hàng toàn quốc • Hỗ trợ: 0967 492 242</div>
    </div>
  </div>

  <header class="ts-headerSticky">
    <div class="header py-2">
      <div class="container d-flex align-items-center gap-3">
        <a href="index.php" class="brand text-decoration-none d-flex align-items-center gap-2">
          <img src="img/logo/logo.png" alt="TechStore" class="ts-logo">
        </a>

        <form class="search flex-grow-1 d-flex" action="index.php" method="get">
          <div class="input-group w-100">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" type="search" name="q" value="<?php echo h($keyword); ?>"
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

          <?php if ($user): ?>
            <div class="dropdown">
              <button class="btn-account-main dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle"></i>
                <span><?php echo h($user['username']); ?></span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="profile.php">Hồ sơ của tôi</a></li>
                <li><a class="dropdown-item" href="orders_user.php">Đơn hàng của tôi</a></li>

                <?php if (($user['role'] ?? '') === 'admin'): ?>
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
          <li class="breadcrumb-item active" aria-current="page">Hồ sơ của tôi</li>
        </ol>
      </nav>

      <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-7">
          <div class="card shadow-sm border-0">
            <div class="card-body p-4">
              <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                <div>
                  <h1 class="h5 mb-1">Hồ sơ người dùng</h1>
                  <div class="text-muted small">
                    Tên hiển thị: <b><?php echo h($username); ?></b>
                    <?php if ($email !== ''): ?>
                      • Email: <b><?php echo h($email); ?></b>
                    <?php endif; ?>
                    <span class="badge bg-light text-dark border ms-2">
                      Vai trò: <?php echo h($user['role'] ?? 'member'); ?>
                    </span>
                  </div>
                </div>
                <a class="btn btn-outline-secondary btn-sm" href="orders_user.php">
                  <i class="bi bi-receipt"></i> Xem đơn hàng
                </a>
              </div>

              <?php if ($message): ?>
                <div class="alert alert-success py-2 mb-3">
                  <i class="bi bi-check-circle me-1"></i>
                  <?php echo h($message); ?>
                </div>
              <?php endif; ?>

              <form method="post" class="vstack gap-3">
                <div>
                  <label class="form-label fw-semibold">Họ và tên</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="fullname" class="form-control"
                      placeholder="Tên người dùng"
                      value="<?php echo h($profile['fullname']); ?>">
                  </div>
                </div>

                <div>
                  <label class="form-label fw-semibold">Số điện thoại</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input type="text" name="phone" class="form-control"
                      placeholder="Số điện thoại"
                      value="<?php echo h($profile['phone']); ?>">
                  </div>
                </div>

                <div>
                  <label class="form-label fw-semibold">Địa chỉ giao hàng</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                    <textarea name="address" rows="3" class="form-control"
                      placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành..."><?php echo h($profile['address']); ?></textarea>
                  </div>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-end pt-2">
                  <a class="btn btn-outline-secondary" href="index.php">
                    <i class="bi bi-house"></i> Về trang chủ
                  </a>
                  <button class="btn btn-primary" type="submit">
                    <i class="bi bi-save"></i> Lưu hồ sơ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

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
</body>
</html>
