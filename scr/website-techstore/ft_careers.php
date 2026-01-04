<?php
require_once 'auth.php';

$u = currentUser();
$keyword = trim($_GET['q'] ?? '');

/** ✅ FIX: helper escape */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** ✅ FIX: tránh undefined khi header dùng */
$categoryFilter = trim($_GET['category'] ?? '');
$brandFilter    = trim($_GET['brand'] ?? '');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tuyển dụng TechStore</title>
    <meta name="description" content="Thông tin tuyển dụng cho hệ thống TechStore">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="logo.css">

    <!-- ✅ Thêm chút CSS riêng, nhẹ, không phá style.css -->
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
      .job-card .card{ border-radius:16px; border:1px solid rgba(0,0,0,.06); }
      .job-card .card:hover{ transform: translateY(-2px); transition: .18s ease; }
      .badge-soft{
        background: rgba(13,110,253,.10);
        color:#0d6efd;
        border: 1px solid rgba(13,110,253,.20);
      }
      .ts-chip{
        display:inline-flex; align-items:center; gap:.35rem;
        padding:.35rem .6rem;
        border-radius:999px;
        border:1px solid rgba(0,0,0,.08);
        background:#fff;
        font-size:.85rem;
      }
    </style>
  </head>

  <body class="d-flex flex-column min-vh-100 bg-light">

    <!-- TOPBAR -->
    <div class="topbar small">
      <div class="container d-flex justify-content-between">
        <div>Hệ thống cửa hàng công nghệ TechStore</div>
        <div class="d-none d-md-block">Miễn phí giao hàng nội thành • Hỗ trợ: 0967 492 242</div>
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
                <span class="badge badge-soft mb-3">Tuyển dụng • TechStore</span>
                <h1 class="display-6 fw-bold mb-2">Gia nhập TechStore</h1>
                <p class="mb-3 ts-soft">
                  Môi trường trẻ trung – học hỏi nhanh – phù hợp sinh viên muốn trải nghiệm mô hình bán lẻ công nghệ hiện đại.
                </p>
                <div class="d-flex flex-wrap gap-2">
                  <a class="btn btn-primary" href="#vi-tri">
                    <i class="bi bi-briefcase me-1"></i> Xem vị trí
                  </a>
                  <a class="btn btn-outline-secondary" href="#ung-tuyen">
                    <i class="bi bi-send me-1"></i> Cách ứng tuyển
                  </a>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                  <span class="ts-chip"><i class="bi bi-clock"></i> Xoay ca</span>
                  <span class="ts-chip"><i class="bi bi-mortarboard"></i> Hỗ trợ lịch học</span>
                  <span class="ts-chip"><i class="bi bi-people"></i> Teamwork</span>
                </div>
              </div>

              <div class="col-lg-5">
                <div class="ts-card bg-white p-3 p-md-4">
                  <div class="d-flex align-items-center gap-3">
                    <div class="ts-icon"><i class="bi bi-lightning-charge"></i></div>
                    <div>
                      <div class="fw-semibold">Làm thật – học nhanh</div>
                      <div class="small text-muted">Tiếp xúc sản phẩm & quy trình vận hành.</div>
                    </div>
                  </div>
                  <hr class="my-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="ts-icon"><i class="bi bi-chat-heart"></i></div>
                    <div>
                      <div class="fw-semibold">Rèn kỹ năng mềm</div>
                      <div class="small text-muted">Giao tiếp, xử lý tình huống, tư vấn.</div>
                    </div>
                  </div>
                  <hr class="my-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="ts-icon"><i class="bi bi-graph-up"></i></div>
                    <div>
                      <div class="fw-semibold">Lộ trình rõ ràng</div>
                      <div class="small text-muted">Thực tập → Nhân viên → Tổ trưởng → Quản lý.</div>
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
              <li class="breadcrumb-item active" aria-current="page">Tuyển dụng</li>
            </ol>
          </nav>

          <div class="bg-white p-4 rounded-4 shadow-sm border">
            <h2 class="h4 mb-3">Tuyển dụng TechStore</h2>
            <p class="mb-2">
              <strong>TechStore</strong> không chỉ là nơi bán các sản phẩm công nghệ, mà còn là môi trường
              làm việc năng động dành cho những bạn trẻ yêu thích công nghệ, thích tư vấn, chăm sóc khách hàng
              và muốn trải nghiệm quy trình vận hành của một hệ thống bán lẻ hiện đại.
            </p>
            <p class="mb-0">
              Trang tuyển dụng này mô phỏng cách một doanh nghiệp giới thiệu nhu cầu nhân sự, vị trí làm việc và quy trình ứng tuyển.
            </p>

            <hr class="my-4">

            <h5 class="mt-0">1. Tại sao nên “gia nhập” TechStore?</h5>
            <div class="row g-3 mt-1">
              <div class="col-md-6 col-lg-3">
                <div class="ts-card bg-white p-3 h-100">
                  <div class="ts-icon mb-2"><i class="bi bi-emoji-smile"></i></div>
                  <div class="fw-semibold">Môi trường trẻ</div>
                  <div class="small text-muted">Khuyến khích học hỏi & trao đổi.</div>
                </div>
              </div>
              <div class="col-md-6 col-lg-3">
                <div class="ts-card bg-white p-3 h-100">
                  <div class="ts-icon mb-2"><i class="bi bi-cpu"></i></div>
                  <div class="fw-semibold">Tiếp xúc công nghệ</div>
                  <div class="small text-muted">Nhiều dòng sản phẩm, thương hiệu.</div>
                </div>
              </div>
              <div class="col-md-6 col-lg-3">
                <div class="ts-card bg-white p-3 h-100">
                  <div class="ts-icon mb-2"><i class="bi bi-people"></i></div>
                  <div class="fw-semibold">Kỹ năng mềm</div>
                  <div class="small text-muted">Giao tiếp, teamwork, xử lý tình huống.</div>
                </div>
              </div>
              <div class="col-md-6 col-lg-3">
                <div class="ts-card bg-white p-3 h-100">
                  <div class="ts-icon mb-2"><i class="bi bi-signpost-2"></i></div>
                  <div class="fw-semibold">Lộ trình phát triển</div>
                  <div class="small text-muted">Có định hướng theo năng lực.</div>
                </div>
              </div>
            </div>

            <h5 class="mt-4" id="vi-tri">2. Các vị trí tuyển dụng</h5>

            <div class="row g-3 job-card mt-1">
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                      <h5 class="card-title mb-1">Nhân viên bán hàng / Tư vấn sản phẩm</h5>
                      <span class="badge text-bg-success-subtle text-success border border-success-subtle">Part-time</span>
                    </div>
                    <p class="card-text small text-muted mb-2">
                      Làm việc tại cửa hàng – hỗ trợ khách chọn mua điện thoại, laptop, phụ kiện.
                    </p>
                    <ul class="small mb-2">
                      <li>Yêu thích công nghệ, giao tiếp tốt.</li>
                      <li>Biết máy tính cơ bản, nắm thông số phổ biến (RAM, ROM, CPU...).</li>
                      <li>Thời gian: xoay ca, linh hoạt theo lịch học.</li>
                    </ul>
                    <p class="small mb-0"><strong>Ưu tiên:</strong> sinh viên CNTT, QTKD, Marketing có nhu cầu làm thêm.</p>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                      <h5 class="card-title mb-1">Nhân viên chăm sóc khách hàng online</h5>
                      <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">Online</span>
                    </div>
                    <p class="card-text small text-muted mb-2">
                      Trả lời tin nhắn, tư vấn đơn hàng, hỗ trợ khách trên website & fanpage.
                    </p>
                    <ul class="small mb-2">
                      <li>Đánh máy nhanh, trình bày rõ ràng, lịch sự.</li>
                      <li>Biết dùng Facebook, Zalo, email.</li>
                      <li>Theo dõi trạng thái đơn hàng và phản hồi cho khách.</li>
                    </ul>
                    <p class="small mb-0"><strong>Ưu tiên:</strong> từng làm admin page/CSKH hoặc có kinh nghiệm TMĐT.</p>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                      <h5 class="card-title mb-1">Thực tập sinh IT / Quản trị website</h5>
                      <span class="badge text-bg-warning-subtle text-warning border border-warning-subtle">Intern</span>
                    </div>
                    <p class="card-text small text-muted mb-2">
                      Hỗ trợ kỹ thuật website: kiểm tra tính năng, cập nhật dữ liệu sản phẩm.
                    </p>
                    <ul class="small mb-0">
                      <li>Biết cơ bản HTML, CSS, PHP (hoặc tương đương).</li>
                      <li>Hiểu khái niệm tài khoản, phân quyền, sản phẩm, đơn hàng.</li>
                      <li>Cẩn thận dữ liệu, chịu học, không ngại thử nghiệm.</li>
                    </ul>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                      <h5 class="card-title mb-1">Cộng tác viên nội dung (Content)</h5>
                      <span class="badge text-bg-secondary-subtle text-secondary border border-secondary-subtle">Remote</span>
                    </div>
                    <p class="card-text small text-muted mb-2">
                      Viết mô tả sản phẩm, tin tức công nghệ, hướng dẫn sử dụng cho khách.
                    </p>
                    <ul class="small mb-2">
                      <li>Viết lách tốt, diễn đạt dễ hiểu.</li>
                      <li>Hứng thú smartphone, laptop, phụ kiện, game, v.v.</li>
                      <li>Làm online, gửi bài theo tuần.</li>
                    </ul>
                    <p class="small mb-0">
                      Phù hợp mô tả đồ án phần <strong>quản lý nội dung & marketing</strong>.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <h5 class="mt-4">3. Yêu cầu chung</h5>
            <ul class="mb-0">
              <li>Trung thực, trách nhiệm, thái độ nghiêm túc.</li>
              <li>Giao tiếp lịch sự, tôn trọng khách hàng và đồng nghiệp.</li>
              <li>Sẵn sàng học hỏi sản phẩm mới, quy trình mới.</li>
              <li>Tinh thần hỗ trợ, làm việc nhóm tốt.</li>
            </ul>

            <h5 class="mt-4" id="ung-tuyen">4. Cách thức ứng tuyển</h5>
            <p class="mb-1">Gửi thông tin theo <strong>mẫu đơn giản</strong> sau:</p>
            <ul>
              <li>Họ tên – Năm sinh.</li>
              <li>Ngành học / Công việc hiện tại.</li>
              <li>Vị trí muốn ứng tuyển.</li>
              <li>Kinh nghiệm liên quan (nếu có).</li>
              <li>Thời gian có thể làm việc.</li>
            </ul>
            <div class="p-3 rounded-4 border bg-light">
              <div class="row g-2 align-items-center">
                <div class="col-md">
                  Email: <a href="mailto:phanhoangdinh106@gmail.com">phanhoangdinh106@gmail.com</a><br>
                  Zalo: <strong>0967 492 242</strong>
                </div>
                <div class="col-md-auto d-flex gap-2">
                  <a class="btn btn-outline-primary" href="mailto:phanhoangdinh106@gmail.com">
                    <i class="bi bi-envelope me-1"></i> Gửi Email
                  </a>
                  <a class="btn btn-outline-secondary" href="tel:0967492242">
                    <i class="bi bi-telephone me-1"></i> Gọi nhanh
                  </a>
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
