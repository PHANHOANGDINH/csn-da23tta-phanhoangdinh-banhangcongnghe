<?php
require_once 'auth.php';

$u = currentUser();
$keyword = trim($_GET['q'] ?? '');

$categoryFilter = trim($_GET['category'] ?? '');
$brandFilter    = trim($_GET['brand'] ?? '');

$mapsUrl = "https://maps.app.goo.gl/pQUF56uYuuvMLzoH7";

// ✅ Load mail config
$configPath = __DIR__ . '/config/mail.php';
$mailCfg = null;
if (is_file($configPath)) {
  $mailCfg = require $configPath;
}

/** =====================
 * Load profile để autofill
===================== */
$profilesFile = __DIR__ . '/data/user_profiles.json';
$profiles = [];
if (file_exists($profilesFile)) {
  $profiles = json_decode(file_get_contents($profilesFile), true);
  if (!is_array($profiles)) $profiles = [];
}

$prefillName = '';
$prefillPhone = '';
$prefillEmail = '';

if ($u) {
  $uEmail = trim((string)($u['email'] ?? ''));
  $uName  = (string)($u['username'] ?? '');

  $key = $uEmail !== '' ? strtolower($uEmail) : $uName;
  $pf = $profiles[$key] ?? null;

  $prefillEmail = $uEmail;
  $prefillName  = trim((string)(($pf['fullname'] ?? '') !== '' ? $pf['fullname'] : $uName));
  $prefillPhone = trim((string)($pf['phone'] ?? ''));
}

// =====================
// Handle submit
// =====================
$errors = [];
$success = '';
$serverError = '';

