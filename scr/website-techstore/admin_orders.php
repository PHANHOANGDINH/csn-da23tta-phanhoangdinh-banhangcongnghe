<?php
// admin_orders.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/orders.php';

requireLogin();
requireAdmin();

$u = currentUser();

// Đặt múi giờ Việt Nam để hiển thị ngày giờ đúng
date_default_timezone_set('Asia/Ho_Chi_Minh');

/* =========================
   HELPERS
========================= */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$keyword        = $_GET['q'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$brandFilter    = $_GET['brand'] ?? '';

/* =========================
   XỬ LÝ ACTION (POST)
   - confirm_paid: duyệt thanh toán QR
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action  = $_POST['action'] ?? '';
  $orderId = (int)($_POST['order_id'] ?? 0);

  if ($orderId > 0) {
    if ($action === 'confirm_paid') {
      // set payment_status = paid (+ paid_at)
      updatePaymentStatus($orderId, 'paid');
    }
    // bạn có thể thêm action khác ở đây nếu muốn
  }

  header('Location: admin_orders.php');
  exit;
}

/* =========================
   LOAD ORDERS + SORT (MỚI NHẤT TRƯỚC)
========================= */
$orders = loadOrders();

// ✅ Sắp xếp đơn lớn nhất (mới nhất) lên trước để dễ duyệt
// Ưu tiên theo id giảm dần; nếu thiếu id thì fallback theo created_at.
uasort($orders, function($a, $b) {
  $aid = (int)($a['id'] ?? 0);
  $bid = (int)($b['id'] ?? 0);

  if ($aid !== 0 || $bid !== 0) {
    return $bid <=> $aid; // DESC theo id
  }

  $at = strtotime($a['created_at'] ?? '1970-01-01 00:00:00');
  $bt = strtotime($b['created_at'] ?? '1970-01-01 00:00:00');
  return $bt <=> $at; // DESC theo thời gian
});

/* =====================================
   THỐNG KÊ SỐ LƯỢNG ĐƠN THEO TRẠNG THÁI
===================================== */
$statusCounters = [
  'pending'  => 0,
  'approved' => 0,
  'shipping' => 0,
  'done'     => 0,
  'cancel'   => 0
];

foreach ($orders as $o) {
  $st = $o['status'] ?? '';
  if (isset($statusCounters[$st])) $statusCounters[$st]++;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý đơn hàng - TechStore (Admin)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <link rel="stylesheet" href="style.css?v=1">
</head>

<body class="ts-admin d-flex flex-column min-vh-100">
  <!-- TOPBAR -->
  <div class="topbar small">
    <div class="container d-flex justify-content-between">
      <div>Bảng điều khiển Admin • TechStore</div>
      <div class="d-none d-md-block">
        Xin chào, <?php echo h($u['username'] ?? ''); ?>
      </div>
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
                  <li><a class="dropdown-item" href="admin_coupons.php">QL mã giảm giá</a></li>
                  <li><a class="dropdown-item" href="admin_revenue.php">Báo cáo doanh thu</a></li>
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

      <!-- BREADCRUMB -->
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active" aria-current="page">Admin • Quản lý đơn hàng</li>
        </ol>
      </nav>

      <!-- THẺ TỔNG QUAN -->
      <div class="row g-3 mb-3">
        <div class="col-md-4 col-lg-2">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-2">
              <div class="text-muted small mb-1">Tổng số đơn</div>
              <div class="fs-4 fw-bold"><?php echo count($orders); ?></div>
            </div>
          </div>
        </div>

        <div class="col-md-4 col-lg-2">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-2">
              <div class="text-muted small mb-1">Chờ duyệt</div>
              <div class="fs-5 fw-semibold text-warning"><?php echo (int)$statusCounters['pending']; ?></div>
            </div>
          </div>
        </div>

        <div class="col-md-4 col-lg-2">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-2">
              <div class="text-muted small mb-1">Đã duyệt / Đang giao</div>
              <div class="fs-5 fw-semibold text-primary">
                <?php echo (int)($statusCounters['approved'] + $statusCounters['shipping']); ?>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4 col-lg-2">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-2">
              <div class="text-muted small mb-1">Hoàn thành</div>
              <div class="fs-5 fw-semibold text-success"><?php echo (int)$statusCounters['done']; ?></div>
            </div>
          </div>
        </div>

        <div class="col-md-4 col-lg-2">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-2">
              <div class="text-muted small mb-1">Đã hủy</div>
              <div class="fs-5 fw-semibold text-danger"><?php echo (int)$statusCounters['cancel']; ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- BẢNG ĐƠN HÀNG -->
      <div class="card shadow-sm border-0">
        <div class="card-body">

          <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
              <h1 class="h5 mb-1">Quản lý đơn hàng</h1>
              <p class="text-muted small mb-0">
                Duyệt đơn, xác nhận thanh toán QR, cập nhật trạng thái giao hàng.
              </p>
            </div>
            <span class="badge bg-dark-subtle text-dark">Vai trò: Admin</span>
          </div>

          <div class="table-responsive">
            <table class="table align-middle">
              <thead class="table-light">
              <tr>
                <th style="width: 90px;">Mã đơn</th>
                <th>Khách hàng</th>
                <th>Sản phẩm</th>
                <th class="text-end" style="width: 140px;">Tổng tiền</th>
                <th style="width: 220px;">Thanh toán</th>
                <th style="width: 150px;">Ngày đặt</th>
                <th style="width: 150px;">Ngày giao</th>
                <th style="width: 170px;">Trạng thái</th>
                <th style="width: 260px;" class="text-center">Hành động</th>
              </tr>
              </thead>

              <tbody>
              <?php if (empty($orders)): ?>
                <tr>
                  <td colspan="9" class="text-center text-muted">Chưa có đơn hàng nào.</td>
                </tr>
              <?php else: ?>

                <?php foreach ($orders as $o): ?>
                  <?php
                    $id = (int)($o['id'] ?? 0);

                    // ----- Trạng thái đơn + badge -----
                    $status     = $o['status'] ?? '';
                    $statusText = function_exists('getStatusLabel') ? getStatusLabel($status) : $status;

                    $badgeClass = 'bg-secondary';
                    if ($status === 'pending')   $badgeClass = 'bg-warning text-dark';
                    if ($status === 'approved')  $badgeClass = 'bg-info text-dark';
                    if ($status === 'shipping')  $badgeClass = 'bg-primary';
                    if ($status === 'done')      $badgeClass = 'bg-success';
                    if ($status === 'cancel')    $badgeClass = 'bg-danger';

                    // ----- Thanh toán -----
                    $pm = $o['payment_method'] ?? 'cod';
                    if ($pm === 'code') $pm = 'qr_bank'; // tương thích dữ liệu cũ

                    $ps = $o['payment_status'] ?? (($pm === 'qr_bank') ? 'unpaid' : 'cod');

                    $payMethodText = ($pm === 'qr_bank') ? 'Chuyển khoản / QR' : 'COD';
                    $payStatusText = function_exists('getPaymentStatusLabel') ? getPaymentStatusLabel($ps) : $ps;

                    $payBadge = 'bg-secondary';
                    if ($ps === 'unpaid')          $payBadge = 'bg-secondary';
                    if ($ps === 'waiting_confirm') $payBadge = 'bg-warning text-dark';
                    if ($ps === 'paid')            $payBadge = 'bg-success';
                    if ($ps === 'cod')             $payBadge = 'bg-info text-dark';

                    // ----- Ngày đặt / ngày giao -----
                    $orderDate = !empty($o['created_at'])
                      ? date('d/m/Y H:i', strtotime($o['created_at']))
                      : '-';

                    $deliveryDate = !empty($o['delivered_at'])
                      ? date('d/m/Y H:i', strtotime($o['delivered_at']))
                      : '-';

                    // ----- Ngày thanh toán -----
                    $paidAt = !empty($o['paid_at'])
                      ? date('d/m/Y H:i', strtotime($o['paid_at']))
                      : '';
                  ?>

                  <tr>
                    <td>#<?php echo $id; ?></td>

                    <td><?php echo h($o['username'] ?? ''); ?></td>

                    <td class="small">
                      <?php if (!empty($o['items']) && is_array($o['items'])): ?>
                        <ul class="mb-0 ps-3">
                          <?php foreach ($o['items'] as $it): ?>
                            <li>
                              <?php echo h($it['name'] ?? ''); ?>
                              <?php if (!empty($it['color'] ?? '')): ?>
                                <span class="text-muted small">(Màu: <?php echo h($it['color']); ?>)</span>
                              <?php endif; ?>
                              x<?php echo (int)($it['qty'] ?? 1); ?>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      <?php else: ?>
                        <span class="text-muted">Không có dữ liệu</span>
                      <?php endif; ?>
                    </td>

                    <td class="text-end">
                      <?php echo number_format($o['total'] ?? 0, 0, ',', '.'); ?>₫
                    </td>

                    <td class="small">
                      <div class="fw-semibold"><?php echo h($payMethodText); ?></div>
                      <div class="mt-1">
                        <span class="badge <?php echo $payBadge; ?>">
                          <?php echo h($payStatusText); ?>
                        </span>
                        <?php if ($paidAt): ?>
                          <div class="text-muted small mt-1">Paid: <?php echo h($paidAt); ?></div>
                        <?php endif; ?>
                      </div>
                    </td>

                    <td class="small"><?php echo h($orderDate); ?></td>

                    <td class="small"><?php echo h($deliveryDate); ?></td>

                    <td>
                      <span class="badge <?php echo $badgeClass; ?>">
                        <?php echo h($statusText); ?>
                      </span>
                    </td>

                    <td class="text-center">
                      <div class="d-inline-flex flex-wrap gap-2 justify-content-center">

                        <!-- ✅ NÚT DUYỆT THANH TOÁN (QR) -->
                        <?php if ($pm === 'qr_bank' && $ps === 'waiting_confirm' && !in_array($status, ['cancel','done'], true)): ?>
                          <form method="post" style="display:inline"
                                onsubmit="return confirm('Xác nhận đơn #<?php echo $id; ?> đã thanh toán?');">
                            <input type="hidden" name="action" value="confirm_paid">
                            <input type="hidden" name="order_id" value="<?php echo $id; ?>">
                            <button type="submit" class="btn btn-sm btn-warning">
                              <i class="bi bi-shield-check"></i> Xác nhận thanh toán
                            </button>
                          </form>
                        <?php endif; ?>

                        <?php if ($status === 'pending'): ?>
                          <a class="btn btn-sm btn-primary"
                             href="admin_order_action.php?id=<?php echo $id; ?>&action=approve">
                            <i class="bi bi-check2-circle"></i> Duyệt (Đang giao)
                          </a>
                        <?php endif; ?>

                        <?php if ($status === 'shipping' || $status === 'approved'): ?>
                          <a class="btn btn-sm btn-success"
                             href="admin_order_action.php?id=<?php echo $id; ?>&action=done">
                            <i class="bi bi-box-seam"></i> Hoàn thành
                          </a>
                        <?php endif; ?>

                        <?php if (in_array($status, ['pending', 'shipping', 'approved'], true)): ?>
                          <a class="btn btn-sm btn-outline-danger"
                             href="admin_order_action.php?id=<?php echo $id; ?>&action=cancel"
                             onclick="return confirm('Bạn chắc chắn muốn hủy đơn #<?php echo $id; ?>?');">
                            <i class="bi bi-x-circle"></i> Hủy đơn
                          </a>
                        <?php endif; ?>

                      </div>

                      <?php if ($pm === 'qr_bank' && $ps === 'unpaid'): ?>
                        <div class="text-muted small mt-2">
                          Khách chưa bấm “Tôi đã thanh toán”.
                        </div>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>

              <?php endif; ?>
              </tbody>
            </table>
          </div>

          <p class="text-muted small mb-0 mt-2">
            • QR: Khách chuyển khoản xong sẽ bấm <b>Tôi đã thanh toán</b> → đơn chuyển sang <b>Chờ xác nhận</b> → admin bấm <b>Xác nhận thanh toán</b>.<br>
            • <b>Chờ duyệt</b> → bấm <b>Duyệt</b> để chuyển sang <b>Đang giao</b>.<br>
            • Giao xong bấm <b>Hoàn thành</b> để chốt đơn (tính vào <b>Báo cáo doanh thu</b>).
          </p>

        </div>
      </div>
    </div>
  </main>

  <!-- FOOTER (GIỮ NGUYÊN CỦA BẠN) -->
  <footer class="footer border-top py-4 mt-3">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="title h5 mb-2">TechStore</div>
          <p class="mb-0">
            Bảng điều khiển Admin TechStore.<br>
            Quản lý đơn hàng, sản phẩm và người dùng.
          </p>
        </div>

        <div>
          <div class="title fw-bold mb-2">Liên hệ kỹ thuật</div>
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
