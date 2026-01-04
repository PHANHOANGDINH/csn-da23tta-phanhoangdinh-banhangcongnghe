<?php
require_once 'auth.php';
require_once 'products.php';

$user = currentUser();

// ✅ biến cho header search (đồng bộ index)
$keyword        = trim($_GET['q'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$brandFilter    = trim($_GET['brand'] ?? '');

// Load products map
$products = loadProducts();
$productMap = [];
foreach ($products as $p) {
  if (!is_array($p) || !isset($p['id'])) continue;
  $productMap[(int)$p['id']] = $p;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!isset($productMap[$id])) {
  http_response_code(404);
  die("Sản phẩm không tồn tại.");
}
$product = $productMap[$id];

// Tồn kho
$stock        = isset($product['stock']) ? (int)$product['stock'] : 0;
$isOutOfStock = ($stock <= 0);

// Danh sách ảnh
$images = $product['images'] ?? [];
if ((!is_array($images) || empty($images)) && !empty($product['image'] ?? '')) {
  $images = [$product['image']];
}
$mainImage = (is_array($images) && !empty($images)) ? $images[0] : '';

// Rating (comments.json)
$avgRating    = 0;
$ratingCount  = 0;
$commentsFile = __DIR__ . '/data/comments.json';
if (file_exists($commentsFile)) {
  $clist = json_decode(file_get_contents($commentsFile), true);
  if (is_array($clist)) {
    $sum = 0; $count = 0;
    foreach ($clist as $c) {
      if (!is_array($c)) continue;
      if (($c['product_id'] ?? null) == $product['id'] && isset($c['rating'])) {
        $sum   += (int)$c['rating'];
        $count += 1;
      }
    }
    if ($count > 0) {
      $avgRating   = $sum / $count;
      $ratingCount = $count;
    }
  }
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title><?php echo h($product['name']); ?> • TechStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="style.css">

  <style>
    /* ✅ gọn nhẹ, không phá style.css */
    .pd-thumbs .pd-thumb{ border:0; background:transparent; padding:0; }
    .pd-thumbs .pd-thumb img{ border-radius:12px; border:1px solid rgba(0,0,0,.08); }
    .pd-mainimg img{ width:100%; height:auto; display:block; }
  </style>
</head>

<body class="d-flex flex-column min-vh-100">

  <!-- TOPBAR -->
  <div class="topbar small">
    <div class="container d-flex justify-content-between">
      <div>Hệ thống cửa hàng công nghệ TechStore</div>
      <div class="d-none d-md-block">Miễn phí giao hàng toàn quốc • Hỗ trợ: 0967 492 242</div>
    </div>
  </div>

  <!-- HEADER (STICKY như index) -->
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

      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active" aria-current="page"><?php echo h($product['name']); ?></li>
        </ol>
      </nav>

      <!-- Product detail -->
      <div class="pd-card mb-4">
        <div class="pd-body">
          <div class="row g-4 align-items-start">

            <!-- Gallery -->
            <div class="col-12 col-md-5 col-lg-4">
              <?php if (!empty($mainImage)): ?>
                <div class="pd-mainimg">
                  <img src="uploads/<?php echo h($mainImage); ?>" alt="<?php echo h($product['name']); ?>">
                </div>

                <?php if (is_array($images) && count($images) > 1): ?>
                  <div class="pd-thumbs mt-2 d-flex gap-2 flex-wrap">
                    <?php foreach ($images as $img): ?>
                      <button class="pd-thumb" type="button"
                              onclick="document.querySelector('.pd-mainimg img').src=this.dataset.src"
                              data-src="uploads/<?php echo h($img); ?>">
                        <img src="uploads/<?php echo h($img); ?>" alt="" width="64" height="64">
                      </button>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              <?php else: ?>
                <div class="pd-noimg">Không có ảnh</div>
              <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="col-12 col-md-7 col-lg-8">
              <h1 class="pd-title"><?php echo h($product['name']); ?></h1>

              <div class="pd-meta">
                <span>Mã: <b><?php echo (int)$product['id']; ?></b></span>
                <span class="dot">•</span>
                <span><?php echo h($product['category'] ?? 'Sản phẩm công nghệ'); ?></span>
                <?php if (!empty($product['brand'] ?? '')): ?>
                  <span class="dot">•</span>
                  <span class="up"><?php echo h($product['brand']); ?></span>
                <?php endif; ?>
              </div>

              <div class="mb-2">
                <?php if ($isOutOfStock): ?>
                  <span class="badge bg-secondary">Hết hàng</span>
                <?php else: ?>
                  <span class="badge bg-success-subtle text-success">Còn <?php echo $stock; ?> sản phẩm</span>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <?php if ($ratingCount > 0): ?>
                  <?php $rounded = (int)round($avgRating); ?>
                  <div class="d-flex align-items-center gap-2">
                    <div class="text-warning">
                      <?php for ($i=1; $i<=5; $i++): ?>
                        <?php echo $i <= $rounded ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>'; ?>
                      <?php endfor; ?>
                    </div>
                    <div class="small text-muted">
                      <b><?php echo number_format($avgRating, 1); ?>/5</b> (<?php echo $ratingCount; ?> lượt)
                    </div>
                  </div>
                <?php else: ?>
                  <div class="small text-muted">Chưa có đánh giá.</div>
                <?php endif; ?>
              </div>

              <div class="pd-price mb-3">
                <?php echo number_format((int)($product['price'] ?? 0), 0, ',', '.'); ?>₫
              </div>

              <!-- Specs -->
              <?php
              $specs = $product['specs'] ?? [];
              $hasSpecs = (is_array($specs) && !empty($specs)) || (is_string($specs) && trim($specs) !== '');
              if ($hasSpecs):
              ?>
                <div class="pd-spec mb-3">
                  <div class="pd-spec__title">Thông số kỹ thuật</div>
                  <ul class="spec-list">
                    <?php
                    if (is_array($specs) && !empty($specs)) {
                      foreach ($specs as $k => $v) {
                        // assoc hoặc list đều xử lý ổn
                        if (is_int($k)) {
                          $line = trim((string)$v);
                          if ($line === '') continue;
                          echo '<li>• '.h($line).'</li>';
                        } else {
                          $kk = trim((string)$k);
                          $vv = trim((string)$v);
                          if ($kk === '' && $vv === '') continue;
                          echo '<li><strong>'.h($kk).'</strong>'.($vv!==''?': '.h($vv):'').'</li>';
                        }
                      }
                    } elseif (is_string($specs)) {
                      $lines = preg_split('/\r\n|\r|\n/', $specs);
                      foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line === '') continue;
                        echo '<li>• '.h($line).'</li>';
                      }
                    }
                    ?>
                  </ul>
                </div>
              <?php endif; ?>

              <!-- Add to cart -->
              <form method="post" action="cart.php" class="pd-buy">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">

                <?php
                $colors = $product['colors'] ?? [];
                if (is_array($colors) && !empty($colors)):
                ?>
                  <div class="mb-3">
                    <label class="form-label mb-1 d-block fw-bold">Màu sắc</label>
                    <div class="d-flex flex-wrap gap-2">
                      <?php foreach ($colors as $idx => $color): ?>
                        <?php $colorValue = h($color); ?>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="color"
                                 id="color_<?php echo (int)$idx; ?>"
                                 value="<?php echo $colorValue; ?>"
                                 <?php echo $idx === 0 ? 'checked' : ''; ?>
                                 <?php echo $isOutOfStock ? 'disabled' : ''; ?>>
                          <label class="form-check-label" for="color_<?php echo (int)$idx; ?>"><?php echo $colorValue; ?></label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>

                <div class="row g-2 align-items-end mb-3">
                  <div class="col-auto">
                    <label class="form-label mb-1 fw-bold">Số lượng</label>
                    <input type="number" name="qty" value="1" min="1"
                           <?php if (!$isOutOfStock && $stock > 0): ?>max="<?php echo $stock; ?>"<?php endif; ?>
                           class="form-control" style="max-width:120px;"
                           <?php echo $isOutOfStock ? 'disabled' : ''; ?>>
                  </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                  <?php if ($isOutOfStock): ?>
                    <button class="btn btn-secondary" type="button" disabled>Hết hàng</button>
                  <?php else: ?>
                    <button class="btn btn-primary" type="submit">
                      <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                    </button>
                    <a class="btn btn-outline-primary"
                       href="checkout.php?product_id=<?php echo (int)$product['id']; ?>&qty=1">
                      Mua ngay
                    </a>
                  <?php endif; ?>
                </div>
              </form>

            </div>
          </div>
        </div>
      </div>

      <!-- Comments -->
      <section class="ts-section pt-0">
        <?php require 'product_detail_comments.php'; ?>
      </section>

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
          <p class="mb-1">
            Email:
            <a href="mailto:phanhoangdinh106@gmail.com" class="text-break">phanhoangdinh106@gmail.com</a>
          </p>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
