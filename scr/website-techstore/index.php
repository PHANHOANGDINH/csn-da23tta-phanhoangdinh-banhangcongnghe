<?php
require_once 'auth.php';
require_once 'products.php';

$u        = currentUser();
$products = loadProducts();

date_default_timezone_set('Asia/Ho_Chi_Minh');

/** Danh mục cố định */
$fixedCategories = ['Điện thoại','Laptop','PC - Màn hình','Âm thanh','Phụ kiện'];

/** Banner dọc cho từng danh mục */
$categorySideBanners = [
  'Điện thoại'     => 'img/side/side1.png',
  'Laptop'         => 'img/side/side2.png',
  'PC - Màn hình'  => 'img/side/side3.png',
  'Âm thanh'       => 'img/side/side4.png',
  'Phụ kiện'       => 'img/side/side5.png',
];

/** Banner ngang ngăn giữa danh mục */
$wideBanners = [
  'between_phone_laptop' => [
    'img'  => 'img/wide/wide1.png',
    'k'    => 'SALE CUỐI TUẦN',
    't'    => 'Giảm đến 50k tất cả các đơn hàng khi nhập mã WELCOME50K',
    'd'    => 'Số lượng có hạn – áp dụng khi thanh toán',
    'link' => 'index.php?category=Phụ%20kiện'
  ],
  'between_laptop_pc' => [
    'img'  => 'img/wide/wide2.png',
    'k'    => 'DEAL HOT',
    't'    => 'Laptop văn phòng giá tốt',
    'd'    => 'Giao nhanh – ưu đãi theo sản phẩm',
    'link' => 'index.php?category=Laptop'
  ],
  'between_pc_audio' => [
    'img'  => 'img/wide/wide3.png',
    'k'    => 'FLASH SALE',
    't'    => 'Màn hình – PC đồng giá cực sốc',
    'd'    => 'Ưu đãi theo từng sản phẩm',
    'link' => 'index.php?category=PC%20-%20M%C3%A0n%20h%C3%ACnh'
  ],
  'between_audio_accessory' => [
    'img'  => 'img/wide/wide4.png',
    'k'    => 'ƯU ĐÃI HÔM NAY',
    't'    => 'Âm thanh chính hãng',
    'd'    => 'Tai nghe – loa – soundbar',
    'link' => 'index.php?category=%C3%82m%20thanh'
  ],
];

/** Helpers */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Build link giữ params hiện tại + override */
function buildLink(array $over = []) {
  $params = [];
  if (isset($_GET['q']))        $params['q']        = trim($_GET['q']);
  if (isset($_GET['category'])) $params['category'] = trim($_GET['category']);
  if (isset($_GET['brand']))    $params['brand']    = trim($_GET['brand']);
  if (isset($_GET['sort']))     $params['sort']     = trim($_GET['sort']);
  if (isset($_GET['page']))     $params['page']     = (int)$_GET['page'];

  foreach ($over as $k => $v) {
    if ($v === null || $v === '') unset($params[$k]);
    else $params[$k] = $v;
  }

  // Khi đổi filter/sort thì reset page
  if (isset($over['q']) || isset($over['category']) || isset($over['brand']) || isset($over['sort'])) {
    unset($params['page']);
  }

  $qs = http_build_query($params);
  return $qs ? ('index.php?' . $qs) : 'index.php';
}

/** ✅ Tự map logo sang _strip / _mini (nếu có) */
function logoVariant($path, $variant) {
  if (!$path) return '';
  $ext  = pathinfo($path, PATHINFO_EXTENSION);
  $base = substr($path, 0, -(strlen($ext) + 1));
  $cand = $base . '_' . $variant . '.png';

  $fsCand = __DIR__ . '/' . ltrim($cand, '/');
  $fsOrig = __DIR__ . '/' . ltrim($path, '/');

  if (is_file($fsCand)) return $cand;
  if (is_file($fsOrig)) return $path;
  return $cand;
}

