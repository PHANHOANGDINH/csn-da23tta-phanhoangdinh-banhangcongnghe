<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/products.php';

$u = currentUser(); // ✅ thống nhất với header

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// 3 biến filter để header không lỗi (dù trang này không dùng filter)
$keyword        = $_GET['q'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$brandFilter    = $_GET['brand'] ?? '';

/* =========================
   LOAD SẢN PHẨM + MAP TỒN KHO
========================= */
$productsAll = loadProducts();
$productMap  = [];
foreach ($productsAll as $p) {
  if (!is_array($p) || !isset($p['id'])) continue;
  $productMap[(int)$p['id']] = $p;
}

/* =========================
   GIỎ HÀNG SESSION
========================= */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
  $_SESSION['cart'] = []; // [ product_id => ['qty'=>..., 'color'=>...] ]
}
$cart = &$_SESSION['cart'];

/* =========================
   HANDLE POST (TRƯỚC HTML)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action    = $_POST['action'] ?? '';
  $productId = (int)($_POST['product_id'] ?? 0);
  $qty       = (int)($_POST['qty'] ?? 1);
  $color     = trim($_POST['color'] ?? '');

  // remove 1 item
  if (isset($_POST['remove'])) {
    $removeId = (int)$_POST['remove'];
    if ($removeId > 0 && isset($cart[$removeId])) {
      unset($cart[$removeId]);
    }
    header('Location: cart.php');
    exit;
  }

  // add to cart
  if ($action === 'add' && $productId > 0 && $qty > 0) {
    if (!isset($productMap[$productId])) {
      header('Location: cart.php');
      exit;
    }

    $p     = $productMap[$productId];
    $stock = isset($p['stock']) ? (int)$p['stock'] : 0;

    if ($stock <= 0) {
      header('Location: cart.php');
      exit;
    }

    // current in cart
    $currentQty = 0;
    $currentColor = '';
    if (isset($cart[$productId])) {
      if (is_array($cart[$productId])) {
        $currentQty   = (int)($cart[$productId]['qty'] ?? 0);
        $currentColor = (string)($cart[$productId]['color'] ?? '');
      } else {
        $currentQty = (int)$cart[$productId];
      }
    }

    $newQty = $currentQty + $qty;
    if ($newQty > $stock) $newQty = $stock;

    if ($newQty <= 0) {
      unset($cart[$productId]);
    } else {
      $colorToSave = $color !== '' ? $color : $currentColor;
      $cart[$productId] = [
        'qty'   => $newQty,
        'color' => $colorToSave
      ];
    }

    header('Location: cart.php');
    exit;
  }

  // update cart qty
  if ($action === 'update') {
    if (isset($_POST['item_id'], $_POST['item_qty'])) {
      $ids    = (array)$_POST['item_id'];
      $qtys   = (array)$_POST['item_qty'];
      $colors = isset($_POST['item_color']) ? (array)$_POST['item_color'] : [];

      $newCart = [];

      $n = min(count($ids), count($qtys));
      for ($i = 0; $i < $n; $i++) {
        $id = (int)$ids[$i];
        $q  = (int)$qtys[$i];

        if ($id <= 0) continue;
        if (!isset($productMap[$id])) continue;

        $p     = $productMap[$id];
        $stock = isset($p['stock']) ? (int)$p['stock'] : 0;

        if ($stock <= 0) continue;

        if ($q > $stock) $q = $stock;
        if ($q <= 0) continue;

        $colorItem = '';
        if (array_key_exists($i, $colors)) {
          $colorItem = trim((string)$colors[$i]);
        } elseif (isset($cart[$id]) && is_array($cart[$id])) {
          $colorItem = (string)($cart[$id]['color'] ?? '');
        }

        $newCart[$id] = [
          'qty'   => $q,
          'color' => $colorItem
        ];
      }

      $cart = $newCart;
    }

    header('Location: cart.php');
    exit;
  }
}

/* =========================
   RENDER DATA
========================= */
$map = $productMap;

$items = [];
$total = 0;

