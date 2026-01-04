<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/products.php';

requireLogin();
requireAdmin();

$u = currentUser();
date_default_timezone_set('Asia/Ho_Chi_Minh');

/* =========================
   HELPERS cho header dùng chung
========================= */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// 3 biến filter để header không lỗi (dù trang admin không dùng)
$keyword        = $_GET['q'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$brandFilter    = $_GET['brand'] ?? '';

$orders = loadOrders();

/* =========================
   MAP PRODUCT ID -> PRODUCT (để lấy brand chuẩn)
========================= */
$products   = loadProducts();
$productMap = [];
foreach ($products as $p) {
  if (!isset($p['id'])) continue;
  $pid = (string)(int)$p['id'];     // ép về string id để map thống nhất
  $productMap[$pid] = $p;
}

/* ============================================
   1) THỐNG KÊ THEO THÁNG (CHỈ LẤY ĐƠN 'done')
============================================ */
$statsByMonth = []; // key: Y-m

foreach ($orders as $o) {
  $status  = $o['status']     ?? '';
  $created = $o['created_at'] ?? '';

  if ($status !== 'done' || $created === '') continue;

  $ts = strtotime($created);
  if ($ts === false) continue;

  $key   = date('Y-m', $ts);
  $label = date('m/Y', $ts);

  if (!isset($statsByMonth[$key])) {
    $statsByMonth[$key] = [
      'label'   => $label,
      'year'    => (int)date('Y', $ts),
      'month'   => (int)date('m', $ts),
      'revenue' => 0,
      'orders'  => 0,
    ];
  }

  $statsByMonth[$key]['revenue'] += (int)($o['total'] ?? 0);
  $statsByMonth[$key]['orders']  += 1;
}

uksort($statsByMonth, fn($a,$b) => strcmp($a,$b));

$chartMonthLabels  = [];
$chartMonthRevenue = [];
$chartMonthOrders  = [];

$totalRevenueAll = 0;
$totalOrdersAll  = 0;

foreach ($statsByMonth as $m) {
  $chartMonthLabels[]  = $m['label'];
  $chartMonthRevenue[] = $m['revenue'];
  $chartMonthOrders[]  = $m['orders'];

  $totalRevenueAll += $m['revenue'];
  $totalOrdersAll  += $m['orders'];
}

$monthCount = count($statsByMonth);

/* ============================================
   2) TOP SẢN PHẨM BÁN CHẠY (CHỈ ĐƠN 'done')
============================================ */
$productStats = []; // key: productKey

foreach ($orders as $o) {
  if (($o['status'] ?? '') !== 'done') continue;
  if (empty($o['items']) || !is_array($o['items'])) continue;

  foreach ($o['items'] as $it) {
    // ưu tiên product_id
    $rawPid = $it['product_id'] ?? $it['id'] ?? null;

    // chuẩn hóa pid
    $pid = null;
    if ($rawPid !== null && $rawPid !== '') {
      $pid = (string)(int)$rawPid; // id chuẩn
    }

    $pname = (string)($it['name'] ?? 'Sản phẩm không rõ');
    $qty   = (int)($it['qty'] ?? 1);
    if ($qty < 1) $qty = 1;

    $price = (int)($it['price'] ?? 0);
    if ($price < 0) $price = 0;

    // nếu không có id → dùng key theo tên để vẫn thống kê được
    $key = $pid ? ('id_' . $pid) : ('name_' . md5(mb_strtolower($pname)));

    // lấy brand từ productMap nếu có pid
    $brand = 'Khác';
    if ($pid && isset($productMap[$pid]) && !empty($productMap[$pid]['brand'])) {
      $brand = (string)$productMap[$pid]['brand'];
    }

    if (!isset($productStats[$key])) {
      $productStats[$key] = [
        'id'      => $pid ?? '',
        'name'    => $pname,
        'brand'   => $brand,
        'qty'     => 0,
        'revenue' => 0,
      ];
    } else {
      // nếu trước đó brand = Khác mà giờ có brand thật → cập nhật
      if (($productStats[$key]['brand'] ?? 'Khác') === 'Khác' && $brand !== 'Khác') {
        $productStats[$key]['brand'] = $brand;
      }
    }

    $productStats[$key]['qty']     += $qty;
    $productStats[$key]['revenue'] += $qty * $price;
  }
}

$productStatsList = array_values($productStats);

usort($productStatsList, function($a, $b){
  if (($a['qty'] ?? 0) === ($b['qty'] ?? 0)) {
    return ($b['revenue'] ?? 0) <=> ($a['revenue'] ?? 0);
  }
  return ($b['qty'] ?? 0) <=> ($a['qty'] ?? 0);
});

$topLimit    = 10;
$topProducts = array_slice($productStatsList, 0, $topLimit);

$chartTopNames = [];
$chartTopQty   = [];
foreach ($topProducts as $p) {
  $brand = $p['brand'] ?? 'Khác';
  $chartTopNames[] = ($p['name'] ?? '') . ' (' . $brand . ')';
  $chartTopQty[]   = (int)($p['qty'] ?? 0);
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Báo cáo doanh thu - TechStore (Admin)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- CSS giao diện -->
  <link rel="stylesheet" href="style.css?v=1">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="ts-admin d-flex flex-column min-vh-100">
  <!-- TOPBAR -->
  <div class="topbar small">
    <div class="container d-flex justify-content-between">
      <div>Báo cáo doanh thu • Admin TechStore</div>
      <div class="d-none d-md-block">
        Xin chào, <?= h($u['username'] ?? '') ?>
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
              value="<?= h($keyword) ?>"
              placeholder="Nhập tên điện thoại, laptop, phụ kiện... cần tìm">

            <?php if ($categoryFilter !== ''): ?>
              <input type="hidden" name="category" value="<?= h($categoryFilter) ?>">
            <?php endif; ?>
            <?php if ($brandFilter !== ''): ?>
              <input type="hidden" name="brand" value="<?= h($brandFilter) ?>">
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
                <span><?= h($u['username'] ?? '') ?></span>
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
          <li class="breadcrumb-item active" aria-current="page">Admin • Báo cáo doanh thu</li>
        </ol>
      </nav>

      <!-- THẺ TỔNG QUAN -->
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <div class="text-muted small mb-1">Tổng doanh thu (tất cả đơn hoàn thành)</div>
              <div class="fs-4 fw-bold text-success">
                <?= number_format($totalRevenueAll, 0, ',', '.'); ?>₫
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <div class="text-muted small mb-1">Tổng số đơn hoàn thành</div>
              <div class="fs-4 fw-bold">
                <?= (int)$totalOrdersAll; ?> đơn
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <div class="text-muted small mb-1">Số tháng có doanh thu</div>
              <div class="fs-4 fw-bold">
                <?= (int)$monthCount; ?> tháng
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- BIỂU ĐỒ & BẢNG -->
      <div class="row g-3">
        <!-- Cột trái -->
        <div class="col-lg-7">
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
              <h2 class="h6 mb-3">Doanh thu & số đơn theo tháng</h2>
              <canvas id="chartMonthly" height="140"></canvas>
            </div>
          </div>

          <div class="card shadow-sm border-0">
            <div class="card-body">
              <h2 class="h6 mb-3">Bảng doanh thu theo tháng</h2>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Tháng</th>
                      <th class="text-end">Số đơn</th>
                      <th class="text-end">Doanh thu</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php if (empty($statsByMonth)): ?>
                    <tr>
                      <td colspan="3" class="text-center text-muted">
                        Chưa có đơn hoàn thành nào để tính doanh thu.
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($statsByMonth as $m): ?>
                      <tr>
                        <td><?= h($m['label']); ?></td>
                        <td class="text-end"><?= (int)$m['orders']; ?></td>
                        <td class="text-end"><?= number_format($m['revenue'], 0, ',', '.'); ?>₫</td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Cột phải -->
        <div class="col-lg-5">
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
              <h2 class="h6 mb-3">Top sản phẩm bán chạy (theo số lượng)</h2>
              <canvas id="chartTopProducts" height="160"></canvas>
            </div>
          </div>

          <div class="card shadow-sm border-0">
            <div class="card-body">
              <h2 class="h6 mb-3">Danh sách Top sản phẩm</h2>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>Sản phẩm</th>
                      <th>Thương hiệu</th>
                      <th class="text-end">SL bán</th>
                      <th class="text-end">Doanh thu</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php if (empty($topProducts)): ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted">
                        Chưa có dữ liệu sản phẩm bán chạy.
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php $i = 1; ?>
                    <?php foreach ($topProducts as $p): ?>
                      <tr>
                        <td><?= $i++; ?></td>
                        <td><?= h($p['name'] ?? ''); ?></td>
                        <td><?= h($p['brand'] ?? 'Khác'); ?></td>
                        <td class="text-end"><?= (int)($p['qty'] ?? 0); ?></td>
                        <td class="text-end"><?= number_format((int)($p['revenue'] ?? 0), 0, ',', '.'); ?>₫</td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
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
            Báo cáo doanh thu & thống kê bán hàng cho Admin TechStore.
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

  <script>
    const monthLabels  = <?php echo json_encode($chartMonthLabels, JSON_UNESCAPED_UNICODE); ?>;
    const monthRevenue = <?php echo json_encode($chartMonthRevenue, JSON_NUMERIC_CHECK); ?>;
    const monthOrders  = <?php echo json_encode($chartMonthOrders, JSON_NUMERIC_CHECK); ?>;

    const topNames = <?php echo json_encode($chartTopNames, JSON_UNESCAPED_UNICODE); ?>;
    const topQty   = <?php echo json_encode($chartTopQty, JSON_NUMERIC_CHECK); ?>;

    // Doanh thu & số đơn theo tháng
    if (monthLabels.length > 0) {
      const ctx1 = document.getElementById('chartMonthly').getContext('2d');

      new Chart(ctx1, {
        type: 'bar',
        data: {
          labels: monthLabels,
          datasets: [
            { label: 'Doanh thu (₫)', data: monthRevenue, yAxisID: 'y1' },
            { label: 'Số đơn', data: monthOrders, type: 'line', yAxisID: 'y2', tension: 0.25 }
          ]
        },
        options: {
          responsive: true,
          interaction: { mode: 'index', intersect: false },
          stacked: false,
          scales: {
            y1: {
              type: 'linear',
              position: 'left',
              beginAtZero: true,
              ticks: {
                callback: (value) => Number(value).toLocaleString('vi-VN')
              }
            },
            y2: {
              type: 'linear',
              position: 'right',
              beginAtZero: true,
              min: 0,
              ticks: { stepSize: 1, precision: 0 },
              grid: { drawOnChartArea: false }
            }
          }
        }
      });
    }

    // Top sản phẩm
    if (topNames.length > 0) {
      const ctx2 = document.getElementById('chartTopProducts').getContext('2d');

      new Chart(ctx2, {
        type: 'bar',
        data: {
          labels: topNames,
          datasets: [{ label: 'Số lượng bán', data: topQty }]
        },
        options: {
          indexAxis: 'y',
          responsive: true
        }
      });
    }
  </script>
</body>
</html>