/** Render card sản phẩm */
function renderProductCard(array $p) {
  $id = isset($p['id']) ? (int)$p['id'] : 0;
  if ($id === 0) return;

  $name     = $p['name']     ?? 'Sản phẩm chưa đặt tên';
  $category = $p['category'] ?? 'Sản phẩm công nghệ';
  $price    = isset($p['price']) ? (int)$p['price'] : 0;

  $image = $p['image'] ?? '';
  if (isset($p['images']) && is_array($p['images']) && count($p['images']) > 0) {
    $image = $p['images'][0];
  }

  $brand      = $p['brand'] ?? '';
  $colors     = (isset($p['colors']) && is_array($p['colors'])) ? $p['colors'] : [];
  $colorCount = count($colors);

  $stock        = isset($p['stock']) ? (int)$p['stock'] : 0;
  $isOutOfStock = ($stock <= 0);
  ?>
  <div class="ts-card">
    <a class="ts-card__media" href="product_detail.php?id=<?php echo $id; ?>">
      <?php if ($image !== ''): ?>
        <img
          src="uploads/<?php echo h($image); ?>"
          alt="<?php echo h($name); ?>"
          onerror="this.style.display='none'; this.parentElement.querySelector('.ts-noimg').style.display='flex';">
        <div class="ts-noimg" style="display:none;">Không có ảnh</div>
      <?php else: ?>
        <div class="ts-noimg" style="display:flex;">Không có ảnh</div>
      <?php endif; ?>
    </a>

    <div class="ts-card__body">
      <a class="ts-card__name" href="product_detail.php?id=<?php echo $id; ?>"><?php echo h($name); ?></a>

      <div class="ts-card__meta">
        <?php echo h($category); ?>
        <?php if ($brand !== ''): ?>
          <span class="dot">•</span><span class="up"><?php echo h($brand); ?></span>
        <?php endif; ?>
      </div>

      <?php if ($colorCount > 0): ?>
        <div class="ts-card__muted"><?php echo $colorCount; ?> lựa chọn màu</div>
      <?php endif; ?>

      <div class="ts-card__star">★★★★★</div>

      <div class="ts-card__stock">
        <?php if ($isOutOfStock): ?>
          <span class="badge bg-secondary">Hết hàng</span>
        <?php else: ?>
          <span class="badge bg-success-subtle text-success">Còn <?php echo $stock; ?> sản phẩm</span>
        <?php endif; ?>
      </div>

      <div class="ts-card__price">
        <span class="now"><?php echo number_format($price, 0, ',', '.'); ?>₫</span>
      </div>

      <div class="ts-card__actions">
        <a class="btn btn-sm btn-outline-primary w-100" href="product_detail.php?id=<?php echo $id; ?>">Xem chi tiết</a>

        <form method="post" action="cart.php">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?php echo $id; ?>">
          <?php if ($colorCount > 0 && isset($colors[0])): ?>
            <input type="hidden" name="color" value="<?php echo h($colors[0]); ?>">
          <?php endif; ?>

          <?php if ($isOutOfStock): ?>
            <button class="btn btn-sm btn-secondary w-100" type="button" disabled>Hết hàng</button>
          <?php else: ?>
            <button class="btn btn-sm btn-primary w-100" type="submit">
              <i class="bi bi-cart-plus"></i> Thêm vào giỏ
            </button>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
  <?php
}

/** Block kiểu banner trái + 8 sp phải */
function renderCategoryBannerBlock($title, $subtitle, $moreLink, $bannerImg, array $items) {
  if (empty($items)) return; ?>
  <section class="ts-section">
    <div class="container">
      <div class="ts-secHead">
        <div>
          <div class="ts-h"><?php echo h($title); ?></div>
          <div class="ts-sub"><?php echo h($subtitle); ?></div>
        </div>
        <a class="ts-secHead__more" href="<?php echo h($moreLink); ?>">Xem tất cả</a>
      </div>

      <div class="ts-catblock">
        <a class="ts-catblock__banner" href="<?php echo h($moreLink); ?>">
          <img src="<?php echo h($bannerImg); ?>" alt="<?php echo h($title); ?>"
               onerror="this.style.display='none'; this.parentElement.classList.add('noimg');">
        </a>

        <div class="ts-catblock__grid">
          <?php foreach ($items as $p): ?>
            <div class="ts-catblock__item">
              <?php renderProductCard($p); ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
<?php }