foreach ($cart as $pid => $item) {
  $pid = (int)$pid;

  if (!isset($map[$pid])) {
    unset($cart[$pid]);
    continue;
  }

  $p     = $map[$pid];
  $stock = isset($p['stock']) ? (int)$p['stock'] : 0;

  if ($stock <= 0) {
    unset($cart[$pid]);
    continue;
  }

  if (is_array($item)) {
    $qty   = (int)($item['qty'] ?? 0);
    $color = (string)($item['color'] ?? '');
  } else {
    $qty   = (int)$item;
    $color = '';
  }

  if ($qty <= 0) {
    unset($cart[$pid]);
    continue;
  }

  if ($qty > $stock) {
    $qty = $stock;
    if (is_array($item)) $cart[$pid]['qty'] = $qty;
    else $cart[$pid] = $qty;
  }

  $lineTotal = ((int)($p['price'] ?? 0)) * $qty;

  $items[] = [
    'id'    => $pid,
    'name'  => (string)($p['name'] ?? ''),
    'price' => (int)($p['price'] ?? 0),
    'qty'   => $qty,
    'color' => $color,
    'line'  => $lineTotal,
    'stock' => $stock
  ];

  $total += $lineTotal;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Giỏ hàng - TechStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- CSS giao diện -->
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
          <li class="breadcrumb-item active" aria-current="page">Giỏ hàng</li>
        </ol>
      </nav>

      <?php if (empty($items)): ?>
        <div class="card shadow-sm border-0">
          <div class="card-body text-center py-5">
            <div class="mb-3 fs-1 text-muted">
              <i class="bi bi-bag"></i>
            </div>
            <h1 class="h5 mb-2">Giỏ hàng của bạn đang trống</h1>
            <p class="text-muted mb-3">Hãy chọn vài sản phẩm yêu thích tại TechStore nhé.</p>
            <a href="index.php" class="btn btn-primary">
              <i class="bi bi-shop"></i> Tiếp tục mua sắm
            </a>
          </div>
        </div>
      <?php else: ?>

        <div class="row g-3">
          <!-- Bảng giỏ hàng -->
          <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-body">
                <h1 class="h5 mb-3">Giỏ hàng</h1>

                <form method="post">
                  <input type="hidden" name="action" value="update">

                  <div class="table-responsive">
                    <table class="table align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>Sản phẩm</th>
                          <th class="text-end">Giá</th>
                          <th class="text-center">Số lượng</th>
                          <th class="text-end">Thành tiền</th>
                          <th class="text-center">Thao tác</th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($items as $row): ?>
                        <?php
                          $pid      = (int)$row['id'];
                          $brand    = isset($map[$pid]['brand']) ? (string)$map[$pid]['brand'] : '';
                          $stockRow = (int)($row['stock'] ?? 0);
                        ?>
                        <tr>
                          <td style="max-width:260px;">
                            <div class="fw-semibold small">
                              <?= h($row['name']) ?>
                            </div>
                            <?php if ($brand !== ''): ?>
                              <div class="small text-muted">Thương hiệu: <?= h($brand) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($row['color'])): ?>
                              <div class="small text-muted">Màu: <?= h($row['color']) ?></div>
                            <?php endif; ?>
                            <div class="small text-muted">Tồn kho: <?= (int)$stockRow ?></div>
                          </td>

                          <td class="text-end">
                            <?= number_format((int)$row['price'], 0, ',', '.'); ?>₫
                          </td>

                          <td class="text-center" style="width:120px;">
                            <input type="hidden" name="item_id[]" value="<?= (int)$row['id'] ?>">
                            <input type="hidden" name="item_color[]" value="<?= h($row['color'] ?? '') ?>">
                            <input
                              type="number"
                              name="item_qty[]"
                              value="<?= (int)$row['qty'] ?>"
                              min="1"
                              max="<?= $stockRow > 0 ? $stockRow : 1 ?>"
                              class="form-control form-control-sm text-center">
                          </td>

                          <td class="text-end">
                            <?= number_format((int)$row['line'], 0, ',', '.'); ?>₫
                          </td>

                          <td class="text-center">
                            <button
                              class="btn btn-sm btn-outline-danger"
                              type="submit"
                              name="remove"
                              value="<?= (int)$row['id'] ?>">
                              <i class="bi bi-x-circle"></i>
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                  <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
                    <div>
                      <a href="index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Tiếp tục mua sắm
                      </a>
                    </div>
                    <div class="d-flex gap-2">
                      <button class="btn btn-sm btn-outline-primary" type="submit">
                        Cập nhật giỏ hàng
                      </button>
                      <a href="checkout.php" class="btn btn-sm btn-primary">
                        Tiến hành thanh toán
                      </a>
                    </div>
                  </div>
                </form>

              </div>
            </div>
          </div>

          <!-- Tổng tiền -->
          <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0">
              <div class="card-body">
                <h2 class="h6 mb-3">Tóm tắt đơn hàng</h2>
                <div class="d-flex justify-content-between mb-1">
                  <span>Tạm tính</span>
                  <span><?= number_format((int)$total, 0, ',', '.'); ?>₫</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>Phí vận chuyển (ước tính)</span>
                  <span class="text-success">Miễn phí</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                  <span class="fw-semibold">Tổng cộng</span>
                  <span class="fw-bold text-danger">
                    <?= number_format((int)$total, 0, ',', '.'); ?>₫
                  </span>
                </div>
                <a href="checkout.php" class="btn btn-danger w-100">
                  Thanh toán ngay
                </a>
                <p class="small text-muted mt-2 mb-0">
                  Thông tin giao hàng sẽ được lấy từ <a href="profile.php">Hồ sơ của bạn</a>.
                </p>
              </div>
            </div>
          </div>

        </div>

      <?php endif; ?>
    </div>
  </main>

  <!-- FOOTER (GIỮ NGUYÊN CỦA BẠN) -->
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
