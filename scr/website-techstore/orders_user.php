<?php
require_once 'auth.php';
require_once 'orders.php';

requireLogin();
$user = currentUser();            // user đang đăng nhập
$u = $user;                       // ✅ để header dùng $u như các trang khác

// ✅ FIX header vars
$keyword        = trim($_GET['q'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$brandFilter    = trim($_GET['brand'] ?? '');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$list = getOrdersByUser($user['username']);

// ✅ Tính nhanh số liệu
$totalOrders = is_array($list) ? count($list) : 0;
$sumTotal = 0;
$statusCount = [
  'pending' => 0, 'approved' => 0, 'shipping' => 0, 'done' => 0, 'cancel' => 0
];
if (!empty($list)) {
  foreach ($list as $o) {
    $sumTotal += (float)($o['total'] ?? 0);
    $st = $o['status'] ?? '';
    if (isset($statusCount[$st])) $statusCount[$st]++;
  }
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8">
    <title>Đơn hàng của tôi - TechStore</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- CSS giao diện -->
    <link rel="stylesheet" href="style.css">

    <!-- ✅ CSS nhẹ cho trang -->
    <style>
      .ts-page-title{ font-weight: 700; }
      .ts-card{
        border: 1px solid rgba(0,0,0,.06);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(15,23,42,.06);
      }
      .ts-stat{
        background: #fff;
        border: 1px solid rgba(0,0,0,.06);
        border-radius: 16px;
        padding: 14px 16px;
        display:flex;
        gap: 12px;
        align-items:center;
      }
      .ts-ico{
        width: 42px; height: 42px;
        border-radius: 14px;
        display:grid;
        place-items:center;
        background: rgba(13,110,253,.10);
        color: #0d6efd;
        font-size: 18px;
        flex: 0 0 auto;
      }
      .table thead th{ white-space: nowrap; }
      .ts-muted{ color:#6b7280; }
    </style>
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
                  <span><?php echo h($u['username']); ?></span>
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

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Đơn hàng của tôi</li>
          </ol>
        </nav>

        <div class="d-flex align-items-end justify-content-between gap-2 mb-3">
          <div>
            <h1 class="h4 ts-page-title mb-1">Đơn hàng của tôi</h1>
            <div class="small ts-muted">
              Tài khoản: <b><?php echo h($user['username']); ?></b>
            </div>
          </div>
          <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-bag"></i> Tiếp tục mua sắm
          </a>
        </div>

        <!-- ✅ Summary -->
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <div class="ts-stat">
              <div class="ts-ico"><i class="bi bi-receipt"></i></div>
              <div>
                <div class="fw-semibold">Tổng đơn hàng</div>
                <div class="h5 mb-0"><?php echo (int)$totalOrders; ?></div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ts-stat">
              <div class="ts-ico"><i class="bi bi-cash-coin"></i></div>
              <div>
                <div class="fw-semibold">Tổng chi tiêu</div>
                <div class="h5 mb-0"><?php echo number_format($sumTotal, 0, ',', '.'); ?>₫</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ts-stat">
              <div class="ts-ico"><i class="bi bi-truck"></i></div>
              <div>
                <div class="fw-semibold">Đang giao / chờ xử lý</div>
                <div class="h5 mb-0">
                  <?php echo (int)($statusCount['shipping'] + $statusCount['pending']); ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="ts-card bg-white p-3 p-md-4">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-semibold">
              Danh sách đơn hàng
              <span class="text-muted small">(<?php echo (int)$totalOrders; ?>)</span>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 120px;">Mã đơn</th>
                  <th class="text-end" style="width: 160px;">Tổng tiền</th>
                  <th style="width: 170px;">Ngày đặt</th>
                  <th style="width: 170px;">Ngày giao</th>
                  <th style="width: 180px;">Trạng thái</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($list)): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    Bạn chưa có đơn hàng nào. <a href="index.php">Mua sắm ngay</a>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($list as $o): ?>
                  <?php
                    // Trạng thái + màu badge
                    $statusText = getStatusLabel($o['status']);
                    $badgeClass = 'bg-secondary';
                    if (($o['status'] ?? '') === 'pending')  $badgeClass = 'bg-warning text-dark';
                    if (($o['status'] ?? '') === 'approved') $badgeClass = 'bg-info text-dark';
                    if (($o['status'] ?? '') === 'shipping') $badgeClass = 'bg-primary';
                    if (($o['status'] ?? '') === 'done')     $badgeClass = 'bg-success';
                    if (($o['status'] ?? '') === 'cancel')   $badgeClass = 'bg-danger';

                    $orderDate = !empty($o['created_at'])
                      ? date('d/m/Y H:i', strtotime($o['created_at']))
                      : '-';

                    $deliveryDate = !empty($o['delivered_at'])
                      ? date('d/m/Y H:i', strtotime($o['delivered_at']))
                      : '-';
                  ?>
                  <tr>
                    <td class="fw-semibold">#<?php echo (int)($o['id'] ?? 0); ?></td>
                    <td class="text-end">
                      <?php echo number_format((float)($o['total'] ?? 0), 0, ',', '.'); ?>₫
                    </td>
                    <td class="small"><?php echo h($orderDate); ?></td>
                    <td class="small"><?php echo h($deliveryDate); ?></td>
                    <td>
                      <span class="badge <?php echo $badgeClass; ?>">
                        <?php echo h($statusText); ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>

          <p class="text-muted small mb-0 mt-3">
            Khi đơn được xử lý, bạn có thể theo dõi <b>trạng thái</b> và <b>thời gian giao hàng</b> ngay tại đây.
          </p>
        </div>

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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