/** Banner ngang */
function renderWideBanner($img, $k, $t, $d, $link) { ?>
  <section class="ts-section pt-0">
    <div class="container">
      <a class="ts-wideBanner" href="<?php echo h($link); ?>">
        <img src="<?php echo h($img); ?>" alt="<?php echo h($t); ?>"
             onerror="this.style.display='none'; this.parentElement.classList.add('noimg');">
        <div class="ts-wideBanner__overlay">
          <div class="k"><?php echo h($k); ?></div>
          <div class="t"><?php echo h($t); ?></div>
          <div class="d"><?php echo h($d); ?></div>
        </div>
      </a>
    </div>
  </section>
<?php }

/** Mobile top promos */
function renderMobileTopPromos() { ?>
  <section class="ts-section pt-2 d-md-none">
    <div class="container">
      <div class="ts-mtop">
        <div class="ts-mtop__scroll">
          <a class="ts-mpromo" href="index.php?category=Điện%20thoại">
            <img src="img/mobile/anh1.png" alt="Deal Điện thoại"
                 onerror="this.style.display='none'; this.parentElement.classList.add('noimg');">
            <div class="ts-mpromo__txt">
              <div class="t">Deal Điện thoại</div>
              <div class="d">Giá tốt mỗi ngày</div>
            </div>
          </a>

          <a class="ts-mpromo" href="index.php?category=Phụ%20kiện">
            <img src="img/mobile/anh2.png" alt="Phụ kiện hot"
                 onerror="this.style.display='none'; this.parentElement.classList.add('noimg');">
            <div class="ts-mpromo__txt">
              <div class="t">Phụ kiện hot</div>
              <div class="d">Giảm sâu hôm nay</div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </section>
<?php }

function slice8(array $arr) { return array_slice($arr, 0, 8); }

