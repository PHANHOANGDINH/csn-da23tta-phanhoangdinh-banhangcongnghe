<?php
require_once 'auth.php';

$u = currentUser();
$keyword = trim($_GET['q'] ?? '');

/** ✅ FIX: helper escape */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** ✅ FIX: tránh undefined khi header dùng */
$categoryFilter = trim($_GET['category'] ?? '');
$brandFilter    = trim($_GET['brand'] ?? '');

/** Link maps đúng */
$mapsUrl = "https://maps.app.goo.gl/pQUF56uYuuvMLzoH7";
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hệ thống cửa hàng TechStore</title>
    <meta name="description" content="Thông tin cửa hàng TechStore Trà Vinh và dịch vụ tại cửa hàng.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="logo.css">

    <!-- ✅ CSS nhẹ cho trang -->
    <style>
      .ts-hero{
        background:
          linear-gradient(120deg, rgba(13,110,253,.12), rgba(255,255,255,.88)),
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
      .ts-chip{
        display:inline-flex; align-items:center; gap:.35rem;
        padding:.35rem .6rem;
        border-radius:999px;
        border:1px solid rgba(0,0,0,.08);
        background:#fff;
        font-size:.85rem;
      }
      .ts-img{
        border-radius: 18px;
        border: 1px solid rgba(0,0,0,.06);
        box-shadow: 0 10px 30px rgba(15,23,42,.06);
      }
      .ts-map{
        width:100%;
        height: 300px;
        border:0;
        border-radius: 16px;
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
                  Hệ thống cửa hàng • TechStore
                </span>
                <h1 class="display-6 fw-bold mb-2">TechStore Vĩnh Long</h1>
                <p class="mb-3 ts-soft">
                  Mô hình đồ án: <strong>1 cửa hàng trung tâm</strong> kết hợp <strong>kênh bán online</strong> để mô phỏng hệ thống bán lẻ công nghệ hiện đại.
                </p>
                <div class="d-flex flex-wrap gap-2">
                  <a class="btn btn-primary" href="#dia-chi">
                    <i class="bi bi-geo-alt me-1"></i> Xem địa chỉ
                  </a>
                  <a class="btn btn-outline-secondary" href="#dich-vu">
                    <i class="bi bi-stars me-1"></i> Dịch vụ tại cửa hàng
                  </a>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                  <span class="ts-chip"><i class="bi bi-clock"></i> 8:00 – 21:00</span>
                  <span class="ts-chip"><i class="bi bi-truck"></i> Giao nội thành</span>
                  <span class="ts-chip"><i class="bi bi-shield-check"></i> Bảo hành – đổi trả</span>
                </div>
              </div>

              <div class="col-lg-5">
                <div class="ts-card bg-white p-3 p-md-4">
                  <div class="d-flex align-items-center gap-3">
                    <div class="ts-icon"><i class="bi bi-shop"></i></div>
                    <div>
                      <div class="fw-semibold">Cửa hàng trung tâm</div>
                      <div class="small text-muted">Trưng bày • tư vấn • tiếp nhận bảo hành.</div>
                    </div>
                  </div>
                  <hr class="my-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="ts-icon"><i class="bi bi-box-seam"></i></div>
                    <div>
                      <div class="fw-semibold">Kho & đơn hàng tập trung</div>
                      <div class="small text-muted">Xử lý đơn online từ một điểm.</div>
                    </div>
                  </div>
                  <hr class="my-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="ts-icon"><i class="bi bi-globe"></i></div>
                    <div>
                      <div class="fw-semibold">Kênh online đồng bộ</div>
                      <div class="small text-muted">Khách đặt web – theo dõi “Đơn hàng của tôi”.</div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </section>

      <section class="section pb-4">
        <div class="container">

          <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
              <li class="breadcrumb-item active" aria-current="page">Hệ thống cửa hàng</li>
            </ol>
          </nav>

          <div class="bg-white p-4 rounded-4 shadow-sm border">
            <h2 class="h4 mb-3">Hệ thống cửa hàng TechStore</h2>
            <p class="mb-0">
              Trong phiên bản đồ án này, <strong>TechStore</strong> được xây dựng dưới mô hình
              một cửa hàng chính kết hợp với kênh bán hàng online. Điều này giúp bạn
              dễ mô tả trong báo cáo: vừa có cửa hàng (offline), vừa có website (online)
              phục vụ khách hàng.
            </p>

            <hr class="my-4">

            <h5 class="mt-0" id="dia-chi">1. Cửa hàng TechStore Vĩnh Long (cơ sở duy nhất)</h5>

            <div class="row g-3 mb-3">
              <div class="col-lg-7">
                <div class="ts-card bg-white p-4 h-100">
                  <div class="d-flex align-items-start gap-3">
                    <div class="ts-icon"><i class="bi bi-geo-alt"></i></div>
                    <div>
                      <h5 class="mb-1">TechStore Vĩnh Long</h5>
                      <div class="text-muted small mb-2">Cơ sở trung tâm trong mô hình đồ án</div>

                      <p class="mb-1"><strong>Địa chỉ:</strong> 126 Nguyễn Thiện Thành, Phường Hòa Thuận, Tỉnh Vĩnh Long</p>
                      <p class="mb-1"><strong>Giờ mở cửa:</strong> 8h00 – 21h00 (tất cả các ngày)</p>
                      <p class="mb-2"><strong>Hotline:</strong> 0967 492 242</p>

                      <p class="text-muted small mb-2">
                        Cửa hàng trưng bày các sản phẩm: điện thoại, laptop, PC - màn hình, thiết bị âm thanh và phụ kiện.
                        Khách có thể đến xem trực tiếp, trải nghiệm sản phẩm mẫu và được tư vấn bởi nhân viên.
                      </p>

                      <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-sm btn-primary" target="_blank" rel="noopener noreferrer" href="<?php echo h($mapsUrl); ?>">
                          <i class="bi bi-map me-1"></i> Xem Google Maps
                        </a>
                        <a class="btn btn-sm btn-outline-secondary" href="index.php">
                          <i class="bi bi-bag me-1"></i> Mua sắm online
                        </a>
                      </div>

                      <div class="alert alert-info mt-3 mb-0 small">
                        <strong>Ghi chú đồ án:</strong> xem đây là “cửa hàng trung tâm” chịu trách nhiệm
                        tiếp nhận bảo hành, giao hàng nội thành, và nhập – xuất kho cho toàn hệ thống.
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-lg-5">
                <div class="ts-card bg-white p-4 h-100">
                  <h6 class="mb-2">Bản đồ & hướng dẫn đường đi</h6>
                  <p class="small text-muted mb-3">
                    Vị trí cửa hàng nằm trên trục đường Nguyễn Thiện Thành, thuận tiện cho sinh viên và người đi làm khu vực TP Trà Vinh.
                  </p>

                  <img
                    class="w-100 ts-img mb-3"
                    alt="Cửa hàng TechStore"
                    src="img/footer/ktcn.png">

                  <ul class="small mb-3">
                    <li>Gần khu dân cư, trường học.</li>
                    <li>Có chỗ để xe máy miễn phí cho khách.</li>
                    <li>Hỗ trợ giao hàng nội thành trong ngày (đơn đủ điều kiện).</li>
                  </ul>

                  <!-- ✅ Nếu muốn iframe Google Maps: bật đoạn dưới (tuỳ chọn) -->
                  <!--
                  <iframe class="ts-map"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps?q=126%20Nguy%E1%BB%85n%20Thi%E1%BB%87n%20Th%C3%A0nh%2C%20Tr%C3%A0%20Vinh&output=embed">
                  </iframe>
                  -->
                </div>
              </div>
            </div>

            <h5 class="mt-4" id="dich-vu">2. Dịch vụ tại cửa hàng TechStore Vĩnh Long</h5>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <div class="ts-card bg-white p-3 h-100">
                  <div class="d-flex align-items-start gap-3">
                    <div class="ts-icon"><i class="bi bi-chat-dots"></i></div>
                    <div>
                      <div class="fw-semibold mb-1">Tư vấn – trải nghiệm</div>
                      <ul class="small mb-0">
                        <li>Tư vấn chọn mua điện thoại, laptop, PC, màn hình và phụ kiện theo nhu cầu.</li>
                        <li>Cho phép khách dùng thử một số sản phẩm (tai nghe, loa, chuột, bàn phím…).</li>
                        <li>Hỗ trợ <strong>cài đặt phần mềm cơ bản</strong> (Office, trình duyệt, gõ tiếng Việt…) cho máy mới.</li>
                        <li>Hướng dẫn khách tạo tài khoản và đặt hàng trực tiếp trên website TechStore.</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="ts-card bg-white p-3 h-100">
                  <div class="d-flex align-items-start gap-3">
                    <div class="ts-icon"><i class="bi bi-shield-check"></i></div>
                    <div>
                      <div class="fw-semibold mb-1">Bảo hành – thanh toán – giao hàng</div>
                      <ul class="small mb-0">
                        <li>Tiếp nhận bảo hành, đổi trả theo <a href="policy_warranty.php">chính sách bảo hành</a> và <a href="policy_return.php">chính sách đổi trả</a>.</li>
                        <li>Hỗ trợ thanh toán tiền mặt; thanh toán chuyển khoản.</li>
                        <li>Tư vấn khuyến mãi, mã giảm giá, combo sản phẩm (nếu có).</li>
                        <li>Đặt hàng giúp khách và giao hàng đến tận nhà trong nội thành.</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <h5 class="mt-4">3. Kênh bán hàng online</h5>
            <p class="mb-2">
              Bên cạnh cửa hàng, TechStore còn có kênh bán hàng online qua website. Mọi đơn hàng online sẽ được:
            </p>
            <ul class="mb-0">
              <li>Xử lý và xuất kho tại TechStore Vĩnh Long.</li>
              <li>Giao nội thành hoặc gửi đơn vị vận chuyển nếu mở rộng.</li>
              <li>Đồng bộ lịch sử mua hàng của khách trong mục “Đơn hàng của tôi”.</li>
            </ul>

            <h5 class="mt-4">4. Gợi ý cho phần mô tả hệ thống trong báo cáo</h5>
            <p class="mb-1">Bạn có thể tận dụng trang này để nói rõ hơn về mô hình triển khai:</p>
            <ul class="mb-0">
              <li>Hệ thống hiện tại có <strong>1 cửa hàng</strong> + <strong>1 kênh online</strong>.</li>
              <li>Có thể mở rộng nhiều chi nhánh (thêm record trong bảng/collection <code>stores</code> sau này).</li>
              <li>Kho hàng, đơn hàng, khách hàng quản lý tập trung; Vĩnh Long là điểm giao dịch chính.</li>
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
              Địa chỉ: 126 Nguyễn Thiện Thành, Phường Hòa Thuận, Tỉnh Vĩnh Long.<br>
              <a href="<?php echo h($mapsUrl); ?>"
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
