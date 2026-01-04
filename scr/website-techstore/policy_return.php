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
  <title>Chính sách đổi trả - TechStore</title>

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
    .ts-danger{
      border-left: 4px solid #dc3545;
      background: rgba(220,53,69,.06);
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

        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chính sách đổi trả</li>
          </ol>
        </nav>

        <!-- ✅ HERO -->
        <div class="ts-hero p-4 p-md-5 mb-4">
          <span class="badge text-bg-primary-subtle text-primary mb-2">
            TechStore • Chính sách
          </span>
          <h1 class="h3 mb-2">Chính sách đổi trả tại TechStore</h1>
          <p class="mb-0 ts-muted">
            Áp dụng cho điện thoại, laptop, PC – màn hình, âm thanh và phụ kiện. Nội dung dưới đây dùng cho đồ án mô phỏng quy trình thực tế.
          </p>
        </div>

        <!-- ✅ Quick points -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="ts-card p-3 h-100">
              <div class="d-flex gap-3">
                <div class="ts-icon"><i class="bi bi-calendar-check"></i></div>
                <div>
                  <div class="fw-semibold">Đổi mới 7 ngày</div>
                  <div class="small ts-muted">Nếu lỗi do nhà sản xuất / không đúng tư vấn ban đầu.</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ts-card p-3 h-100">
              <div class="d-flex gap-3">
                <div class="ts-icon"><i class="bi bi-box-seam"></i></div>
                <div>
                  <div class="fw-semibold">Cần đủ phụ kiện</div>
                  <div class="small ts-muted">Hộp, seal (nếu có), quà tặng, tem còn nguyên.</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ts-card p-3 h-100">
              <div class="d-flex gap-3">
                <div class="ts-icon"><i class="bi bi-clipboard-check"></i></div>
                <div>
                  <div class="fw-semibold">Có hóa đơn / lịch sử mua</div>
                  <div class="small ts-muted">Dùng để xác minh giao dịch và xử lý nhanh.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <article class="ts-card p-3 p-md-4">

          <h5 class="mb-2">I. Phạm vi áp dụng</h5>
          <p class="ts-muted mb-2">Chính sách áp dụng cho các nhóm sản phẩm sau:</p>
          <ul class="mb-4">
            <li>Điện thoại, máy tính bảng.</li>
            <li>Laptop, ultrabook, gaming laptop.</li>
            <li>PC lắp ráp, màn hình máy tính.</li>
            <li>Tai nghe, loa, thiết bị âm thanh.</li>
            <li>Phụ kiện công nghệ (chuột, bàn phím, cáp, sạc, v.v.).</li>
          </ul>

          <h5 class="mb-2">II. Thời gian đổi trả</h5>
          <ul class="mb-4">
            <li><b>Đổi mới trong 7 ngày đầu</b> kể từ ngày mua: áp dụng khi sản phẩm lỗi do nhà sản xuất hoặc không đúng thông tin tư vấn.</li>
            <li>Sau 7 ngày: xử lý theo <b>chính sách bảo hành</b> (nếu có), không áp dụng đổi mới miễn phí.</li>
          </ul>

          <h5 class="mb-2">III. Điều kiện sản phẩm được đổi</h5>
          <p class="mb-1 ts-muted">Sản phẩm cần đáp ứng đầy đủ:</p>
          <ul class="mb-4">
            <li>Còn đầy đủ hộp, seal (nếu có), phụ kiện, quà tặng kèm theo.</li>
            <li>Tem bảo hành/tem niêm phong còn nguyên vẹn, không rách/tẩy xóa.</li>
            <li>Không trầy xước nặng, móp méo, nứt vỡ, vào nước, biến dạng do tác động bên ngoài.</li>
            <li>Có <b>hóa đơn</b> hoặc xác minh được lịch sử mua hàng tại TechStore.</li>
            <li>Không can thiệp phần cứng; không root/jailbreak/cài ROM không chính hãng.</li>
          </ul>

          <div class="ts-danger mb-4">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Không áp dụng đổi trả khi</div>
            <ul class="small mb-0">
              <li>Hư hỏng do người dùng: rơi vỡ, va đập, vào nước, cháy nổ, chập điện…</li>
              <li>Mất hóa đơn và không xác minh được thông tin mua hàng.</li>
              <li>Thiếu phụ kiện quan trọng / quà tặng khuyến mãi.</li>
              <li>Đã can thiệp phần mềm/phần cứng không theo hướng dẫn của hãng.</li>
              <li>Phần mềm, code kích hoạt, tài khoản điện tử đã kích hoạt.</li>
            </ul>
          </div>

          <h5 class="mb-2">IV. Quy trình đổi trả</h5>
          <ol class="mb-4">
            <li><b>Tiếp nhận yêu cầu:</b> khách liên hệ cửa hàng/hotline và cung cấp mã đơn, tình trạng lỗi.</li>
            <li class="mt-2"><b>Kiểm tra:</b> kỹ thuật kiểm tra, xác định nguyên nhân và đối chiếu điều kiện đổi trả.</li>
            <li class="mt-2"><b>Đề xuất xử lý:</b>
              <ul class="mt-1">
                <li>Đổi mới cùng model hoặc tương đương (nếu còn hàng).</li>
                <li>Đổi sang sản phẩm khác và bù/trả chênh lệch (theo thỏa thuận).</li>
                <li>Hoặc tiếp nhận bảo hành theo quy định hãng (nếu không đủ điều kiện đổi mới).</li>
              </ul>
            </li>
            <li class="mt-2"><b>Hoàn tất:</b> lập biên bản đổi trả/bảo hành và bàn giao lại sản phẩm.</li>
          </ol>

          <div class="ts-note">
            <div class="fw-semibold mb-1"><i class="bi bi-shield-check me-1"></i> Lưu ý</div>
            <ul class="small mb-0">
              <li>Nên <b>sao lưu dữ liệu</b> trước khi gửi máy đổi/trả/bảo hành.</li>
              <li>TechStore không chịu trách nhiệm dữ liệu bị mất trong quá trình kiểm tra/sửa chữa.</li>
              <li>Một số sản phẩm có thể áp dụng chính sách riêng của hãng.</li>
            </ul>
          </div>

          <!-- ✅ FAQ gọn -->
          <div class="mt-4">
            <h5 class="mb-2">V. Câu hỏi thường gặp</h5>
            <div class="accordion" id="faqReturn">
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f1">
                    Nếu mình mua online thì đổi trả thế nào?
                  </button>
                </h2>
                <div id="f1" class="accordion-collapse collapse" data-bs-parent="#faqReturn">
                  <div class="accordion-body small ts-muted">
                    Bạn liên hệ hotline/cửa hàng và cung cấp mã đơn. TechStore sẽ hướng dẫn mang đến cửa hàng hoặc gửi theo đơn vị vận chuyển (tùy trường hợp).
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f2">
                    Đổi sang sản phẩm khác có được không?
                  </button>
                </h2>
                <div id="f2" class="accordion-collapse collapse" data-bs-parent="#faqReturn">
                  <div class="accordion-body small ts-muted">
                    Có thể, tùy tình trạng hàng và thỏa thuận bù/trả chênh lệch tại thời điểm xử lý.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f3">
                    Mất hóa đơn thì có đổi được không?
                  </button>
                </h2>
                <div id="f3" class="accordion-collapse collapse" data-bs-parent="#faqReturn">
                  <div class="accordion-body small ts-muted">
                    Nếu TechStore xác minh được lịch sử mua hàng theo số điện thoại/tài khoản/mã đơn thì vẫn hỗ trợ theo quy định.
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
