<?php
require_once 'auth.php';

$u = currentUser();

// ✅ FIX biến cho header search (đồng bộ index)
$keyword        = trim($_GET['q'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$brandFilter    = trim($_GET['brand'] ?? '');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hình thức thanh toán - TechStore</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- CSS cùng cấp -->
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="logo.css">

  <!-- ✅ CSS nhẹ cho trang -->
  <style>
    .ts-card{
      border: 1px solid rgba(0,0,0,.06);
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(15,23,42,.06);
      background:#fff;
    }
    .ts-hero{
      border: 1px solid rgba(0,0,0,.06);
      border-radius: 18px;
      overflow: hidden;
      background: linear-gradient(120deg, rgba(13,110,253,.10), rgba(255,255,255,.95));
    }
    .ts-hero .badge{ border: 1px solid rgba(13,110,253,.25); }
    .ts-icon{
      width: 44px; height: 44px;
      border-radius: 14px;
      display:grid; place-items:center;
      background: rgba(13,110,253,.10);
      color:#0d6efd;
      font-size: 20px;
      flex: 0 0 auto;
    }
    .ts-muted{ color:#6b7280; }
    .ts-kv ul{ margin-bottom: 0; }
    .ts-kv li{ margin-bottom: 6px; }
    .ts-note{
      border-left: 4px solid #0d6efd;
      background: rgba(13,110,253,.06);
      border-radius: 14px;
      padding: 14px 16px;
    }
  </style>
</head>

<body class="d-flex flex-column min-vh-100 bg-light">

  <!-- TOPBAR -->
  <div class="topbar small">
    <div class="container d-flex justify-content-between">
      <div>Hệ thống cửa hàng công nghệ TechStore</div>
      <div class="d-none d-md-block">Miễn phí giao hàng toàn quốc • Hỗ trợ: 0967 492 242</div>
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
    <section class="section py-4">
      <div class="container">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hình thức thanh toán</li>
          </ol>
        </nav>

        <!-- ✅ HERO gọn -->
        <div class="ts-hero p-4 p-md-5 mb-4">
          <span class="badge text-bg-primary-subtle text-primary mb-2">TechStore • Thanh toán</span>
          <h1 class="h3 mb-2">Hình thức thanh toán tại TechStore</h1>
          <p class="mb-0 ts-muted">
            TechStore hỗ trợ nhiều phương thức thanh toán để bạn lựa chọn linh hoạt khi mua online hoặc tại cửa hàng.
          </p>
        </div>

        <!-- ✅ 4 cards -->
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="ts-card p-3 p-md-4 h-100">
              <div class="d-flex align-items-start gap-3">
                <div class="ts-icon"><i class="bi bi-truck"></i></div>
                <div>
                  <h2 class="h6 mb-1">1) Thanh toán khi nhận hàng (COD)</h2>
                  <p class="small ts-muted mb-2">Phù hợp khi bạn muốn kiểm tra sản phẩm trước khi thanh toán.</p>
                  <ul class="small ts-kv">
                    <li>Áp dụng cho hầu hết đơn hàng nội thành và ngoại tỉnh.</li>
                    <li>Thanh toán tiền mặt cho nhân viên giao hàng sau khi kiểm tra.</li>
                    <li>Nên chuẩn bị số tiền tương ứng để nhận hàng nhanh.</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="ts-card p-3 p-md-4 h-100">
              <div class="d-flex align-items-start gap-3">
                <div class="ts-icon"><i class="bi bi-bank"></i></div>
                <div>
                  <h2 class="h6 mb-1">2) Chuyển khoản ngân hàng</h2>
                  <p class="small ts-muted mb-2">Chuyển khoản trước khi giao hàng hoặc mua tại cửa hàng.</p>
                  <ul class="small mb-2">
                    <li><b>Ngân hàng:</b> Sacombank </li>
                    <li><b>Chủ TK:</b> PHAN HOANG DINH </li>
                    <li><b>Số TK:</b> 070145453842 </li>
                    <li><b>Nội dung:</b> <code>Họ tên + SĐT + Mã đơn</code></li>
                  </ul>
                  <div class="small ts-muted mb-0">
                    Sau khi chuyển khoản, bạn có thể gửi ảnh biên lai để TechStore xác nhận nhanh hơn.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="ts-card p-3 p-md-4 h-100">
              <div class="d-flex align-items-start gap-3">
                <div class="ts-icon"><i class="bi bi-shop"></i></div>
                <div>
                  <h2 class="h6 mb-1">3) Thanh toán trực tiếp tại cửa hàng</h2>
                  <p class="small ts-muted mb-2">Dành cho khách mua trực tiếp tại TechStore.</p>
                  <ul class="small ts-kv">
                    <li>Tiền mặt tại quầy.</li>
                    <li>Thẻ ATM nội địa qua máy POS.</li>
                    <li>Thẻ Visa / MasterCard / JCB.</li>
                  </ul>
                  <div class="small ts-muted mt-2">
                    Có thể xuất hóa đơn bán hàng/VAT (nếu cung cấp đủ thông tin).
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="ts-card p-3 p-md-4 h-100">
              <div class="d-flex align-items-start gap-3">
                <div class="ts-icon"><i class="bi bi-credit-card-2-front"></i></div>
                <div>
                  <h2 class="h6 mb-1">4) Trả góp</h2>
                  <p class="small ts-muted mb-2">Có thể triển khai qua đối tác tài chính cho sản phẩm giá trị cao.</p>
                  <ul class="small ts-kv">
                    <li>Kỳ hạn tham khảo: 3 – 6 – 12 tháng.</li>
                    <li>Cần CMND/CCCD và giấy tờ theo gói trả góp.</li>
                    <li>Một số chương trình có thể hỗ trợ <b>lãi suất 0%</b>.</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ✅ Lưu ý bảo mật -->
        <div class="ts-note">
          <h2 class="h6 mb-2"><i class="bi bi-shield-check me-1"></i> Lưu ý và bảo mật thông tin thanh toán</h2>
          <ul class="small mb-0">
            <li>TechStore <b>không yêu cầu</b> cung cấp mật khẩu Internet Banking, mã OTP, PIN thẻ… qua điện thoại/tin nhắn/mạng xã hội.</li>
            <li>Chỉ thanh toán qua các kênh chính thức do nhân viên TechStore cung cấp.</li>
            <li>Kiểm tra kỹ số tiền, nội dung chuyển khoản và mã đơn trước khi xác nhận.</li>
            <li>Nếu phát hiện bất thường, hãy liên hệ hotline để được hỗ trợ kiểm tra.</li>
          </ul>
        </div>

      </div>
    </section>
  </main>

  <!-- FOOTER -->
  <footer class="footer border-top py-4">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="title h5 mb-2">TechStore</div>
          <p class="mb-0">
            Địa chỉ: 126 Đường Nguyễn Thiện Thành, Phường 5, TP Trà Vinh.<br>
            <a href="https://maps.app.goo.gl/pQUF56uYuuvMLzoH7"
               target="_blank"
               rel="noopener noreferrer"
               class="text-decoration-underline">
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
            <a href="tel:+0123456789" class="text-decoration-none d-flex align-items-center gap-2">
              <i class="bi bi-chat-dots"></i><span>Zalo: 0123 456 789</span>
            </a>
          </div>
        </div>

      </div>
    </div>
  </footer>

  <!-- MOBILE TABBAR -->
  <nav class="mobile-tabbar d-md-none">
    <a class="tab-item" href="index.php">
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
