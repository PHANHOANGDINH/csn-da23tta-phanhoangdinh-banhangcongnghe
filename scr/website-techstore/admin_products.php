<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/products.php';

requireLogin();
requireAdmin();

$u       = currentUser();
$message = '';

// timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

/* =========================
   HELPERS cho header dùng chung
========================= */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$keyword        = $_GET['q'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$brandFilter    = $_GET['brand'] ?? '';

/* =========================
   SORT: SẢN PHẨM MỚI NHẤT (ID LỚN NHẤT) LÊN TRÊN
========================= */
function sortProductsNewestFirst(array $products): array {
  uasort($products, function($a, $b) {
    $aid = (int)($a['id'] ?? 0);
    $bid = (int)($b['id'] ?? 0);

    // Ưu tiên ID giảm dần
    if ($aid !== 0 || $bid !== 0) {
      return $bid <=> $aid; // DESC
    }

    // Fallback theo created_at nếu có
    $at = strtotime($a['created_at'] ?? '1970-01-01 00:00:00');
    $bt = strtotime($b['created_at'] ?? '1970-01-01 00:00:00');
    return $bt <=> $at; // DESC
  });

  return $products;
}

/* =========================
   LOAD PRODUCTS (MỚI NHẤT TRƯỚC)
========================= */
$products = sortProductsNewestFirst(loadProducts());

/* =========================
   HANDLE POST ACTIONS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'create';

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
      $message = "ID sản phẩm không hợp lệ.";
    } else {
      if (deleteProduct($id)) {
        $message = "Đã xóa sản phẩm #{$id} thành công.";
      } else {
        $message = "Không thể xóa sản phẩm #{$id} (có thể không tồn tại).";
      }
    }

    // reload + sort
    $products = sortProductsNewestFirst(loadProducts());
  }

  elseif ($action === 'update_stock') {
    $id    = (int)($_POST['id'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    if ($stock < 0) $stock = 0;

    if ($id <= 0) {
      $message = "ID sản phẩm không hợp lệ.";
    } else {
      $all   = loadProducts();
      $found = false;

      foreach ($all as &$p) {
        if ((int)($p['id'] ?? 0) === $id) {
          $p['stock'] = $stock;
          $found = true;
          break;
        }
      }
      unset($p);

      if ($found) {
        saveProducts($all);
        $message  = "Đã cập nhật tồn kho cho sản phẩm #{$id}.";
        $products = sortProductsNewestFirst(loadProducts());
      } else {
        $message = "Không tìm thấy sản phẩm #{$id} để cập nhật tồn kho.";
      }
    }
  }

  elseif ($action === 'create') {
    $name     = trim($_POST['name'] ?? '');
    $price    = (int)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $specsRaw = trim($_POST['specs'] ?? '');
    $stock    = (int)($_POST['stock'] ?? 0);
    if ($stock < 0) $stock = 0;

    $brand = trim($_POST['brand'] ?? '');

    if ($name === '' || $price <= 0) {
      $message = 'Tên sản phẩm và giá phải hợp lệ.';
    } else {
      $imageName = '';

      // Upload ảnh (tùy chọn)
      if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
          $allowed = ['jpg','jpeg','png','gif','webp'];
          $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

          if (in_array($ext, $allowed, true)) {
            $safeName  = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $imageName = $safeName . '_' . time() . '.' . $ext;

            $destDir = __DIR__ . '/uploads';
            if (!is_dir($destDir)) mkdir($destDir, 0777, true);

            $destPath = $destDir . '/' . $imageName;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
              $imageName = '';
              $message   = 'Không thể lưu file ảnh, sản phẩm vẫn được tạo nhưng không có hình.';
            }
          } else {
            $message = 'Định dạng ảnh không hợp lệ. Chỉ hỗ trợ: jpg, jpeg, png, gif, webp.';
          }
        }
      }

      // createProduct: thêm brand & stock
      createProduct($name, $price, $category, $imageName, $specsRaw, $brand, $stock);

      if ($message === '') $message = 'Thêm sản phẩm mới thành công.';

      // reload + sort
      $products = sortProductsNewestFirst(loadProducts());
    }
  }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý sản phẩm - TechStore (Admin)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- CSS giao diện -->
  <link rel="stylesheet" href="style.css?v=1">
</head>

<body class="ts-admin d-flex flex-column min-vh-100">

  <!-- TOPBAR -->
  <div class="topbar small">
    <div class="container d-flex justify-content-between">
      <div>Quản lý sản phẩm • Admin TechStore</div>
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
      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active" aria-current="page">Admin • Quản lý sản phẩm</li>
        </ol>
      </nav>

      <div class="row g-3">
        <!-- Form thêm sản phẩm -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
              <h1 class="h5 mb-3">Thêm sản phẩm mới</h1>

              <?php if ($message): ?>
                <div class="alert alert-info py-2">
                  <?= h($message) ?>
                </div>
              <?php endif; ?>

              <form method="post" enctype="multipart/form-data" class="vstack gap-3">
                <input type="hidden" name="action" value="create">

                <div>
                  <label class="form-label">Tên sản phẩm</label>
                  <input type="text" name="name" class="form-control" required>
                </div>

                <div>
                  <label class="form-label">Giá (VNĐ)</label>
                  <input type="number" name="price" min="1" class="form-control" required>
                </div>

                <div>
                  <label class="form-label">Danh mục (Category)</label>
                  <select name="category" class="form-select" required>
                    <option value="">-- Chọn danh mục --</option>
                    <option value="Điện thoại">Điện thoại</option>
                    <option value="Laptop">Laptop</option>
                    <option value="PC - Màn hình">PC - Màn hình</option>
                    <option value="Âm thanh">Âm thanh</option>
                    <option value="Phụ kiện">Phụ kiện</option>
                  </select>
                </div>

                <!-- Thương hiệu -->
                <div>
                  <label class="form-label">Thương hiệu (brand)</label>
                  <select name="brand" class="form-select">
                    <option value="">-- Chọn thương hiệu (tùy chọn) --</option>

                    <optgroup label="Điện thoại">
                      <option value="Apple">Apple</option>
                      <option value="Samsung">Samsung</option>
                      <option value="OPPO">OPPO</option>
                      <option value="Xiaomi">Xiaomi</option>
                      <option value="realme">realme</option>
                      <option value="vivo">vivo</option>
                      <option value="Nokia">Nokia</option>
                      <option value="Huawei">Huawei</option>
                    </optgroup>

                    <optgroup label="Laptop">
                      <option value="Asus">Asus</option>
                      <option value="Acer">Acer</option>
                      <option value="Dell">Dell</option>
                      <option value="HP">HP</option>
                      <option value="Lenovo">Lenovo</option>
                      <option value="MSI">MSI</option>
                    </optgroup>

                    <optgroup label="PC - Màn hình">
                      <option value="LG">LG</option>
                      <option value="ViewSonic">ViewSonic</option>
                      <option value="Gigabyte">Gigabyte</option>
                      <option value="AOC">AOC</option>
                    </optgroup>

                    <optgroup label="Âm thanh">
                      <option value="Sony">Sony</option>
                      <option value="JBL">JBL</option>
                      <option value="Anker">Anker</option>
                      <option value="Sennheiser">Sennheiser</option>
                      <option value="Bose">Bose</option>
                      <option value="Marshall">Marshall</option>
                    </optgroup>

                    <optgroup label="Phụ kiện di động">
                      <option value="Baseus">Baseus</option>
                      <option value="Ugreen">Ugreen</option>
                      <option value="Remax">Remax</option>
                      <option value="Energizer">Energizer</option>
                    </optgroup>

                    <optgroup label="Thiết bị lưu trữ">
                      <option value="WD">WD</option>
                      <option value="Seagate">Seagate</option>
                      <option value="Kingston">Kingston</option>
                      <option value="SanDisk">SanDisk</option>
                      <option value="Transcend">Transcend</option>
                      <option value="Lexar">Lexar</option>
                    </optgroup>

                    <optgroup label="Đồng hồ / Smartwatch">
                      <option value="Garmin">Garmin</option>
                      <option value="Amazfit">Amazfit</option>
                    </optgroup>
                  </select>
                </div>

                <!-- Thông số kỹ thuật -->
                <div>
                  <label class="form-label">Thông số kỹ thuật</label>
                  <textarea
                    name="specs"
                    rows="4"
                    class="form-control"
                    placeholder="Mỗi dòng 1 thông số.&#10;Ví dụ:&#10;Màn hình: 6.1&quot; OLED&#10;Chip: Apple A15 Bionic&#10;RAM: 6 GB&#10;Bộ nhớ: 128 GB"></textarea>
                </div>

                <div>
                  <label class="form-label">Tồn kho ban đầu</label>
                  <input type="number" name="stock" min="0" value="0" class="form-control">
                </div>

                <div>
                  <label class="form-label">Ảnh sản phẩm (tùy chọn)</label>
                  <input type="file" name="image" accept="image/*" class="form-control">
                  <div class="form-text">
                    Kích thước vừa phải, định dạng: jpg, jpeg, png, gif, webp.
                  </div>
                </div>

                <button class="btn btn-primary" type="submit">
                  <i class="bi bi-plus-circle"></i> Thêm sản phẩm
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h2 class="h6 mb-0">Danh sách sản phẩm (mới nhất lên trên)</h2>
                <span class="badge bg-secondary-subtle text-dark">
                  Tổng: <?php echo count($products); ?> sản phẩm
                </span>
              </div>

              <?php if (empty($products)): ?>
                <p class="text-muted mb-0">Chưa có sản phẩm nào.</p>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table align-middle">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 80px;">Ảnh</th>
                        <th>Tên</th>
                        <th style="width: 140px;">Danh mục</th>
                        <th style="width: 140px;">Thương hiệu</th>
                        <th class="text-end" style="width: 120px;">Giá</th>
                        <th class="text-center" style="width: 150px;">Tồn kho</th>
                        <th class="text-center" style="width: 140px;">Hành động</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                      <?php
                        $pid   = (int)($p['id'] ?? 0);
                        $img   = (string)($p['image'] ?? '');
                        $name  = (string)($p['name'] ?? '');
                        $cat   = (string)($p['category'] ?? '');
                        $brand = (string)($p['brand'] ?? '');
                        $price = (int)($p['price'] ?? 0);
                        $stock = (int)($p['stock'] ?? 0);
                      ?>
                      <tr>
                        <td><?php echo $pid; ?></td>
                        <td>
                          <?php if ($img !== ''): ?>
                            <img
                              src="uploads/<?php echo h($img); ?>"
                              alt=""
                              style="max-width:60px; max-height:60px; object-fit:cover; border-radius:6px;">
                          <?php else: ?>
                            <span class="text-muted small">Không ảnh</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="fw-semibold small"><?php echo h($name); ?></div>
                        </td>
                        <td class="small"><?php echo h($cat); ?></td>
                        <td class="small"><?php echo h($brand); ?></td>
                        <td class="text-end"><?php echo number_format($price, 0, ',', '.'); ?>₫</td>

                        <td class="text-center">
                          <form method="post" class="d-flex align-items-center justify-content-center gap-1">
                            <input type="hidden" name="action" value="update_stock">
                            <input type="hidden" name="id" value="<?php echo $pid; ?>">
                            <input
                              type="number"
                              name="stock"
                              min="0"
                              value="<?php echo $stock; ?>"
                              class="form-control form-control-sm text-center"
                              style="max-width:80px;">
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Cập nhật tồn kho">
                              <i class="bi bi-save"></i>
                            </button>
                          </form>
                        </td>

                        <td class="text-center">
                          <form method="post"
                                class="d-inline"
                                onsubmit="return confirm('Xóa sản phẩm #<?php echo $pid; ?> ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $pid; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                              <i class="bi bi-trash"></i> Xóa
                            </button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>

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
            Bảng điều khiển Admin TechStore.<br>
            Quản lý sản phẩm hiển thị trên trang khách.
          </p>
        </div>

        <div>
          <div class="title fw-bold mb-2">Gợi ý</div>
          <p class="mb-1 small mb-0">
            Điền đầy đủ <b>tên, giá, danh mục, thương hiệu, thông số, ảnh và tồn kho</b>
            để trang chủ & trang chi tiết hiển thị đẹp, dễ lọc.
          </p>
        </div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