$name    = trim($_POST['name'] ?? ($prefillName ?? ''));
$email   = trim($_POST['email'] ?? ($prefillEmail ?? ''));
$phone   = trim($_POST['phone'] ?? ($prefillPhone ?? ''));
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$mailCfg) {
    $serverError = "Thiếu file cấu hình <code>config/mail.php</code>. Bạn tạo file này rồi thử lại nhé.";
  } else {
    if ($name === '') $errors[] = "Vui lòng nhập họ tên.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ.";
    if ($subject === '') $errors[] = "Vui lòng nhập chủ đề.";
    if (mb_strlen($message) < 10) $errors[] = "Nội dung tối thiểu 10 ký tự.";

    if (!$errors) {
      require_once __DIR__ . '/PHPMailer/src/Exception.php';
      require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
      require_once __DIR__ . '/PHPMailer/src/SMTP.php';

      $body = "
      <div style='font-family:Arial,sans-serif;font-size:14px;line-height:1.6'>
        <h2 style='margin:0 0 10px'>Liên hệ mới từ TechStore</h2>
        <p><b>Họ tên:</b> ".h($name)."</p>
        <p><b>Email:</b> ".h($email)."</p>
        <p><b>SĐT:</b> ".h($phone)."</p>
        <p><b>Chủ đề:</b> ".h($subject)."</p>
        <hr>
        <p style='white-space:pre-wrap;margin:0'>".nl2br(h($message))."</p>
        <hr>
        <p style='color:#6b7280;margin:0'>Gửi từ form Liên hệ - TechStore</p>
      </div>";

      try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $mailCfg['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailCfg['smtp_user'];
        $mail->Password   = $mailCfg['smtp_pass'];
        $mail->SMTPSecure = $mailCfg['smtp_secure'];
        $mail->Port       = (int)$mailCfg['smtp_port'];

        $mail->CharSet = 'UTF-8';

        $mail->setFrom($mailCfg['from_email'], $mailCfg['from_name']);
        $mail->addAddress($mailCfg['to_email'], $mailCfg['from_name']);
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "[TechStore] " . $subject;
        $mail->Body    = $body;

        $mail->send();
        $success = "✅ Gửi liên hệ thành công! TechStore đã nhận được email và sẽ phản hồi sớm.";

        // reset subject/message thôi, giữ name/email/phone để user đỡ nhập lại
        $subject = $message = '';
      } catch (Exception $e) {
        $serverError = "❌ Gửi thất bại. Lỗi mailer: <code>".h($e->getMessage())."</code>";
      }
    }
  }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Liên hệ - TechStore</title>
  <meta name="description" content="Liên hệ TechStore Vĩnh Long. Gửi thông tin để shop hỗ trợ qua email.">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="logo.css">

  <style>
    .ts-hero{
      background:
        linear-gradient(120deg, rgba(13,110,253,.12), rgba(255,255,255,.9)),
        url("img/footer/nen.png");
      background-size: cover;
      background-position: center;
      border-radius: 18px;
      overflow: hidden;
      border: 1px solid rgba(0,0,0,.06);
    }
    .ts-card{
      border: 1px solid rgba(0,0,0,.06);
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(15,23,42,.06);
      background:#fff;
    }
    .ts-icon{
      width: 44px; height: 44px;
      display: inline-grid;
      place-items: center;
      border-radius: 12px;
      background: rgba(13,110,253,.10);
      color: #0d6efd;
      font-size: 20px;
      flex: 0 0 auto;
    }
    .ts-soft{ color:#6b7280; }
    .ts-map{
      width:100%;
      height: 260px;
      border:0;
      border-radius: 16px;
    }
    .form-control, .form-select{ border-radius: 12px; }
    .btn{ border-radius: 12px; }
  </style>
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
      <div class="container d-flex align-items-center gap-3">
        <a href="index.php" class="brand text-decoration-none d-flex align-items-center gap-2">
          <img src="img/logo/logo.png" alt="TechStore" class="ts-logo">
        </a>

        <form class="search flex-grow-1 d-flex" action="index.php" method="get">
          <div class="input-group w-100">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" type="search" name="q" value="<?php echo h($keyword); ?>"
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

    <section class="py-4">
      <div class="container">
        <div class="ts-hero p-4 p-md-5">
          <div class="row g-4 align-items-center">
            <div class="col-lg-7">
              <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle mb-3">
                Liên hệ • Gửi mail thật bằng Gmail
              </span>
              <h1 class="display-6 fw-bold mb-2">Liên hệ TechStore</h1>
              <p class="mb-0 ts-soft">
                Nếu bạn đã đăng nhập, hệ thống sẽ <b>tự điền Email/Họ tên/SĐT</b> từ tài khoản và hồ sơ.
              </p>
            </div>
            <div class="col-lg-5">
              <div class="ts-card p-4">
                <div class="d-flex align-items-start gap-3">
                  <div class="ts-icon"><i class="bi bi-shield-check"></i></div>
                  <div>
                    <div class="fw-semibold">Bảo mật</div>
                    <div class="small text-muted">
                      Dùng <b>Mật khẩu ứng dụng</b> của Google, không dùng mật khẩu Gmail thường.
                    </div>
                  </div>
                </div>
                <hr class="my-3">
                <div class="d-flex align-items-start gap-3">
                  <div class="ts-icon"><i class="bi bi-envelope-paper"></i></div>
                  <div>
                    <div class="fw-semibold">Phản hồi nhanh</div>
                    <div class="small text-muted">Email người gửi được gắn Reply-To để shop trả lời tiện.</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="pb-5">
      <div class="container">

        <?php if ($serverError): ?>
          <div class="alert alert-danger rounded-4 border shadow-sm"><?php echo $serverError; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success rounded-4 border shadow-sm"><?php echo h($success); ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
          <div class="alert alert-warning rounded-4 border shadow-sm">
            <div class="fw-semibold mb-1">Vui lòng kiểm tra lại:</div>
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?>
                <li><?php echo h($e); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="row g-4">
          <div class="col-lg-7">
            <div class="ts-card p-4 p-md-5">
              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="ts-icon"><i class="bi bi-envelope"></i></div>
                <div>
                  <h2 class="h4 mb-1">Form liên hệ TechStore</h2>
                  <div class="ts-soft">Điền thông tin, bấm gửi — hệ thống sẽ gửi mail thật.</div>
                </div>
              </div>

              <form method="post" novalidate>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                    <input class="form-control" name="name" value="<?php echo h($name); ?>" placeholder="Ví dụ: Phát">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input class="form-control" name="email" value="<?php echo h($email); ?>" placeholder="abc@gmail.com" <?php echo ($u && $prefillEmail !== '') ? 'readonly' : ''; ?>>
                    <?php if ($u && $prefillEmail !== ''): ?>
                      <div class="form-text ts-soft">Email lấy từ tài khoản đang đăng nhập (readonly).</div>
                    <?php endif; ?>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Số điện thoại</label>
                    <input class="form-control" name="phone" value="<?php echo h($phone); ?>" placeholder="0xxxxxxxxx">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Chủ đề <span class="text-danger">*</span></label>
                    <input class="form-control" name="subject" value="<?php echo h($subject); ?>" placeholder="Hỏi về sản phẩm / đơn hàng...">
                  </div>

                  <div class="col-12">
                    <label class="form-label">Nội dung <span class="text-danger">*</span></label>
                    <textarea class="form-control" rows="7" name="message" placeholder="Nội dung tối thiểu 10 ký tự..."><?php echo h($message); ?></textarea>
                    <div class="form-text">Gợi ý: nếu hỏi đơn hàng, bạn nên kèm mã đơn (nếu có).</div>
                  </div>

                  <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                    <button class="btn btn-primary px-4" type="submit">
                      <i class="bi bi-send me-1"></i> Gửi liên hệ
                    </button>
                    <a class="btn btn-outline-secondary" href="index.php">
                      <i class="bi bi-bag me-1"></i> Quay lại mua sắm
                    </a>
                  </div>
                </div>
              </form>

            </div>
          </div>

          <div class="col-lg-5">
            <div class="ts-card p-4 p-md-5 h-100">
              <h3 class="h5 mb-1">Thông tin liên hệ</h3>
              <div class="ts-soft mb-3">TechStore Vĩnh Long — cửa hàng trung tâm mô hình đồ án.</div>

              <div class="d-flex gap-3 mb-3">
                <div class="ts-icon"><i class="bi bi-geo-alt"></i></div>
                <div>
                  <div class="fw-semibold">Địa chỉ</div>
                  <div>126 Nguyễn Thiện Thành, Phường Hòa Thuận, Tỉnh Vĩnh Long</div>
                  <a class="text-decoration-underline" target="_blank" rel="noopener noreferrer" href="<?php echo h($mapsUrl); ?>">
                    Mở Google Maps
                  </a>
                </div>
              </div>

              <div class="d-flex gap-3 mb-3">
                <div class="ts-icon"><i class="bi bi-telephone"></i></div>
                <div>
                  <div class="fw-semibold">Hotline</div>
                  <div>0967 492 242</div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-3">
                <div class="ts-icon"><i class="bi bi-envelope-at"></i></div>
                <div>
                  <div class="fw-semibold">Email</div>
                  <div>phanhoangdinh106@gmail.com</div>
                </div>
              </div>

              <hr>

              <div class="d-flex flex-column gap-2">
                <a href="https://www.facebook.com/dinhphan2910" target="_blank" rel="noopener noreferrer"
                   class="text-decoration-none d-flex align-items-center gap-2">
                  <i class="bi bi-facebook"></i><span>Facebook</span>
                </a>
                <a href="tel:+0967492242" class="text-decoration-none d-flex align-items-center gap-2">
                  <i class="bi bi-chat-dots"></i><span>Zalo: 0967 492 242</span>
                </a>
              </div>

              <div class="mt-4">
                <iframe class="ts-map"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  src="https://www.google.com/maps?q=126%20Nguy%E1%BB%85n%20Thi%E1%BB%87n%20Th%C3%A0nh%2C%20Tr%C3%A0%20Vinh&output=embed">
                </iframe>
              </div>

            </div>
          </div>
        </div>

      </div>
    </section>

  </main>

  <footer class="footer border-top py-4">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="title h5 mb-2">TechStore</div>
          <p class="mb-0">
            Địa chỉ: 126 Nguyễn Thiện Thành, Phường Hòa Thuận, Tỉnh Vĩnh Long.<br>
            <a href="<?php echo h($mapsUrl); ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-underline">
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
            <a href="mailto:phanhoangdinh106@gmail.com" class="text-break">
              phanhoangdinh106@gmail.com
            </a>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
