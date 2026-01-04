<?php
require_once 'auth.php';

$u = currentUser();

// ✅ Fix biến cho header search đồng bộ
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
  <title>Chính sách giao hàng - TechStore</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="logo.css">

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
      background: linear-gradient(120deg, rgba(13,110,253,.10), rgba(255,255,255,.96));
    }
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
    .ts-note{
      border-left: 4px solid #0d6efd;
      background: rgba(13,110,253,.06);
      border-radius: 14px;
      padding: 14px 16px;
    }
    .ts-warn{
      border-left: 4px solid #ffc107;
      background: rgba(255,193,7,.10);
      border-radius: 14px;
      padding: 14px 16px;
    }
    .accordion-button:focus{ box-shadow:none; }
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
            <span class="input-group-text"><i class="bi bi-search"></i></span>
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

        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chính sách giao hàng</li>
          </ol>
        </nav>

        <!-- HERO -->
        <div class="ts-hero p-4 p-md-5 mb-4">
          <span class="badge text-bg-primary-subtle text-primary mb-2">TechStore • Chính sách</span>
          <h1 class="h3 mb-2">Chính sách giao hàng</h1>
          <p class="mb-0 ts-muted">
            TechStore hỗ trợ giao nội thành TP Trà Vinh và giao tỉnh qua đơn vị vận chuyển. Thời gian có thể thay đổi theo thời điểm và khu vực.
          </p>
        </div>

        <!-- Quick points -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="ts-card p-3 h-100">
              <div class="d-flex gap-3">
                <div class="ts-icon"><i class="bi bi-truck"></i></div>
                <div>
                  <div class="fw-semibold">Nội thành TP Trà Vinh</div>
                  <div class="small ts-muted">Dự kiến 1–2 ngày làm việc sau khi xác nhận.</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ts-card p-3 h-100">
              <div class="d-flex gap-3">
                <div class="ts-icon"><i class="bi bi-geo-alt"></i></div>
                <div>
                  <div class="fw-semibold">Giao tỉnh</div>
                  <div class="small ts-muted">Dự kiến 2–5 ngày làm việc tùy khu vực.</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ts-card p-3 h-100">
              <div class="d-flex gap-3">
                <div class="ts-icon"><i class="bi bi-cash-coin"></i></div>
                <div>
                  <div class="fw-semibold">Hỗ trợ COD</div>
                  <div class="small ts-muted">Tùy khu vực; đơn giá trị cao có thể yêu cầu cọc.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <article class="ts-card p-3 p-md-4">

          <h5 class="mb-2">I. Phạm vi giao hàng</h5>
          <ul class="mb-4">
            <li><b>Nội thành:</b> giao trong khu vực TP. Trà Vinh.</li>
            <li><b>Ngoại thành & tỉnh/thành khác:</b> giao qua đối tác vận chuyển (GHN, Viettel Post, …).</li>
            <li>Có thể nhận tại nhà hoặc tại bưu cục tùy chính sách đối tác.</li>
          </ul>

          <h5 class="mb-2">II. Hình thức giao hàng</h5>
          <ul class="mb-4">
            <li><b>Giao tận nơi</b> theo địa chỉ khi đặt hàng.</li>
            <li><b>Nhận tại cửa hàng</b> (khi mua trực tiếp tại cửa hàng).</li>
            <li>Một số đơn có thể chia nhiều kiện nếu sản phẩm thuộc các kho khác nhau.</li>
          </ul>

          <h5 class="mb-2">III. Phí vận chuyển</h5>
          <div class="table-responsive mb-4">
            <table class="table align-middle">
              <thead class="table-light">
                <tr>
                  <th>Khu vực</th>
                  <th>Phí</th>
                  <th>Ghi chú</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><b>Nội thành TP Trà Vinh</b></td>
                  <td><span class="badge bg-success">Miễn phí</span></td>
                  <td class="small ts-muted">Thời gian giao có thể thay đổi theo tuyến và thời điểm.</td>
                </tr>
                <tr>
                  <td><b>Tỉnh/thành khác</b></td>
                  <td><span class="badge bg-success">Miễn phí</span></td>
                  <td class="small ts-muted">Có thể phát sinh phụ phí vùng xa theo đối tác (nếu có).</td>
                </tr>
              </tbody>
            </table>
          </div>

          <h5 class="mb-2">IV. Thời gian giao hàng dự kiến</h5>
          <ul class="mb-4">
            <li><b>Nội thành:</b> khoảng <b>1–2 ngày làm việc</b> sau khi đơn được xác nhận.</li>
            <li><b>Giao tỉnh:</b> khoảng <b>2–5 ngày làm việc</b> tùy địa lý và đơn vị vận chuyển.</li>
            <li>Lễ/Tết hoặc đợt khuyến mãi lớn có thể kéo dài do lượng đơn tăng.</li>
          </ul>

          <h5 class="mb-2">V. Thanh toán khi nhận hàng (COD)</h5>
          <ul class="mb-4">
            <li>Khách có thể chọn <b>COD</b> nếu khu vực hỗ trợ.</li>
            <li>Với đơn giá trị cao, TechStore có thể đề nghị <b>đặt cọc</b> để xác nhận đơn.</li>
          </ul>

          <h5 class="mb-2">VI. Quy định khi nhận hàng</h5>
          <div class="ts-warn mb-4">
            <div class="fw-semibold mb-1"><i class="bi bi-box2-heart me-1"></i> Kiểm tra trước khi nhận</div>
            <ul class="small mb-0">
              <li>Được kiểm tra <b>ngoại quan</b>: thùng/hộp, tem niêm phong, tình trạng móp méo.</li>
              <li>Nếu phát hiện dấu hiệu bị can thiệp: có thể <b>từ chối nhận</b> và liên hệ TechStore.</li>
              <li>Sau khi đã ký nhận, khiếu nại hư hỏng do vận chuyển sẽ được xem xét theo từng trường hợp.</li>
            </ul>
          </div>

          <h5 class="mb-2">VII. Trường hợp giao hàng chậm</h5>
          <ul class="mb-4">
            <li>Thời tiết xấu, thiên tai, dịch bệnh.</li>
            <li>Thiếu/sai thông tin địa chỉ hoặc số điện thoại.</li>
            <li>Sự cố phát sinh từ phía đối tác vận chuyển.</li>
            <li>TechStore sẽ cố gắng thông báo sớm nếu có nguy cơ chậm.</li>
          </ul>

          <h5 class="mb-2">VIII. Hoàn tiền khi đã thanh toán online</h5>
          <div class="ts-note">
            <div class="fw-semibold mb-1"><i class="bi bi-shield-check me-1"></i> Quy định hoàn tiền</div>
            <ul class="small mb-0">
              <li>Nếu đã thanh toán online mà đơn bị hủy/không thể giao do lỗi từ TechStore: hoàn tiền trong <b>7–14 ngày làm việc</b>.</li>
              <li>Khách cung cấp thông tin tài khoản ngân hàng để đối soát hoàn tiền.</li>
            </ul>
          </div>

          <!-- FAQ -->
          <div class="mt-4">
            <h5 class="mb-2">IX. Câu hỏi thường gặp</h5>
            <div class="accordion" id="faqShip">
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#s1">
                    Mình có được xem hàng trước khi trả COD không?
                  </button>
                </h2>
                <div id="s1" class="accordion-collapse collapse" data-bs-parent="#faqShip">
                  <div class="accordion-body small ts-muted">
                    Bạn được kiểm tra ngoại quan (tem/hộp/tình trạng kiện). Việc mở seal/kiểm tra sâu tùy chính sách đơn vị vận chuyển và từng loại sản phẩm.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#s2">
                    Nếu giao không tới được do mình cung cấp sai địa chỉ?
                  </button>
                </h2>
                <div id="s2" class="accordion-collapse collapse" data-bs-parent="#faqShip">
                  <div class="accordion-body small ts-muted">
                    TechStore/đối tác sẽ liên hệ lại để xác nhận. Nếu không liên lạc được hoặc địa chỉ không chính xác, đơn có thể bị hoàn về.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#s3">
                    Dịp lễ Tết có giao không?
                  </button>
                </h2>
                <div id="s3" class="accordion-collapse collapse" data-bs-parent="#faqShip">
                  <div class="accordion-body small ts-muted">
                    Tùy lịch làm việc của cửa hàng và đối tác vận chuyển. Thường thời gian sẽ chậm hơn do lượng đơn tăng.
                  </div>
                </div>
              </div>
            </div>
          </div>

        </article>

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
            Địa chỉ: 126 Nguyễn Thiện Thành, Phường Hòa Thuận, Tỉnh Vĩnh Long.<br>
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
