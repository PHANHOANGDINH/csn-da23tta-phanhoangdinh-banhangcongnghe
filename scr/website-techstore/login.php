<?php
require_once 'auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $p     = trim($_POST['password'] ?? '');

    if ($email === '' || $p === '') {
        $error = 'Vui lòng nhập đầy đủ.';
    } elseif (!isValidEmail($email)) {
        $error = 'Email không hợp lệ. Vui lòng nhập lại.';
    } else {
        $user = findUserByEmail($email, $p);
        if ($user) {
            loginUser($user);
            header('Location: index.php');
            exit;
        } else {
            $error = 'Sai email hoặc mật khẩu.';
        }
    }
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8">
    <title>Đăng nhập - TechStore</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="logo.css">

    <style>
      .ts-auth-wrap{ padding: 36px 0 44px; }
      .ts-auth-card{
        border: 1px solid rgba(0,0,0,.06);
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15,23,42,.08);
        background: #fff;
      }
      .ts-auth-badge{
        width: 52px; height: 52px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        background: rgba(13,110,253,.10);
        color: #0d6efd;
        font-size: 22px;
        margin: 0 auto 10px;
      }
      .ts-soft{ color:#6b7280; }
      .input-group-text{ background:#f8fafc; }
    </style>
  </head>

  <body class="d-flex flex-column min-vh-100 bg-light">

    <div class="topbar small">
      <div class="container d-flex justify-content-between">
        <div>Hệ thống cửa hàng công nghệ TechStore</div>
        <div class="d-none d-md-block">Miễn phí giao hàng nội thành • Hỗ trợ: 0967 492 242</div>
      </div>
    </div>

    <header class="header py-2 border-bottom bg-white mb-3">
      <div class="container d-flex align-items-center justify-content-between gap-3">
        <a href="index.php" class="brand text-decoration-none d-flex align-items-center gap-2">
          <img src="img/logo/logo.png" alt="TechStore" class="ts-logo" width="120">
          <span class="fw-bold"></span>
        </a>

        <div class="d-flex align-items-center gap-2">
          <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-house"></i> Trang chủ
          </a>
          <a href="register.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-person-plus"></i> Đăng ký
          </a>
        </div>
      </div>
    </header>

    <main class="flex-grow-1 ts-auth-wrap">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">

            <div class="ts-auth-card p-4 p-md-4">
              <div class="text-center mb-3">
                <div class="ts-auth-badge"><i class="bi bi-person-lock"></i></div>
                <h1 class="h4 mb-1">Đăng nhập</h1>
                <div class="small ts-soft">Nhập email để tiếp tục</div>
              </div>

              <?php if ($error): ?>
                <div class="alert alert-danger py-2 mb-3">
                  <?php echo h($error); ?>
                </div>
              <?php endif; ?>

              <form method="post" novalidate>
                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input
                      type="email"
                      name="email"
                      class="form-control"
                      value="<?php echo h($_POST['email'] ?? ''); ?>"
                      placeholder="Tài khoản email"
                      required>
                  </div>
                </div>

                <div class="mb-2">
                  <label class="form-label">Mật khẩu</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input
                      id="password"
                      type="password"
                      name="password"
                      class="form-control"
                      placeholder="Nhập mật khẩu"
                      required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePass" aria-label="Hiện/ẩn mật khẩu">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                </div>

                <button class="btn btn-primary w-100 mt-3" type="submit">
                  <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
                </button>

                <div class="d-flex justify-content-between align-items-center mt-3 small">
                  <span class="ts-soft">Chưa có tài khoản?</span>
                  <a href="register.php" class="text-decoration-none">Đăng ký ngay</a>
                </div>
              </form>
            </div>

          </div>
        </div>
      </div>
    </main>

    <footer class="footer border-top py-4 mt-4">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
      (function(){
        const pass = document.getElementById('password');
        const btn = document.getElementById('togglePass');
        if(!pass || !btn) return;

        btn.addEventListener('click', function(){
          const isHidden = pass.type === 'password';
          pass.type = isHidden ? 'text' : 'password';
          btn.innerHTML = isHidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
      })();
    </script>
  </body>
</html>
