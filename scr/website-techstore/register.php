<?php
require_once 'auth.php';

if (isLoggedIn()) {
  header('Location: index.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email    = trim($_POST['email'] ?? '');
  $username = trim($_POST['username'] ?? ''); // tên hiển thị
  $password = trim($_POST['password'] ?? '');
  $confirm  = trim($_POST['confirm'] ?? '');

  if ($email === '' || $username === '' || $password === '' || $confirm === '') {
    $error = 'Vui lòng nhập đầy đủ thông tin.';
  } elseif (!isValidEmail($email)) {
    $error = 'Email không hợp lệ. Vui lòng nhập lại.';
  } elseif (emailExists($email)) {
    $error = 'Email này đã được sử dụng.';
  } elseif (strlen($username) < 3) {
    $error = 'Tên hiển thị phải từ 3 ký tự trở lên.';
  } elseif (userExists($username)) {
    $error = 'Tên hiển thị đã được sử dụng.';
  } elseif (strlen($password) < 6) {
    $error = 'Mật khẩu nên từ 6 ký tự trở lên.';
  } elseif ($password !== $confirm) {
    $error = 'Mật khẩu xác nhận không khớp.';
  } else {
    createUserWithEmail($email, $username, $password, 'user');

    $user = findUserByEmail($email, $password);
    if ($user) {
      loginUser($user);
      header('Location: index.php');
      exit;
    } else {
      $error = 'Có lỗi khi tạo tài khoản, vui lòng thử lại.';
    }
  }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng ký tài khoản - TechStore</title>
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
      <div class="container d-flex align-items-center justify-content-between gap-3">
        <a href="index.php" class="brand text-decoration-none d-flex align-items-center gap-2">
          <img src="img/logo/logo.png" alt="TechStore" class="ts-logo">
        </a>

        <div class="d-flex align-items-center gap-2">
          <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-house"></i> Trang chủ
          </a>
          <a href="login.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
          </a>
        </div>
      </div>
    </div>
  </header>

  <main class="flex-grow-1">
    <div class="container py-4">
      <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5">
          <div class="card shadow-sm border-0">
            <div class="card-body p-4">
              <div class="text-center mb-3">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle border bg-white"
                     style="width:56px;height:56px;">
                  <i class="bi bi-person-plus fs-4"></i>
                </div>
                <h1 class="h4 mt-2 mb-0">Đăng ký</h1>
                <div class="text-muted small">Đăng ký bằng Email • Username là tên hiển thị</div>
              </div>

              <?php if ($error): ?>
                <div class="alert alert-danger py-2">
                  <i class="bi bi-exclamation-triangle me-1"></i>
                  <?php echo h($error); ?>
                </div>
              <?php endif; ?>

              <form method="post" class="vstack gap-3">
                <div>
                  <label class="form-label fw-semibold">Email</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input
                      type="email"
                      name="email"
                      class="form-control"
                      placeholder="Tài khoản email"
                      value="<?php echo h($_POST['email'] ?? ''); ?>"
                      required>
                  </div>
                </div>

                <div>
                  <label class="form-label fw-semibold">Tên hiển thị</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input
                      type="text"
                      name="username"
                      class="form-control"
                      placeholder="Tên hiển thị"
                      value="<?php echo h($_POST['username'] ?? ''); ?>"
                      required>
                  </div>
                  <div class="form-text">Tối thiểu 3 ký tự.</div>
                </div>

                <div>
                  <label class="form-label fw-semibold">Mật khẩu</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input
                      type="password"
                      name="password"
                      class="form-control"
                      placeholder="Tối thiểu 6 ký tự"
                      required>
                  </div>
                </div>

                <div>
                  <label class="form-label fw-semibold">Nhập lại mật khẩu</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                    <input
                      type="password"
                      name="confirm"
                      class="form-control"
                      placeholder="Nhập lại mật khẩu"
                      required>
                  </div>
                </div>

                <button class="btn btn-primary w-100" type="submit">
                  <i class="bi bi-check2-circle"></i> Tạo tài khoản
                </button>

                <div class="text-center small text-muted">
                  Đã có tài khoản? <a href="login.php">Đăng nhập</a>
                </div>
              </form>

            </div>
          </div>

          <div class="text-center text-muted small mt-3">
            Bằng việc đăng ký, bạn đồng ý với các chính sách của TechStore.
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="footer border-top py-4 mt-auto">
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
