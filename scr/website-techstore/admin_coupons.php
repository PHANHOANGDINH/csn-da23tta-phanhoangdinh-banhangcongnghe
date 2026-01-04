<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/coupons.php';

requireLogin();
requireAdmin();

$u = currentUser();
date_default_timezone_set('Asia/Ho_Chi_Minh');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ✅ tránh undefined variable trong header search */
$keyword        = $_GET['q'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$brandFilter    = $_GET['brand'] ?? '';

/* ✅ đảm bảo coupons.php có đủ hàm */
foreach (['loadCoupons','saveCoupons','normCode','findCouponIndex','validateCouponPayload'] as $fn) {
  if (!function_exists($fn)) {
    die("Thiếu hàm $fn trong coupons.php. Kiểm tra lại file coupons.php.");
  }
}

/* convert "YYYY-MM-DD HH:MM:SS" <-> "YYYY-MM-DDTHH:MM" */
function toDatetimeLocal($s){
  $s = trim((string)$s);
  if ($s === '') return '';
  if (strpos($s, ' ') !== false) $s = str_replace(' ', 'T', $s);
  if (strlen($s) >= 16) return substr($s, 0, 16);
  return $s;
}
function fromDatetimeLocal($s){
  $s = trim((string)$s);
  if ($s === '') return '';
  $s = str_replace('T', ' ', $s);
  if (strlen($s) === 16) $s .= ':00';
  return $s;
}

$coupons = loadCoupons();

$flashOk = '';
$flashErr = '';
$errors = [];
$editing = false;
$editCode = '';

function resetCouponForm(): array {
  return [
    'code' => '',
    'type' => 'fixed',
    'value' => 0,
    'minOrder' => 0,
    'maxDiscount' => 0,
    'startAt' => '',
    'endAt' => '',
    'usageLimit' => 0,
    'perUserLimit' => 0,
    'active' => true
  ];
}

$form = resetCouponForm();

/* =========================
   HANDLE POST
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'delete') {
    $code = normCode($_POST['code'] ?? '');
    $idx = findCouponIndex($coupons, $code);
    if ($idx >= 0) {
      array_splice($coupons, $idx, 1);
      saveCoupons($coupons);
      $flashOk = "Đã xoá mã $code.";
    } else {
      $flashErr = "Không tìm thấy mã để xoá.";
    }
  }

  elseif ($action === 'toggle') {
    $code = normCode($_POST['code'] ?? '');
    $idx  = findCouponIndex($coupons, $code);
    if ($idx >= 0) {
      if (isset($_POST['to'])) $coupons[$idx]['active'] = ($_POST['to'] == '1');
      else $coupons[$idx]['active'] = !empty($coupons[$idx]['active']) ? false : true;

      saveCoupons($coupons);
      $flashOk = "Đã cập nhật trạng thái $code.";
    } else {
      $flashErr = "Không tìm thấy mã để bật/tắt.";
    }
  }

  elseif ($action === 'save') {
    $mode = $_POST['mode'] ?? 'add'; // add|edit
    $editing = ($mode === 'edit');

    $incoming = [
      'code' => $_POST['code'] ?? '',
      'type' => $_POST['type'] ?? 'fixed',
      'value' => $_POST['value'] ?? 0,
      'minOrder' => $_POST['minOrder'] ?? 0,
      'maxDiscount' => $_POST['maxDiscount'] ?? 0,
      'startAt' => fromDatetimeLocal($_POST['startAt'] ?? ''),
      'endAt'   => fromDatetimeLocal($_POST['endAt'] ?? ''),
      'usageLimit' => $_POST['usageLimit'] ?? 0,
      'perUserLimit' => $_POST['perUserLimit'] ?? 0,
      'active' => isset($_POST['active']) ? 1 : 0
    ];

    $code = normCode($incoming['code']);
    $editCode = normCode($_POST['editCode'] ?? $code);

    $old = null;
    $idxOld = -1;

    if ($editing) {
      $idxOld = findCouponIndex($coupons, $editCode);
      if ($idxOld >= 0) $old = $coupons[$idxOld];
      else $errors[] = "Không tìm thấy mã đang sửa ($editCode).";
    }

    if (empty($errors)) {
      [$ok, $errs, $clean] = validateCouponPayload($incoming, $editing, $old);
      $errors = array_merge($errors, $errs);

      if ($ok) {
        $idxNew = findCouponIndex($coupons, $clean['code']);
        if (!$editing) {
          if ($idxNew >= 0) $errors[] = "Mã {$clean['code']} đã tồn tại.";
        } else {
          if ($clean['code'] !== $editCode && $idxNew >= 0) $errors[] = "Mã {$clean['code']} đã tồn tại.";
        }
      }

      if (empty($errors)) {
        if (!$editing) {
          $clean['usedCount'] = 0;
          $coupons[] = $clean;
          $flashOk = "Đã tạo mã {$clean['code']}.";
        } else {
          $coupons[$idxOld] = $clean;
          $flashOk = "Đã cập nhật mã {$clean['code']}.";
        }

        saveCoupons($coupons);

        $editing = false;
        $editCode = '';
        $form = resetCouponForm();
      } else {
        $form = [
          'code' => (string)$incoming['code'],
          'type' => (string)$incoming['type'],
          'value' => (int)$incoming['value'],
          'minOrder' => (int)$incoming['minOrder'],
          'maxDiscount' => (int)$incoming['maxDiscount'],
          'startAt' => (string)$incoming['startAt'],
          'endAt' => (string)$incoming['endAt'],
          'usageLimit' => (int)$incoming['usageLimit'],
          'perUserLimit' => (int)$incoming['perUserLimit'],
          'active' => !empty($incoming['active'])
        ];
      }
    }
  }

  $coupons = loadCoupons();
}

/* =========================
   LOAD EDIT
   ========================= */
if (!empty($_GET['edit'])) {
  $editing = true;
  $editCode = normCode($_GET['edit']);
  $idx = findCouponIndex($coupons, $editCode);

  if ($idx >= 0) {
    $c = $coupons[$idx];
    $form = [
      'code' => $c['code'] ?? '',
      'type' => $c['type'] ?? 'fixed',
      'value' => (int)($c['value'] ?? 0),
      'minOrder' => (int)($c['minOrder'] ?? 0),
      'maxDiscount' => (int)($c['maxDiscount'] ?? 0),
      'startAt' => (string)($c['startAt'] ?? ''),
      'endAt' => (string)($c['endAt'] ?? ''),
      'usageLimit' => (int)($c['usageLimit'] ?? 0),
      'perUserLimit' => (int)($c['perUserLimit'] ?? 0),
      'active' => !empty($c['active'])
    ];
  } else {
    $flashErr = "Không tìm thấy mã để sửa.";
    $editing = false;
    $editCode = '';
    $form = resetCouponForm();
  }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý mã giảm giá - TechStore (Admin)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <!-- CSS giao diện -->
  <link rel="stylesheet" href="style.css?v=1">
</head>

<!-- ✅ bỏ bg-light để không ép nền “trắng tươi” -->
<body class="d-flex flex-column min-vh-100">
  <!-- TOPBAR -->
  <div class="topbar small">
    <div class="container d-flex justify-content-between">
      <div>Quản lý mã giảm giá • Admin TechStore</div>
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
                <span><?= h($u['username']); ?></span>
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
          <li class="breadcrumb-item"><a href="admin_orders.php">Admin</a></li>
          <li class="breadcrumb-item active" aria-current="page">Mã giảm giá</li>
        </ol>
      </nav>

      <?php if ($flashOk): ?>
        <div class="alert alert-success"><?= h($flashOk) ?></div>
      <?php endif; ?>
      <?php if ($flashErr): ?>
        <div class="alert alert-danger"><?= h($flashErr) ?></div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <div class="fw-bold mb-1">Có lỗi:</div>
          <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
              <li><?= h($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="row g-3">
        <!-- LEFT: LIST -->
        <div class="col-lg-8">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <h2 class="h6 mb-0">Danh sách mã</h2>
                <a class="btn btn-sm btn-primary" href="admin_coupons.php">Thêm mã mới</a>
              </div>

              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Code</th>
                      <th>Loại</th>
                      <th class="text-end">Giá trị</th>
                      <th class="text-end">Đơn tối thiểu</th>
                      <th>Thời gian</th>
                      <th class="text-end">Đã dùng</th>
                      <th>Trạng thái</th>
                      <th class="text-end">Thao tác</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php if (empty($coupons)): ?>
                    <tr>
                      <td colspan="8" class="text-center text-muted py-4">Chưa có mã giảm giá nào.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($coupons as $c): ?>
                      <?php
                        $code   = normCode($c['code'] ?? '');
                        $type   = $c['type'] ?? 'fixed';
                        $val    = (int)($c['value'] ?? 0);
                        $minO   = (int)($c['minOrder'] ?? 0);
                        $used   = (int)($c['usedCount'] ?? 0);
                        $limit  = (int)($c['usageLimit'] ?? 0);
                        $active = !empty($c['active']);

                        $start = trim((string)($c['startAt'] ?? ''));
                        $end   = trim((string)($c['endAt'] ?? ''));
                        $range = '';
                        if ($start !== '' || $end !== '') $range = ($start ?: '...') . ' → ' . ($end ?: '...');
                      ?>
                      <tr>
                        <td class="fw-semibold"><?= h($code) ?></td>
                        <td><?= $type === 'percent' ? 'Phần trăm' : 'Giảm tiền' ?></td>
                        <td class="text-end">
                          <?= $type === 'percent'
                            ? h($val . '%')
                            : h(number_format($val,0,',','.') . '₫') ?>
                        </td>
                        <td class="text-end"><?= h(number_format($minO,0,',','.') . '₫') ?></td>
                        <td class="small text-muted"><?= h($range) ?></td>
                        <td class="text-end"><?= h($used . ($limit>0 ? "/$limit" : "")) ?></td>
                        <td>
                          <?php if ($active): ?>
                            <span class="badge text-bg-success">Đang bật</span>
                          <?php else: ?>
                            <span class="badge text-bg-secondary">Đang tắt</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end">
                          <a class="btn btn-sm btn-outline-primary" href="admin_coupons.php?edit=<?= urlencode($code) ?>">Sửa</a>

                          <form class="d-inline" method="post" action="admin_coupons.php"
                                onsubmit="return confirm('Xoá mã <?= h($code) ?> ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="code" value="<?= h($code) ?>">
                            <button class="btn btn-sm btn-outline-danger">Xoá</button>
                          </form>

                          <form class="d-inline" method="post" action="admin_coupons.php">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="code" value="<?= h($code) ?>">
                            <input type="hidden" name="to" value="<?= $active ? '0' : '1' ?>">
                            <button class="btn btn-sm btn-outline-dark">
                              <?= $active ? 'Tắt' : 'Bật' ?>
                            </button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>

            </div>
          </div>
        </div>

        <!-- RIGHT: FORM -->
        <div class="col-lg-4">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <h2 class="h6 mb-3"><?= $editing ? 'Sửa mã giảm giá' : 'Thêm mã giảm giá' ?></h2>

              <form method="post" action="admin_coupons.php" novalidate>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="mode" value="<?= $editing ? 'edit' : 'add' ?>">
                <input type="hidden" name="editCode" value="<?= h($editCode) ?>">

                <div class="mb-2">
                  <label class="form-label">Code</label>
                  <input class="form-control" name="code" value="<?= h($form['code']) ?>" placeholder="VD: SALE10" required>
                  <div class="form-text">Không phân biệt hoa thường, sẽ tự viết hoa.</div>
                </div>

                <div class="mb-2">
                  <label class="form-label">Loại giảm</label>
                  <select class="form-select" name="type" id="couponType">
                    <option value="fixed" <?= $form['type']==='fixed'?'selected':'' ?>>Giảm tiền (fixed)</option>
                    <option value="percent" <?= $form['type']==='percent'?'selected':'' ?>>Giảm % (percent)</option>
                  </select>
                </div>

                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label">Giá trị</label>
                    <input class="form-control" type="number" name="value" value="<?= (int)$form['value'] ?>" min="1" required>
                  </div>
                  <div class="col-6">
                    <label class="form-label">Đơn tối thiểu</label>
                    <input class="form-control" type="number" name="minOrder" value="<?= (int)$form['minOrder'] ?>" min="0">
                  </div>
                </div>

                <div class="mt-2">
                  <label class="form-label">Giảm tối đa (chỉ cho percent)</label>
                  <input class="form-control" type="number" name="maxDiscount" id="maxDiscount"
                         value="<?= (int)$form['maxDiscount'] ?>" min="0">
                </div>

                <!-- ✅ datetime picker -->
                <div class="mt-2">
                  <label class="form-label">Bắt đầu</label>
                  <input class="form-control" type="datetime-local" name="startAt"
                         value="<?= h(toDatetimeLocal($form['startAt'])) ?>">
                </div>

                <div class="mt-2">
                  <label class="form-label">Kết thúc</label>
                  <input class="form-control" type="datetime-local" name="endAt"
                         value="<?= h(toDatetimeLocal($form['endAt'])) ?>">
                </div>

                <div class="row g-2 mt-2">
                  <div class="col-6">
                    <label class="form-label">Giới hạn lượt</label>
                    <input class="form-control" type="number" name="usageLimit" value="<?= (int)$form['usageLimit'] ?>" min="0">
                    <div class="form-text">0 = không giới hạn</div>
                  </div>
                  <div class="col-6">
                    <label class="form-label">Mỗi user</label>
                    <input class="form-control" type="number" name="perUserLimit" value="<?= (int)$form['perUserLimit'] ?>" min="0">
                    <div class="form-text">0 = không giới hạn</div>
                  </div>
                </div>

                <div class="form-check mt-3">
                  <input class="form-check-input" type="checkbox" name="active" id="active" <?= !empty($form['active']) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="active">Bật mã</label>
                </div>

                <div class="d-grid mt-3">
                  <button class="btn btn-primary">
                    <?= $editing ? 'Lưu thay đổi' : 'Tạo mã' ?>
                  </button>
                </div>

                <?php if ($editing): ?>
                  <div class="d-grid mt-2">
                    <a class="btn btn-outline-secondary" href="admin_coupons.php">Huỷ sửa</a>
                  </div>
                <?php endif; ?>
              </form>

            </div>
          </div>
        </div>

      </div>
    </div>
  </main>

  <!-- ✅ GIỮ NGUYÊN FOOTER CỦA BẠN (KHÔNG SỬA) -->
  <footer class="footer border-top py-4 mt-3">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="title h5 mb-2">TechStore</div>
          <p class="mb-0">Quản lý mã giảm giá cho Admin TechStore.</p>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // UX: nếu chọn fixed thì maxDiscount disable
    (function(){
      const typeEl = document.getElementById('couponType');
      const maxEl  = document.getElementById('maxDiscount');
      function sync(){
        if (!typeEl || !maxEl) return;
        const isPercent = typeEl.value === 'percent';
        maxEl.disabled = !isPercent;
        if (!isPercent) maxEl.value = 0;
      }
      if (typeEl) typeEl.addEventListener('change', sync);
      sync();
    })();
  </script>
</body>
</html>
