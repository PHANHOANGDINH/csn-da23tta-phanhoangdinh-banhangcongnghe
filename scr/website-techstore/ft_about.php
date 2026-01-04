<?php
require_once 'auth.php';

$u = currentUser();
$keyword = trim($_GET['q'] ?? '');

/** Fix: escape helper */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Fix: tránh undefined biến khi header dùng hidden input */
$categoryFilter = trim($_GET['category'] ?? '');
$brandFilter    = trim($_GET['brand'] ?? '');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giới thiệu TechStore</title>
    <meta name="description" content="Giới thiệu hệ thống cửa hàng công nghệ TechStore">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="logo.css">

    <!-- ✅ Thêm chút CSS riêng cho trang (nhẹ, không phá style.css) -->
    <style>
      .ts-hero{
        background:
          linear-gradient(120deg, rgba(13,110,253,.10), rgba(255,255,255,.85)),
          url("img/footer/nen.png");
        background-size: cover;
        background-position: center;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,.06);
      }
      .ts-hero .badge{
        backdrop-filter: blur(6px);
      }
      .ts-card{
        border: 1px solid rgba(0,0,0,.06);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(15,23,42,.06);
      }
      .ts-icon{
        width: 44px; height: 44px;
        display: inline-grid;
        place-items: center;
        border-radius: 12px;
        background: rgba(13,110,253,.10);
        color: #0d6efd;
        font-size: 20px;
      }
      .ts-img{
        border-radius: 18px;
        border: 1px solid rgba(0,0,0,.06);
        box-shadow: 0 10px 30px rgba(15,23,42,.06);
      }
      .ts-stat{
        border-radius: 16px;
        border: 1px dashed rgba(0,0,0,.14);
        background: #fff;
      }
      .ts-soft{
        color: #6b7280;
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

      <!-- ✅ HERO -->
      <section class="py-4">
        <div class="container">
          <div class="ts-hero p-4 p-md-5">
            <div class="row align-items-center g-4">
              <div class="col-lg-7">
                <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle mb-3">
                  TechStore • Hàng công nghệ chính hãng
                </span>
                <h1 class="display-6 fw-bold mb-2">Giới thiệu TechStore</h1>
                <p class="mb-3 ts-soft">
                  Website mô phỏng hệ thống thương mại điện tử: tìm kiếm sản phẩm, giỏ hàng, đơn hàng,
                  tài khoản người dùng và khu vực quản trị.
                </p>
                <div class="d-flex flex-wrap gap-2">
                  <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-bag-check me-1"></i> Khám phá sản phẩm
                  </a>
                  <a href="ft_stores.php" class="btn btn-outline-secondary">
                    <i class="bi bi-geo-alt me-1"></i> Hệ thống cửa hàng
                  </a>
                </div>
              </div>

              <div class="col-lg-5">
                <div class="ts-card bg-white p-3 p-md-4">
                  <div class="d-flex align-items-center gap-3">
                    <div class="ts-icon"><i class="bi bi-shield-check"></i></div>
                    <div>
                      <div class="fw-semibold">Minh bạch & chính hãng</div>
                      <div class="small text-muted">Thông tin rõ ràng, trải nghiệm mua sắm gọn gàng.</div>
                    </div>
                  </div>
                  <hr class="my-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="ts-icon"><i class="bi bi-truck"></i></div>
                    <div>
                      <div class="fw-semibold">Giao nhanh nội thành</div>
                      <div class="small text-muted">Hỗ trợ giao hàng và chính sách rõ ràng.</div>
                    </div>
                  </div>
                  <hr class="my-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="ts-icon"><i class="bi bi-headset"></i></div>
                    <div>
                      <div class="fw-semibold">Hỗ trợ nhiệt tình</div>
                      <div class="small text-muted">Tư vấn theo nhu cầu học tập, làm việc, giải trí.</div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </section>

      <!-- CONTENT -->
      <section class="section pb-4">
        <div class="container">
          <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
              <li class="breadcrumb-item active" aria-current="page">Giới thiệu</li>
            </ol>
          </nav>

          <div class="row g-4">
            <div class="col-lg-7">
              <article class="bg-white p-4 p-md-4 rounded-4 shadow-sm border">
                <h2 class="h4 mb-3">TechStore là gì?</h2>
                <p>
                  <strong>TechStore</strong> là hệ thống cửa hàng chuyên cung cấp các sản phẩm công nghệ
                  như điện thoại, laptop, PC – màn hình, thiết bị âm thanh và phụ kiện chính hãng,
                  hướng đến việc mang lại trải nghiệm mua sắm hiện đại, minh bạch và dễ tiếp cận cho mọi người.
                </p>
                <p>
                  Website TechStore trong đồ án này mô phỏng cách một hệ thống thương mại điện tử vận hành:
                  từ trưng bày sản phẩm, giỏ hàng, đơn hàng, tài khoản người dùng cho đến khu vực quản trị
                  (quản lý sản phẩm, người dùng, đơn hàng…).
                </p>

                <h5 class="mt-4">1. Tầm nhìn & sứ mệnh</h5>
                <p class="mb-2">
                  <strong>Tầm nhìn:</strong> Trở thành lựa chọn đáng tin cậy khi người dùng mua sắm thiết bị công nghệ trực tuyến –
                  nơi thông tin rõ ràng, giá cả minh bạch và dịch vụ hậu mãi được chú trọng.
                </p>
                <p class="mb-0">
                  <strong>Sứ mệnh:</strong> Mang sản phẩm công nghệ chính hãng đến gần hơn với người dùng,
                  giúp khách hàng chọn đúng thiết bị cho học tập, làm việc và giải trí qua trải nghiệm đơn giản, nhanh chóng.
                </p>

                <h5 class="mt-4">2. Danh mục sản phẩm chính</h5>
                <ul class="mb-0">
                  <li><strong>Điện thoại:</strong> smartphone từ phổ thông đến cao cấp.</li>
                  <li><strong>Laptop:</strong> văn phòng, học tập, đồ họa, gaming…</li>
                  <li><strong>PC – Màn hình:</strong> PC lắp ráp, văn phòng, màn hình độ phân giải cao.</li>
                  <li><strong>Âm thanh:</strong> tai nghe, loa Bluetooth, thiết bị audio.</li>
                  <li><strong>Phụ kiện:</strong> chuột, bàn phím, cáp – sạc, giá đỡ, v.v.</li>
                </ul>
              </article>
            </div>

            <div class="col-lg-5">
              <div class="bg-white p-4 rounded-4 shadow-sm border">
                <h3 class="h5 mb-3">Tổng quan nhanh</h3>

                <img
                  class="w-100 ts-img mb-3"
                  alt="TechStore - cửa hàng công nghệ"
                  src="img/footer/gioithieu.png">

                <div class="row g-2">
                  <div class="col-6">
                    <div class="ts-stat p-3">
                      <div class="fw-bold fs-5">5+</div>
                      <div class="small text-muted">Danh mục</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="ts-stat p-3">
                      <div class="fw-bold fs-5">100%</div>
                      <div class="small text-muted">Thông tin rõ</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="ts-stat p-3">
                      <div class="fw-bold fs-5">24/7</div>
                      <div class="small text-muted">Hỗ trợ</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="ts-stat p-3">
                      <div class="fw-bold fs-5">Fast</div>
                      <div class="small text-muted">Giao nội thành</div>
                    </div>
                  </div>
                </div>

                <hr class="my-4">

                <h3 class="h6 mb-2">Địa chỉ & liên hệ</h3>
                <p class="mb-2">
                  <i class="bi bi-geo-alt me-1"></i>
                  <strong>126 Nguyễn Thiện Thành, Phường Hòa Thuận, Tỉnh Vĩnh Long.</strong>
                </p>
                <p class="mb-2">
                  <i class="bi bi-telephone me-1"></i>
                  <a class="text-decoration-none" href="tel:0967492242">0967 492 242</a>
                </p>
                <p class="mb-0">
                  <i class="bi bi-envelope me-1"></i>
                  <a class="text-decoration-none" href="mailto:phanhoangdinh106@gmail.com">phanhoangdinh106@gmail.com</a>
                </p>

                <div class="d-grid gap-2 mt-3">
                  <a class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer"
                     href="https://maps.app.goo.gl/pQUF56uYuuvMLzoH7">
                    <i class="bi bi-map me-1"></i> Mở Google Maps
                  </a>
                  <a class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer"
                     href="https://www.facebook.com/dinhphan2910">
                    <i class="bi bi-facebook me-1"></i> Facebook
                  </a>
                </div>
              </div>

              <div class="bg-white p-4 rounded-4 shadow-sm border mt-4">
                <h3 class="h5 mb-3">Giá trị cốt lõi</h3>
                <ul class="mb-0">
                  <li><strong>Chính hãng:</strong> nguồn gốc rõ ràng, cấu hình minh bạch.</li>
                  <li><strong>Tư vấn đúng nhu cầu:</strong> tập trung trải nghiệm sử dụng.</li>
                  <li><strong>Hậu mãi:</strong> đổi trả, bảo hành, giao hàng trình bày rõ ràng.</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- ✅ Experience section -->
          <div class="bg-white p-4 rounded-4 shadow-sm border mt-4">
            <h3 class="h5 mb-3">Trải nghiệm người dùng trên website</h3>
            <div class="row g-3">
              <div class="col-md-6 col-lg-3">
                <div class="ts-card bg-white p-3 h-100">
                  <div class="ts-icon mb-2"><i class="bi bi-grid-1x2"></i></div>
                  <div class="fw-semibold">Danh mục rõ ràng</div>
                  <div class="small text-muted">Chia theo nhóm sản phẩm, dễ khám phá.</div>
                </div>
              </div>
              <div class="col-md-6 col-lg-3">
                <div class="ts-card bg-white p-3 h-100">
                  <div class="ts-icon mb-2"><i class="bi bi-cart-check"></i></div>
                  <div class="fw-semibold">Giỏ hàng & đặt hàng</div>
                  <div class="small text-muted">Quy trình mua sắm đơn giản, nhanh.</div>
                </div>
              </div>
              <div class="col-md-6 col-lg-3">
                <div class="ts-card bg-white p-3 h-100">
                  <div class="ts-icon mb-2"><i class="bi bi-receipt"></i></div>
                  <div class="fw-semibold">Theo dõi đơn hàng</div>
                  <div class="small text-muted">Xem lịch sử mua hàng theo tài khoản.</div>
                </div>
              </div>
              <div class="col-md-6 col-lg-3">
                <div class="ts-card bg-white p-3 h-100">
                  <div class="ts-icon mb-2"><i class="bi bi-speedometer2"></i></div>
                  <div class="fw-semibold">Khu vực Admin</div>
                  <div class="small text-muted">Quản lý sản phẩm, người dùng, đơn hàng.</div>
                </div>
              </div>
            </div>
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
              <a href="tel:+0967492242" class="text-decoration-none d-flex align-items-center gap-2">
                <i class="bi bi-chat-dots"></i><span>Zalo: 0967 492 242</span>
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
