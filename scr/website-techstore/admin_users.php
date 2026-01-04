<?php
require_once __DIR__ . '/auth.php';

requireLogin();
requireAdmin();

date_default_timezone_set('Asia/Ho_Chi_Minh');

/* =========================
   HELPERS
========================= */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function usersJsonPath(): string {
  if (defined('USERS_FILE') && is_string(USERS_FILE) && USERS_FILE !== '') return USERS_FILE;
  if (defined('USERS_JSON') && is_string(USERS_JSON) && USERS_JSON !== '') return USERS_JSON;

  $candidates = [
    __DIR__ . '/users.json',
    __DIR__ . '/data/users.json',
    __DIR__ . '/db/users.json',
    __DIR__ . '/storage/users.json',
  ];
  foreach ($candidates as $p) {
    if (file_exists($p)) return $p;
  }
  return __DIR__ . '/users.json';
}

function saveUsersFallback(array $users): bool {
  if (function_exists('saveUsersToJson')) {
    try { return (bool)saveUsersToJson($users); } catch (\Throwable $e) {}
  }
  if (function_exists('saveUsers')) {
    try { return (bool)saveUsers($users); } catch (\Throwable $e) {}
  }

  $path = usersJsonPath();
  $json = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  if ($json === false) return false;
  return file_put_contents($path, $json) !== false;
}

function updateUserPasswordByEmailSafe(string $email, string $newPassword): bool {
  if (function_exists('changeUserPasswordByEmail')) {
    try { return (bool)changeUserPasswordByEmail($email, $newPassword); } catch (\Throwable $e) {}
  }
  if (function_exists('updateUserPasswordByEmail')) {
    try { return (bool)updateUserPasswordByEmail($email, $newPassword); } catch (\Throwable $e) {}
  }

  if (!function_exists('loadUsersFromJson')) return false;

  $users = loadUsersFromJson();
  $found = false;

  foreach ($users as $k => $row) {
    $rowEmail = strtolower(trim((string)($row['email'] ?? '')));
    if ($rowEmail !== '' && $rowEmail === strtolower(trim($email))) {
      $hash = password_hash($newPassword, PASSWORD_DEFAULT);

      // Tương thích nhiều kiểu lưu
      $users[$k]['password_hash'] = $hash;
      $users[$k]['password']      = $hash;

      // nếu có created_at thì giữ nguyên, có updated_at thì set lại
      $users[$k]['updated_at'] = date('c');

      $found = true;
      break;
    }
  }

  if (!$found) return false;
  return saveUsersFallback($users);
}

/* =========================
   DATA
========================= */
$u   = currentUser();
$msg = '';

