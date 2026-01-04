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
  <title>Chính sách bảo hành - TechStore</title>

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
      background: linear-gradient(120deg, rgba(25,135,84,.10), rgba(255,255,255,.96));
    }
    .ts-icon{
      width: 44px; height: 44px;
      border-radius: 14px;
      display:grid; place-items:center;
      background: rgba(25,135,84,.10);
      color:#198754;
      font-size: 20px;
      flex: 0 0 auto;
    }
    .ts-muted{ color:#6b7280; }
    .ts-note{
      border-left: 4px solid #198754;
      background: rgba(25,135,84,.06);
      border-radius: 14px;
      padding: 14px 16px;
    }
    .ts-warn{
      border-left: 4px solid #dc3545;
      background: rgba(220,53,69,.08);
      border-radius: 14px;
      padding: 14px 16px;
    }
    .accordion-button:focus{ box-shadow:none; }
    code{ background: rgba(0,0,0,.05); padding: 2px 6px; border-radius: 8px; }
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
            <li class="breadcrumb-item active" aria-current="page">Chính sách bảo hành</li>
          </ol>
        </nav>

        <!-- HERO -->
        <div class="ts-hero p-4 p-md-5 mb-4">
          <span class="badge text-bg-success-subtle text-success mb-2">TechStore • Chính sách</span>
          <h1 class="h3 mb-2">Chính sách bảo hành</h1>
          <p class="mb-0 ts-muted">
            TechStore hỗ trợ tiếp nhận bảo hành cho sản phẩm chính hãng. Thời hạn và quy trình có thể khác nhau theo từng hãng/dòng sản phẩm.
          </p>
        </div>

        <!-- Quick points -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="ts-card p-3 h-100">
              <div class="d-flex gap-3">
                <div class="ts-icon"><i class="bi bi-shield-check"></i></div>
                <div>
                  <div class="fw-semibold">Hỗ trợ gửi hãng</div>
                  <div class="small ts-muted">Mang tới TechStore để được hỗ trợ gửi trung tâm bảo hành.</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ts-card p-3 h-100">
              <div class="d-flex gap-3">
                <div class="ts-icon"><i class="bi bi-clock-history"></i></div>
                <div>
                  <div class="fw-semibold">Thời gian xử lý</div>
                  <div class="small ts-muted">Dự kiến 7–14 ngày làm việc (tùy linh kiện & hãng).</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ts-card p-3 h-100">
              <div class="d-flex gap-3">
                <div class="ts-icon"><i class="bi bi-receipt"></i></div>
                <div>
                  <div class="fw-semibold">Cần thông tin mua hàng</div>
                  <div class="small ts-muted">Hóa đơn / lịch sử mua / IMEI-Serial để đối chiếu.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <article class="ts-card p-3 p-md-4">

          <h5 class="mb-2">I. Phạm vi áp dụng</h5>
          <ul class="mb-4">
            <li>Áp dụng cho <b>tất cả sản phẩm chính hãng</b> mua tại TechStore.</li>
            <li>Thời gian bảo hành thường <b>tối thiểu 12 tháng</b> (tùy hãng/dòng sản phẩm có thể dài hơn).</li>
            <li>Chính sách tuân theo quy định của TechStore và quy định của từng hãng.</li>
          </ul>

          <h5 class="mb-2">II. Thời hạn bảo hành tham khảo</h5>
          <div class="table-responsive mb-4">
            <table class="table align-middle">
              <thead class="table-light">
                <tr>
                  <th>Nhóm sản phẩm</th>
                  <th>Thời hạn (tham khảo)</th>
                  <th>Ghi chú</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><b>Điện thoại / Tablet</b></td>
                  <td>12–24 tháng</td>
                  <td class="small ts-muted">Theo hãng & model.</td>
                </tr>
                <tr>
                  <td><b>Laptop / PC / Màn hình</b></td>
                  <td>12–24 tháng</td>
                  <td class="small ts-muted">Có thể dài hơn với một số dòng cao cấp.</td>
                </tr>
                <tr>
                  <td><b>Âm thanh (tai nghe/loa)</b></td>
                  <td>6–12 tháng</td>
                  <td class="small ts-muted">Tùy hãng & điều kiện sử dụng.</td>
                </tr>
                <tr>
                  <td><b>Phụ kiện</b></td>
                  <td>6–12 tháng</td>
                  <td class="small ts-muted">Một số phụ kiện có chính sách riêng.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <h5 class="mb-2">III. Hình thức bảo hành</h5>
          <ul class="mb-4">
            <li>Bảo hành tại <b>trung tâm bảo hành ủy quyền của hãng</b>.</li>
            <li>Bạn có thể:
              <ul>
                <li>Mang sản phẩm đến TechStore để hỗ trợ gửi hãng, hoặc</li>
                <li>Đến trực tiếp trung tâm bảo hành chính hãng gần nhất.</li>
              </ul>
            </li>
            <li>Thời gian xử lý thường <b>7–14 ngày làm việc</b> (có thể thay đổi tùy linh kiện).</li>
          </ul>

          <h5 class="mb-2">IV. Điều kiện được bảo hành</h5>
          <ul class="mb-4">
            <li>Còn thời hạn bảo hành (phiếu / hóa đơn / bảo hành điện tử).</li>
            <li>Tem/niêm phong còn nguyên, không rách, không tẩy xóa.</li>
            <li>Lỗi kỹ thuật do nhà sản xuất.</li>
            <li>IMEI/Serial trùng khớp giữa máy – hộp – chứng từ (nếu có).</li>
          </ul>

          <h5 class="mb-2">V. Trường hợp không được bảo hành</h5>
          <div class="ts-warn mb-4">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Các trường hợp thường bị từ chối</div>
            <ul class="small mb-0">
              <li>Rơi vỡ, va đập mạnh, cong vênh, nứt vỡ, vào nước/ẩm mốc/oxy hóa.</li>
              <li>Cháy nổ, chập điện, dùng sai điện áp, côn trùng xâm nhập.</li>
              <li>Đã can thiệp phần cứng hoặc sửa chữa tại nơi không ủy quyền.</li>
              <li>Root/jailbreak, cài ROM không chính hãng gây lỗi.</li>
              <li>Mất/hỏng tem, IMEI/Serial bị xóa hoặc không đọc được; không xác minh được mua hàng.</li>
            </ul>
          </div>

          <h5 class="mb-2">VI. Quy trình bảo hành</h5>
          <ol class="mb-4">
            <li>
              <b>Tiếp nhận:</b> cung cấp model + IMEI/Serial + mô tả lỗi + hóa đơn/lịch sử mua.
            </li>
            <li class="mt-2">
              <b>Kiểm tra sơ bộ:</b> xác định tình trạng, điều kiện bảo hành.
            </li>
            <li class="mt-2">
              <b>Gửi hãng:</b> trung tâm ủy quyền kiểm tra & sửa chữa/thay linh kiện.
            </li>
            <li class="mt-2">
              <b>Bàn giao:</b> TechStore thông báo kết quả và trả sản phẩm cho khách.
            </li>
          </ol>

          <h5 class="mb-2">VII. Pin và phụ kiện</h5>
          <div class="ts-note mb-4">
            <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i> Lưu ý</div>
            <ul class="small mb-0">
              <li>Một số hãng có thời gian bảo hành pin/sạc/cáp ngắn hơn thân máy.</li>
              <li>Chi tiết sẽ áp dụng theo chính sách từng hãng sản xuất.</li>
            </ul>
          </div>

          <h5 class="mb-2">VIII. Chính sách đổi mới</h5>
          <ul class="mb-0">
            <li>Trường hợp lỗi nặng không thể khắc phục, hãng có thể áp dụng <b>đổi mới</b> theo quy định.</li>
            <li>Điều kiện và phương án xử lý sẽ được thông báo cụ thể khi tiếp nhận.</li>
          </ul>

          <!-- FAQ -->
          <div class="mt-4">
            <h5 class="mb-2">IX. Câu hỏi thường gặp</h5>
            <div class="accordion" id="faqWarranty">
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#w1">
                    Bảo hành có mất phí không?
                  </button>
                </h2>
                <div id="w1" class="accordion-collapse collapse" data-bs-parent="#faqWarranty">
                  <div class="accordion-body small ts-muted">
                    Nếu lỗi do nhà sản xuất và còn thời hạn bảo hành thì thường không mất phí. Nếu lỗi do người dùng hoặc hết hạn, hãng có thể báo phí sửa chữa.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#w2">
                    Không còn hóa đơn thì có bảo hành được không?
                  </button>
                </h2>
                <div id="w2" class="accordion-collapse collapse" data-bs-parent="#faqWarranty">
                  <div class="accordion-body small ts-muted">
                    Tùy sản phẩm. Nếu hệ thống/IMEI/Serial xác minh được thời điểm mua và thời hạn bảo hành điện tử còn hiệu lực thì vẫn có thể tiếp nhận.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#w3">
                    Thời gian bảo hành bao lâu?
                  </button>
                </h2>
                <div id="w3" class="accordion-collapse collapse" data-bs-parent="#faqWarranty">
                  <div class="accordion-body small ts-muted">
                    Thường 7–14 ngày làm việc. Một số trường hợp chờ linh kiện có thể lâu hơn và TechStore sẽ thông báo.
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
            Địa chỉ: 126 Nguyễn Thiện Thành, Phường Hòa Thuận, Tỉnh Vĩnh Long.br>
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