/** ====== LỌC & TÌM KIẾM ====== */
$keyword        = trim($_GET['q'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$brandFilter    = trim($_GET['brand'] ?? '');
$sort           = trim($_GET['sort'] ?? ''); // ✅ bỏ "nổi bật" (featured)
$keywordLower   = mb_strtolower($keyword, 'UTF-8');

if ($categoryFilter !== '' && !in_array($categoryFilter, $fixedCategories, true)) {
  $categoryFilter = '';
}

/** Logo hãng theo danh mục */
$brandLogosByCat = [
  'Điện thoại' => [
    'Apple'   => 'img/brands/Apple-Logo.png',
    'Samsung' => 'img/brands/samsung.png',
    'OPPO'    => 'img/brands/oppo.png',
    'Xiaomi'  => 'img/brands/xiaomi.png',
    'realme'  => 'img/brands/realme.png',
    'vivo'    => 'img/brands/vivo.png',
    'Nokia'   => 'img/brands/nokia.png',
  ],
  'Laptop' => [
    'Apple'   => 'img/brands/Apple-Logo.png',
    'Asus'    => 'img/brands/asus.png',
    'Acer'    => 'img/brands/acer.png',
    'Dell'    => 'img/brands/dell.png',
    'HP'      => 'img/brands/hp.png',
    'Lenovo'  => 'img/brands/lenovo.png',
    'MSI'     => 'img/brands/msi.png',
  ],
  'PC - Màn hình' => [
    'LG'        => 'img/brands/lg.png',
    'ViewSonic' => 'img/brands/viewsonic.png',
    'Gigabyte'  => 'img/brands/gigabyte.png',
    'AOC'       => 'img/brands/aoc.png',
  ],
  'Âm thanh' => [
    'Sony'       => 'img/brands/sony.png',
    'JBL'        => 'img/brands/jbl.png',
    'Anker'      => 'img/brands/anker.png',
    'Sennheiser' => 'img/brands/sennheiser.png',
    'Bose'       => 'img/brands/bose.png',
    'Marshall'   => 'img/brands/marshall.png',
  ],
  'Phụ kiện' => [
    'Apple'     => 'img/brands/Apple-Logo.png',
    'Samsung'   => 'img/brands/samsung.png',
    'Baseus'    => 'img/brands/baseus.png',
    'Ugreen'    => 'img/brands/ugreen.png',
    'Remax'     => 'img/brands/remax.png',
    'Energizer' => 'img/brands/energizer.png',
    'WD'        => 'img/brands/wd.png',
    'Seagate'   => 'img/brands/seagate.png',
    'Kingston'  => 'img/brands/kingston.png',
    'SanDisk'   => 'img/brands/sandisk.png',
    'Lexar'     => 'img/brands/lexar.png',
    'Garmin'    => 'img/brands/garmin.png',
    'Amazfit'   => 'img/brands/amazfit.png',
    'Huawei'    => 'img/brands/huawei.png',
    'Xiaomi'    => 'img/brands/xiaomi.png',
  ],
];

/** Map phẳng brand => logo */
$brandLogos = [];
foreach ($brandLogosByCat as $catName => $brandList) {
  foreach ($brandList as $brandName => $logoPath) {
    $brandLogos[$brandName] = $logoPath;
  }
}

/** Danh sách thương hiệu hiển thị theo category */
$brands = [];
if ($categoryFilter !== '' && isset($brandLogosByCat[$categoryFilter])) {
  $brands = array_keys($brandLogosByCat[$categoryFilter]);
} else {
  foreach ($products as $p) {
    if (!is_array($p)) continue;
    $b = trim($p['brand'] ?? '');
    if ($b !== '' && !in_array($b, $brands, true)) $brands[] = $b;
  }
}
sort($brands);

if ($brandFilter !== '' && !in_array($brandFilter, $brands, true)) {
  $brandFilter = '';
}

/** Áp dụng filter + search */
$filtered = [];
foreach ($products as $p) {
  if (!is_array($p)) continue;
  $ok = true;

  if ($keyword !== '') {
    $nameLower = mb_strtolower($p['name'] ?? '', 'UTF-8');
    $catLower  = mb_strtolower($p['category'] ?? '', 'UTF-8');
    if (mb_strpos($nameLower, $keywordLower) === false &&
        mb_strpos($catLower,  $keywordLower) === false) {
      $ok = false;
    }
  }

  if ($ok && $categoryFilter !== '') {
    if (strcasecmp($p['category'] ?? '', $categoryFilter) !== 0) $ok = false;
  }

  if ($ok && $brandFilter !== '') {
    if (strcasecmp($p['brand'] ?? '', $brandFilter) !== 0) $ok = false;
  }

  if ($ok) $filtered[] = $p;
}

if ($keyword === '' && $categoryFilter === '' && $brandFilter === '') {
  $filtered = $products;
}

/** ====== SORT (CHỈ GIÁ TĂNG/GIẢM — bỏ "nổi bật" & "0%") ====== */
$priceOf = function($p){
  $raw = $p['price'] ?? 0;
  if (is_numeric($raw)) return (float)$raw;
  $n = preg_replace('/[^\d]/', '', (string)$raw);
  return $n ? (float)$n : 0;
};

if (!empty($filtered)) {
  if ($sort === 'price_asc') {
    usort($filtered, fn($a,$b) => $priceOf($a) <=> $priceOf($b));
  } elseif ($sort === 'price_desc') {
    usort($filtered, fn($a,$b) => $priceOf($b) <=> $priceOf($a));
  } else {
    // không sort => giữ nguyên
    $sort = '';
  }
}

/** ====== PHÂN TRANG ====== */
$perPage      = 8;
$page         = max(1, (int)($_GET['page'] ?? 1));
$totalResults = count($filtered);
$totalPages   = ($totalResults > 0) ? (int)ceil($totalResults / $perPage) : 1;
if ($page > $totalPages) $page = $totalPages;

$pageResults = $filtered;
$isFiltering = (bool)($keyword || $categoryFilter || $brandFilter);
if ($isFiltering) {
  $offset      = ($page - 1) * $perPage;
  $pageResults = array_slice($filtered, $offset, $perPage);
}

/** Chia sản phẩm theo danh mục (HOME) */
$phones = $laptops = $monitors = $audios = $accessories = [];
foreach ($products as $p) {
  if (!is_array($p)) continue;
  $cat = $p['category'] ?? '';
  if ($cat === 'Điện thoại') $phones[] = $p;
  elseif ($cat === 'Laptop') $laptops[] = $p;
  elseif ($cat === 'PC - Màn hình') $monitors[] = $p;
  elseif ($cat === 'Âm thanh') $audios[] = $p;
  elseif ($cat === 'Phụ kiện') $accessories[] = $p;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TechStore • Cửa hàng công nghệ</title>
  <meta name="description" content="TechStore - Cửa hàng công nghệ dành cho mọi nhà">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="style.css">
</head>

<body class="d-flex flex-column min-vh-100 ts-home">

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

            <input class="form-control" type="search" name="q"
                   value="<?php echo h($keyword); ?>"
                   placeholder="Nhập tên điện thoại, laptop, phụ kiện... cần tìm">

            <?php if ($categoryFilter !== ''): ?>
              <input type="hidden" name="category" value="<?php echo h($categoryFilter); ?>">
            <?php endif; ?>
            <?php if ($brandFilter !== ''): ?>
              <input type="hidden" name="brand" value="<?php echo h($brandFilter); ?>">
            <?php endif; ?>
            <?php if ($sort !== ''): ?>
              <input type="hidden" name="sort" value="<?php echo h($sort); ?>">
            <?php endif; ?>

            <button class="btn btn-primary" type="submit">Tìm</button>
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

    <!-- BANNER -->
    <section class="ts-section pt-3 ts-heroFull">
      <div class="hero hero--banner">
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="img/banner/banner-ok-1.png" class="d-block w-100" alt="Banner 1">
            </div>
            <div class="carousel-item">
              <img src="img/banner/banner-ok-2.png" class="d-block w-100" alt="Banner 2">
            </div>
            <div class="carousel-item">
              <img src="img/banner/banner-ok-3.png" class="d-block w-100" alt="Banner 3">
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Trước</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Sau</span>
          </button>
        </div>
      </div>
    </section>

    <?php if (!$isFiltering) renderMobileTopPromos(); ?>

    <!-- NAV DANH MỤC -->
    <section class="ts-section pt-2">
      <div class="container">
        <div class="ts-catnav">
          <button class="btn btn-light rounded-pill px-3 d-lg-none"
                  type="button" data-bs-toggle="offcanvas"
                  data-bs-target="#offcat" aria-controls="offcat">
            <i class="bi bi-list"></i> Danh mục
          </button>

          <ul class="nav gap-2 flex-wrap d-none d-lg-flex">
            <li class="nav-item">
              <a class="nav-link ts-pill <?php echo ($categoryFilter === '' ? 'active' : ''); ?>"
                 href="<?php echo h(buildLink(['category'=>null,'brand'=>null,'q'=>null,'sort'=>null,'page'=>null])); ?>">
                Tất cả sản phẩm
              </a>
            </li>
            <?php foreach ($fixedCategories as $cat): ?>
              <li class="nav-item">
                <a class="nav-link ts-pill <?php echo ($cat === $categoryFilter ? 'active' : ''); ?>"
                   href="<?php echo h(buildLink(['category'=>$cat,'brand'=>null,'sort'=>null,'page'=>null])); ?>">
                  <?php echo h($cat); ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </section>

    <!-- STRIP THƯƠNG HIỆU (khi đã chọn category) -->
    <?php if ($categoryFilter !== '' && !empty($brands)): ?>
      <section class="ts-section pt-1">
        <div class="container">
          <div class="ts-brandstrip">
            <div class="ts-brandstrip__label">Thương hiệu:</div>
            <div class="ts-brandstrip__list">
              <a class="ts-brandbtn <?php echo ($brandFilter === '' ? 'active' : ''); ?>"
                 href="<?php echo h(buildLink(['brand'=>null,'sort'=>null,'page'=>null])); ?>">
                Tất cả
              </a>

              <?php foreach ($brands as $b): ?>
                <?php
                  $logoPathOrig = $brandLogos[$b] ?? '';
                  $logoStrip = $logoPathOrig ? logoVariant($logoPathOrig, 'strip') : '';
                ?>
                <a class="ts-brandbtn <?php echo ($b === $brandFilter ? 'active' : ''); ?>"
                   href="<?php echo h(buildLink(['brand'=>$b,'sort'=>null,'page'=>null])); ?>"
                   title="<?php echo h($b); ?>">
                  <?php if ($logoStrip !== ''): ?>
                    <img src="<?php echo h($logoStrip); ?>" alt="<?php echo h($b); ?>" onerror="this.style.display='none'">
                  <?php else: ?>
                    <?php echo h($b); ?>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($isFiltering): ?>
      <!-- TRANG LỌC / TÌM KIẾM -->
      <section class="ts-section">
        <div class="container">
          <div class="ts-filterLayout">

            <aside class="ts-filter">
              <div class="ts-filter__head">
                <i class="bi bi-funnel"></i><span>Bộ lọc tìm kiếm</span>
              </div>

              <?php if (!empty($brands)): ?>
                <div class="ts-filter__group">
                  <div class="ts-filter__title">Hãng sản xuất</div>
                  <div class="ts-filter__brands">
                    <a class="ts-brandMini <?php echo ($brandFilter === '' ? 'active' : ''); ?>"
                       href="<?php echo h(buildLink(['brand'=>null,'sort'=>null,'page'=>null])); ?>">Tất cả</a>

                    <?php foreach ($brands as $b): ?>
                      <?php
                        $logoPathOrig = $brandLogos[$b] ?? '';
                        $logoMini = $logoPathOrig ? logoVariant($logoPathOrig, 'mini') : '';
                      ?>
                      <a class="ts-brandMini <?php echo ($b === $brandFilter ? 'active' : ''); ?>"
                         href="<?php echo h(buildLink(['brand'=>$b,'sort'=>null,'page'=>null])); ?>"
                         title="<?php echo h($b); ?>">
                        <?php if ($logoMini !== ''): ?>
                          <img src="<?php echo h($logoMini); ?>" alt="<?php echo h($b); ?>" onerror="this.style.display='none'">
                        <?php else: ?>
                          <?php echo h($b); ?>
                        <?php endif; ?>
                      </a>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <div class="ts-filter__hint">
                <a href="index.php" class="text-decoration-none">Xóa lọc</a>
              </div>
            </aside>

            <section class="ts-results">
              <div class="ts-results__top">
                <div>
                  <div class="ts-results__count">Tìm thấy <b><?php echo (int)$totalResults; ?></b> kết quả</div>
                  <div class="ts-results__chips">
                    <?php if ($keyword): ?>
                      <span class="chip">Từ khóa: <b><?php echo h($keyword); ?></b></span>
                    <?php endif; ?>
                    <?php if ($categoryFilter): ?>
                      <span class="chip">Danh mục: <b><?php echo h($categoryFilter); ?></b></span>
                    <?php endif; ?>
                    <?php if ($brandFilter): ?>
                      <span class="chip">Hãng: <b><?php echo h($brandFilter); ?></b></span>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- ✅ CHỈ CÒN: GIÁ TĂNG / GIÁ GIẢM -->
                <div class="sort">
                  <span class="label">Sắp xếp:</span>
                  <div class="sort-list">
                    <a class="chip <?php echo ($sort==='price_asc'?'active':''); ?>"
                       href="<?php echo h(buildLink(['sort'=>'price_asc'])); ?>">
                      Giá tăng dần
                    </a>
                    <a class="chip <?php echo ($sort==='price_desc'?'active':''); ?>"
                       href="<?php echo h(buildLink(['sort'=>'price_desc'])); ?>">
                      Giá giảm dần
                    </a>
                    <?php if ($sort !== ''): ?>
                      <a class="chip" href="<?php echo h(buildLink(['sort'=>null])); ?>">Bỏ sắp xếp</a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <?php if (empty($pageResults)): ?>
                <div class="alert alert-warning">Không tìm thấy sản phẩm phù hợp.</div>
              <?php else: ?>
                <div class="row g-3">
                  <?php foreach ($pageResults as $p): ?>
                    <?php if (!is_array($p)) continue; ?>
                    <div class="col-6 col-md-4 col-lg-3">
                      <?php renderProductCard($p); ?>
                    </div>
                  <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                  <?php
                    $baseParams = [];
                    if ($keyword        !== '') $baseParams['q']        = $keyword;
                    if ($categoryFilter !== '') $baseParams['category'] = $categoryFilter;
                    if ($brandFilter    !== '') $baseParams['brand']    = $brandFilter;
                    if ($sort           !== '') $baseParams['sort']     = $sort;
                  ?>
                  <nav class="mt-3">
                    <ul class="pagination pagination-sm justify-content-center">
                      <li class="page-item <?php echo ($page <= 1 ? 'disabled' : ''); ?>">
                        <?php $prev = $baseParams; $prev['page'] = max(1, $page - 1); ?>
                        <a class="page-link" href="index.php?<?php echo h(http_build_query($prev)); ?>">&laquo;</a>
                      </li>

                      <?php for ($i=1; $i<=$totalPages; $i++): ?>
                        <?php $pp = $baseParams; $pp['page'] = $i; ?>
                        <li class="page-item <?php echo ($i === $page ? 'active' : ''); ?>">
                          <a class="page-link" href="index.php?<?php echo h(http_build_query($pp)); ?>"><?php echo $i; ?></a>
                        </li>
                      <?php endfor; ?>

                      <li class="page-item <?php echo ($page >= $totalPages ? 'disabled' : ''); ?>">
                        <?php $next = $baseParams; $next['page'] = min($totalPages, $page + 1); ?>
                        <a class="page-link" href="index.php?<?php echo h(http_build_query($next)); ?>">&raquo;</a>
                      </li>
                    </ul>
                  </nav>
                <?php endif; ?>
              <?php endif; ?>
            </section>

          </div>
        </div>
      </section>

    <?php else: ?>
      <!-- HOME -->
      <?php
        $phonesHome      = slice8($phones);
        $laptopsHome     = slice8($laptops);
        $monitorsHome    = slice8($monitors);
        $audiosHome      = slice8($audios);
        $accessoriesHome = slice8($accessories);
      ?>

      <?php if (!empty($phonesHome)): ?>
        <?php renderCategoryBannerBlock('Điện thoại','Sản phẩm tiêu biểu trong danh mục',buildLink(['category'=>'Điện thoại']),$categorySideBanners['Điện thoại'],$phonesHome); ?>
      <?php endif; ?>

      <?php if (isset($wideBanners['between_phone_laptop'])): $b = $wideBanners['between_phone_laptop']; renderWideBanner($b['img'],$b['k'],$b['t'],$b['d'],$b['link']); endif; ?>

      <?php if (!empty($laptopsHome)): ?>
        <?php renderCategoryBannerBlock('Laptop','Sản phẩm tiêu biểu trong danh mục',buildLink(['category'=>'Laptop']),$categorySideBanners['Laptop'],$laptopsHome); ?>
      <?php endif; ?>

      <?php if (isset($wideBanners['between_laptop_pc'])): $b = $wideBanners['between_laptop_pc']; renderWideBanner($b['img'],$b['k'],$b['t'],$b['d'],$b['link']); endif; ?>

      <?php if (!empty($monitorsHome)): ?>
        <?php renderCategoryBannerBlock('PC - Màn hình','Sản phẩm tiêu biểu trong danh mục',buildLink(['category'=>'PC - Màn hình']),$categorySideBanners['PC - Màn hình'],$monitorsHome); ?>
      <?php endif; ?>

      <?php if (isset($wideBanners['between_pc_audio'])): $b = $wideBanners['between_pc_audio']; renderWideBanner($b['img'],$b['k'],$b['t'],$b['d'],$b['link']); endif; ?>

      <?php if (!empty($audiosHome)): ?>
        <?php renderCategoryBannerBlock('Âm thanh','Sản phẩm tiêu biểu trong danh mục',buildLink(['category'=>'Âm thanh']),$categorySideBanners['Âm thanh'],$audiosHome); ?>
      <?php endif; ?>

      <?php if (isset($wideBanners['between_audio_accessory'])): $b = $wideBanners['between_audio_accessory']; renderWideBanner($b['img'],$b['k'],$b['t'],$b['d'],$b['link']); endif; ?>

      <?php if (!empty($accessoriesHome)): ?>
        <?php renderCategoryBannerBlock('Phụ kiện','Sản phẩm tiêu biểu trong danh mục',buildLink(['category'=>'Phụ kiện']),$categorySideBanners['Phụ kiện'],$accessoriesHome); ?>
      <?php endif; ?>

    <?php endif; ?>

  </main>

  <!-- FOOTER -->
  <footer class="footer border-top py-4">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="title h5 mb-2">TechStore</div>
          <p class="mb-0">
            Địa chỉ: 126 Nguyễn Thiện Thành, Phường Hòa Thuận, Tỉnh Vĩnh Long<br>
            <a href="https://maps.app.goo.gl/pQUF56uYuuvMLzoH7" target="_blank" rel="noopener noreferrer" class="text-decoration-underline">
              Xem bản đồ trên Google Maps
            </a>
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
            Email: <a href="#" class="text-break">phanhoangdinh106@gmail.com</a>
          </p>
          <div class="d-flex flex-column gap-2">
            <a href="https://www.facebook.com/dinhphan2910" target="_blank" rel="noopener noreferrer"
               class="text-decoration-none d-flex align-items-center gap-2">
              <i class="bi bi-facebook"></i><span>Facebook</span>
            </a>
            <a href="tel:+0967492242" class="text-decoration-none d-flex align-items-center gap-2">
              <i class="bi bi-chat-dots"></i><span>Zalo: 0967 492 242</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <!-- OFFCANVAS DANH MỤC MOBILE -->
  <div class="offcanvas offcanvas-start" tabindex="-1" id="offcat" aria-labelledby="offcatLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="offcatLabel">Danh mục sản phẩm</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
      <div class="list-group list-group-flush">
        <a class="list-group-item list-group-item-action <?php echo ($categoryFilter === '' ? 'active' : ''); ?>"
           href="<?php echo h(buildLink(['category'=>null,'brand'=>null,'sort'=>null])); ?>">
          Tất cả sản phẩm
        </a>

        <?php foreach ($fixedCategories as $cat): ?>
          <a class="list-group-item list-group-item-action <?php echo ($cat === $categoryFilter ? 'active' : ''); ?>"
             href="<?php echo h(buildLink(['category'=>$cat,'brand'=>null,'sort'=>null])); ?>">
            <?php echo h($cat); ?>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($categoryFilter !== '' && !empty($brands)): ?>
        <hr class="my-0">
        <div class="px-3 pt-3 pb-2 small fw-semibold text-muted">Thương hiệu</div>
        <div class="list-group list-group-flush">
          <a class="list-group-item list-group-item-action <?php echo ($brandFilter === '' ? 'active' : ''); ?>"
             href="<?php echo h(buildLink(['brand'=>null,'sort'=>null])); ?>">
            Tất cả thương hiệu
          </a>

          <?php foreach ($brands as $b): ?>
            <?php
              $logoPathOrig = $brandLogos[$b] ?? '';
              $logoMini = $logoPathOrig ? logoVariant($logoPathOrig, 'mini') : '';
            ?>
            <a class="list-group-item list-group-item-action <?php echo ($b === $brandFilter ? 'active' : ''); ?>"
               href="<?php echo h(buildLink(['brand'=>$b,'sort'=>null])); ?>">
              <?php if ($logoMini !== ''): ?>
                <img src="<?php echo h($logoMini); ?>" alt="<?php echo h($b); ?>" style="height:26px;width:auto;object-fit:contain;" onerror="this.style.display='none'">
              <?php else: ?>
                <?php echo h($b); ?>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- MOBILE TABBAR -->
  <nav class="mobile-tabbar d-md-none">
    <a class="tab-item active" href="index.php">
      <i class="bi bi-house-fill"></i><span>Trang chủ</span>
    </a>
    <a class="tab-item" href="cart.php">
      <i class="bi bi-bag-fill"></i><span>Giỏ hàng</span>
    </a>
    <a class="tab-item" href="orders_user.php">
      <i class="bi bi-receipt"></i><span>Đơn hàng</span>
    </a>
    <?php if ($u && ($u['role'] ?? '') === 'admin'): ?>
      <a class="tab-item" href="admin_orders.php">
        <i class="bi bi-speedometer2"></i><span>Quản trị</span>
      </a>
    <?php endif; ?>
    <?php if ($u): ?>
      <a class="tab-item" href="profile.php">
        <i class="bi bi-person"></i><span>Tài khoản</span>
      </a>
    <?php else: ?>
      <a class="tab-item" href="login.php">
        <i class="bi bi-box-arrow-in-right"></i><span>Đăng nhập</span>
      </a>
    <?php endif; ?>
  </nav>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