$keyword        = $_GET['q'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$brandFilter    = $_GET['brand'] ?? '';

/* =========================
   HANDLE POST
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add') {
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = $_POST['role'] ?? 'user';

    if ($email === '' || $username === '' || $password === '') {
      $msg = 'Email / Tên hiển thị / Mật khẩu không được rỗng.';
    } elseif (!isValidEmail($email)) {
      $msg = 'Email không hợp lệ.';
    } elseif (!in_array($role, ['user','admin'], true)) {
      $msg = 'Role không hợp lệ.';
    } elseif (emailExists($email)) {
      $msg = 'Email đã tồn tại.';
    } elseif (userExists($username)) {
      $msg = 'Tên hiển thị đã tồn tại.';
    } else {
      createUserWithEmail($email, $username, $password, $role);

      // Nếu auth.php không tự set created_at, ta cố gắng set theo fallback (tùy cấu trúc users.json)
      // (Không bắt buộc, vì phía dưới đã có sort theo index nếu thiếu created_at)
      $msg = 'Thêm người dùng thành công.';
    }
  }

  elseif ($action === 'changerole') {
    $email = trim($_POST['email'] ?? '');
    $role  = $_POST['role'] ?? 'user';

    if ($email === '' || !isValidEmail($email)) {
      $msg = 'Email không hợp lệ.';
    } elseif (!in_array($role, ['user','admin'], true)) {
      $msg = 'Role không hợp lệ.';
    } else {
      $msg = changeUserRoleByEmail($email, $role)
        ? 'Đổi quyền thành công.'
        : 'Không thể đổi quyền (có thể là admin).';
    }
  }

  elseif ($action === 'changepw') {
    $email   = trim($_POST['email'] ?? '');
    $newPass = trim($_POST['new_password'] ?? '');

    if ($email === '' || !isValidEmail($email)) {
      $msg = 'Email không hợp lệ.';
    } elseif ($newPass === '') {
      $msg = 'Mật khẩu mới không được rỗng.';
    } elseif (strlen($newPass) < 4) {
      $msg = 'Mật khẩu mới tối thiểu 4 ký tự.';
    } else {
      $ok  = updateUserPasswordByEmailSafe($email, $newPass);
      $msg = $ok ? 'Đổi mật khẩu thành công.' : 'Không thể đổi mật khẩu (không tìm thấy user hoặc lỗi lưu).';
    }
  }
}

/* =========================
   HANDLE DELETE (GET)
========================= */
if (isset($_GET['delete_email'])) {
  $delEmail  = trim((string)$_GET['delete_email']);
  $selfEmail = trim((string)($u['email'] ?? ''));

  if ($selfEmail !== '' && strtolower($delEmail) === strtolower($selfEmail)) {
    $msg = 'Không thể xóa chính mình.';
  } elseif (deleteUserByEmail($delEmail)) {
    $msg = 'Đã xóa người dùng.';
  } else {
    $msg = 'Không thể xóa (có thể là admin hoặc không tồn tại).';
  }

  header('Location: admin_users.php');
  exit;
}

/* =========================
   LOAD USERS + SORT
   ✅ TÀI KHOẢN TẠO MỚI LÊN TRÊN
   - Ưu tiên created_at DESC nếu có
   - Nếu không có created_at: dùng thứ tự trong file (dòng sau = mới hơn)
========================= */
$users = loadUsersFromJson();

// Gắn chỉ số theo thứ tự đọc từ file (index càng lớn => user càng mới)
$__i = 0;
foreach ($users as $k => $row) {
  if (!is_array($row)) $row = [];
  $row['__idx'] = $__i++;
  $users[$k] = $row;
}

uasort($users, function($a, $b) {
  $at = !empty($a['created_at']) ? strtotime($a['created_at']) : 0;
  $bt = !empty($b['created_at']) ? strtotime($b['created_at']) : 0;

  // Có created_at hợp lệ => sort theo created_at (mới nhất lên trên)
  if ($at && $bt) return $bt <=> $at;
  if ($at && !$bt) return -1;
  if (!$at && $bt) return 1;

  // Không có created_at => sort theo index trong file (mới nhất lên trên)
  $ai = (int)($a['__idx'] ?? 0);
  $bi = (int)($b['__idx'] ?? 0);
  return $bi <=> $ai;
});
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý người dùng - TechStore (Admin)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="style.css?v=1">

  <style>
    .ts-table-fixed { table-layout: fixed; }
    .ts-ellipsis { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ts-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
  </style>
</head>

<body class="ts-admin d-flex flex-column min-vh-100 bg-light">

  <div class="topbar small">
    <div class="container d-flex justify-content-between">
      <div>Bảng điều khiển Admin • TechStore</div>
      <div class="d-none d-md-block">
        Xin chào, <?= h($u['username'] ?? '') ?>
      </div>
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
            <input class="form-control" type="search" name="q" value="<?= h($keyword) ?>"
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

          <div class="dropdown">
            <button class="btn-account-main dropdown-toggle" type="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle"></i>
              <span><?= h($u['username'] ?? '') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="profile.php">Hồ sơ của tôi</a></li>
              <li><a class="dropdown-item" href="orders_user.php">Đơn hàng của tôi</a></li>

              <li><hr class="dropdown-divider"></li>
              <li class="dropdown-header small text-muted">Khu vực quản trị</li>
              <li><a class="dropdown-item" href="admin_orders.php">QL đơn hàng</a></li>
              <li><a class="dropdown-item" href="admin_products.php">QL sản phẩm</a></li>
              <li><a class="dropdown-item" href="admin_users.php">QL người dùng</a></li>
              <li><a class="dropdown-item" href="admin_coupons.php">QL mã giảm giá</a></li>
              <li><a class="dropdown-item" href="admin_revenue.php">Báo cáo doanh thu</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </header>

  <main class="flex-grow-1">
    <div class="container py-3">

      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active" aria-current="page">Admin • Quản lý người dùng</li>
        </ol>
      </nav>

      <div class="row g-3">

        <!-- FORM THÊM USER -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
              <h1 class="h5 mb-3">Thêm người dùng mới</h1>

              <?php if ($msg): ?>
                <div class="alert alert-info py-2 mb-3"><?= h($msg) ?></div>
              <?php endif; ?>

              <form method="post" class="vstack gap-3">
                <input type="hidden" name="action" value="add">

                <div>
                  <label class="form-label">Email (Tài khoản đăng nhập)</label>
                  <input type="email" name="email" class="form-control">
                </div>

                <div>
                  <label class="form-label">Tên hiển thị</label>
                  <input type="text" name="username" class="form-control">
                </div>

                <div>
                  <label class="form-label">Password</label>
                  <input type="text" name="password" class="form-control" required>
                </div>

                <div>
                  <label class="form-label">Role</label>
                  <select name="role" class="form-select">
                    <option value="user">user</option>
                    <option value="admin">admin</option>
                  </select>
                </div>

                <button class="btn btn-primary" type="submit">
                  <i class="bi bi-person-plus"></i> Thêm
                </button>

                <div class="form-text">
                  Lưu ý: Mật khẩu lưu trong hệ thống nên là <b>hash</b> (mã hóa 1 chiều), không lưu plaintext.
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- LIST USER -->
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h2 class="h6 mb-0">Danh sách người dùng (tài khoản tạo mới lên trên)</h2>
                <span class="badge bg-secondary-subtle text-dark">Tổng: <?= count($users); ?> tài khoản</span>
              </div>

              <div class="table-responsive">
                <table class="table align-middle ts-table-fixed">
                  <colgroup>
                    <col style="width: 26%;">
                    <col style="width: 16%;">
                    <col style="width: 10%;">
                    <col style="width: 28%;">
                    <col style="width: 20%;">
                  </colgroup>

                  <thead class="table-light">
                    <tr>
                      <th>Email</th>
                      <th>Tên hiển thị</th>
                      <th>Role</th>
                      <th>Mật khẩu (hash)</th>
                      <th class="text-center">Hành động</th>
                    </tr>
                  </thead>

                  <tbody>
                    <?php foreach ($users as $row): ?>
                      <?php
                        $email = (string)($row['email'] ?? '');
                        $uname = (string)($row['username'] ?? '');
                        $role  = (string)($row['role'] ?? 'user');

                        $pwHash = (string)($row['password_hash'] ?? ($row['password'] ?? ''));
                        $pwHash = trim($pwHash);

                        $pwHashShort = $pwHash;
                        if (strlen($pwHash) > 40) {
                          $pwHashShort = substr($pwHash, 0, 26) . '…' . substr($pwHash, -10);
                        }

                        $isAdmin   = ($role === 'admin');
                        $selfEmail = strtolower(trim((string)($u['email'] ?? '')));
                        $isSelf    = ($selfEmail !== '' && strtolower(trim($email)) === $selfEmail);

                        $modalId = 'um_' . substr(md5($email . '|' . $uname), 0, 10);
                      ?>
                      <tr>
                        <td><div class="ts-ellipsis" title="<?= h($email) ?>"><?= h($email) ?></div></td>
                        <td><div class="ts-ellipsis" title="<?= h($uname) ?>"><?= h($uname) ?></div></td>

                        <td>
                          <?php if ($isAdmin): ?>
                            <span class="badge bg-danger-subtle text-danger">admin</span>
                          <?php else: ?>
                            <span class="badge bg-primary-subtle text-primary">user</span>
                          <?php endif; ?>
                        </td>

                        <td class="small">
                          <?php if ($pwHash !== ''): ?>
                            <code class="d-block ts-ellipsis ts-mono" title="<?= h($pwHash) ?>">
                              <?= h($pwHashShort) ?>
                            </code>
                          <?php else: ?>
                            <span class="text-muted">-</span>
                          <?php endif; ?>
                        </td>

                        <td class="text-center">
                          <?php if ($email !== ''): ?>
                            <div class="d-inline-flex gap-2 flex-wrap justify-content-center">
                              <button type="button"
                                      class="btn btn-sm btn-outline-primary"
                                      data-bs-toggle="modal"
                                      data-bs-target="#<?= h($modalId) ?>">
                                <i class="bi bi-sliders"></i> Quản lý
                              </button>

                              <?php if (!$isAdmin && !$isSelf): ?>
                                <a class="btn btn-sm btn-outline-danger"
                                   href="admin_users.php?delete_email=<?= urlencode($email) ?>"
                                   onclick="return confirm('Xóa user này?');">
                                  Xóa
                                </a>
                              <?php else: ?>
                                <button class="btn btn-sm btn-outline-danger" disabled
                                        title="<?= $isAdmin ? 'Không xóa admin' : 'Không xóa chính mình' ?>">
                                  Xóa
                                </button>
                              <?php endif; ?>
                            </div>
                          <?php else: ?>
                            <span class="text-muted small">Thiếu email</span>
                          <?php endif; ?>
                        </td>
                      </tr>

                      <?php if ($email !== ''): ?>
                      <div class="modal fade" id="<?= h($modalId) ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content">
                            <div class="modal-header">
                              <div>
                                <h5 class="modal-title mb-0">Quản lý người dùng</h5>
                                <div class="text-muted small"><?= h($uname) ?> • <?= h($email) ?></div>
                              </div>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                              <div class="mb-3">
                                <div class="fw-semibold mb-2">Đổi quyền</div>
                                <?php if ($isAdmin): ?>
                                  <div class="text-muted small">Tài khoản admin (không đổi quyền tại đây).</div>
                                <?php else: ?>
                                  <form method="post" class="d-flex gap-2 align-items-center flex-wrap">
                                    <input type="hidden" name="action" value="changerole">
                                    <input type="hidden" name="email" value="<?= h($email) ?>">

                                    <select name="role" class="form-select form-select-sm" style="width:auto;">
                                      <option value="user"  <?= $role==='user'  ? 'selected' : '' ?>>user</option>
                                      <option value="admin" <?= $role==='admin' ? 'selected' : '' ?>>admin</option>
                                    </select>

                                    <button class="btn btn-sm btn-outline-secondary" type="submit">Lưu quyền</button>
                                  </form>
                                <?php endif; ?>
                              </div>

                              <hr>

                              <div>
                                <div class="fw-semibold mb-2">Đổi mật khẩu</div>
                                <form method="post"
                                      class="d-flex gap-2 align-items-center flex-wrap"
                                      onsubmit="return confirm('Đổi mật khẩu cho <?= h($email) ?> ?');">
                                  <input type="hidden" name="action" value="changepw">
                                  <input type="hidden" name="email" value="<?= h($email) ?>">

                                  <input type="password" name="new_password"
                                         class="form-control form-control-sm"
                                         placeholder="Mật khẩu mới" required>

                                  <button class="btn btn-sm btn-warning" type="submit">Đổi mật khẩu</button>
                                </form>

                                <div class="text-muted small mt-2">
                                  Mật khẩu sẽ được lưu dạng <b>hash</b>.
                                </div>
                              </div>
                            </div>

                            <div class="modal-footer">
                              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                            </div>
                          </div>
                        </div>
                      </div>
                      <?php endif; ?>

                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="text-muted small mt-2">
                Tip: Danh sách đang sắp theo <b>tài khoản tạo mới</b> (ưu tiên created_at, nếu thiếu sẽ theo thứ tự trong file).
              </div>

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
            Bảng điều khiển Admin TechStore.<br>
            Quản lý tài khoản người dùng & phân quyền.
          </p>
        </div>
        <div>
          <div class="title fw-bold mb-2">Lưu ý bảo mật</div>
          <p class="mb-1 small mb-0">
            Chỉ cấp quyền <b>admin</b> cho tài khoản cần thiết, và nên dùng mật khẩu mạnh.
          </p>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
