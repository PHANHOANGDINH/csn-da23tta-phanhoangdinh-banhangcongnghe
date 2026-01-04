<?php
const COMMENTS_FILE = __DIR__ . "/data/comments.json";

function loadComments() {
    if (!file_exists(COMMENTS_FILE)) return [];
    $json = file_get_contents(COMMENTS_FILE);
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}

function saveComments($comments) {
    file_put_contents(
        COMMENTS_FILE,
        json_encode($comments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

// Hàm render sao (text ★★★☆☆)
function renderStars($rating) {
    $rating = (int)$rating;
    if ($rating < 1) $rating = 0;
    if ($rating > 5) $rating = 5;

    $full  = str_repeat("★", $rating);
    $empty = str_repeat("☆", 5 - $rating);

    return $full . $empty;
}

$comments = loadComments();

// Xử lý gửi bình luận + đánh giá sao
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {
    if (!$user) {
        echo "<div class='alert alert-danger py-2 mb-3'>Bạn cần đăng nhập để bình luận.</div>";
    } else {
        $text   = trim($_POST['comment_text']);
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;

        if ($text === '') {
            echo "<div class='alert alert-danger py-2 mb-3'>Nội dung bình luận không được rỗng.</div>";
        } elseif ($rating < 1 || $rating > 5) {
            echo "<div class='alert alert-danger py-2 mb-3'>Vui lòng chọn số sao từ 1 đến 5.</div>";
        } else {
            $comments[] = [
                'product_id' => $product['id'],
                'username'   => $user['username'],
                'text'       => $text,
                'time'       => date("Y-m-d H:i"),
                'rating'     => $rating
            ];
            saveComments($comments);

            // Không dùng header() nữa vì trang đã xuất HTML
            echo "<script>window.location.href='product_detail.php?id=".$product['id']."';</script>";
            exit;
        }
    }
}

// Lọc bình luận theo sản phẩm
$productComments = array_filter($comments, function($c) use ($product) {
    return $c['product_id'] == $product['id'];
});
?>

<!-- FORM BÌNH LUẬN & ĐÁNH GIÁ -->
<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <h3 class="h5 mb-3">Bình luận &amp; đánh giá</h3>

    <?php if ($user): ?>
      <form method="post" class="vstack gap-3">
        <div>
          <label class="form-label">Đánh giá của bạn</label>
          <select name="rating" class="form-select form-select-sm" required>
            <option value="">-- Chọn số sao --</option>
            <option value="5">★★★★★ • Rất tốt</option>
            <option value="4">★★★★☆ • Tốt</option>
            <option value="3">★★★☆☆ • Bình thường</option>
            <option value="2">★★☆☆☆ • Chưa tốt</option>
            <option value="1">★☆☆☆☆ • Rất tệ</option>
          </select>
        </div>

        <div>
          <label class="form-label">Viết bình luận của bạn</label>
          <textarea
            name="comment_text"
            rows="3"
            class="form-control"
            placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..."></textarea>
        </div>

        <div>
          <button class="btn btn-primary" type="submit">
            Gửi đánh giá
          </button>
        </div>
      </form>
    <?php else: ?>
      <p class="text-muted mb-0">
        <i>Hãy <a href="login.php">đăng nhập</a> để bình luận và đánh giá sản phẩm.</i>
      </p>
    <?php endif; ?>
  </div>
</div>

<!-- DANH SÁCH BÌNH LUẬN -->
<div class="card shadow-sm border-0">
  <div class="card-body">
    <h3 class="h5 mb-3">Bình luận gần đây</h3>

    <?php if (empty($productComments)): ?>
      <p class="mb-0 text-muted">Chưa có bình luận nào cho sản phẩm này.</p>
    <?php else: ?>
      <div class="vstack gap-3">
        <?php foreach ($productComments as $c): ?>
          <div class="border rounded-3 px-3 py-2 bg-light-subtle">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div class="fw-semibold small">
                <?php echo htmlspecialchars($c['username']); ?>
                <?php
                  $r = isset($c['rating']) ? (int)$c['rating'] : 0;
                  if ($r > 0):
                ?>
                  <span class="ms-2 text-warning small">
                    <?php echo renderStars($r); ?>
                  </span>
                <?php endif; ?>
              </div>
              <div class="text-muted small">
                <?php echo htmlspecialchars($c['time']); ?>
              </div>
            </div>

            <p class="mb-1 small">
              <?php echo nl2br(htmlspecialchars($c['text'])); ?>
            </p>

            <?php if (function_exists('isAdmin') && isAdmin()): ?>
              <a class="btn btn-sm btn-outline-danger mt-1"
                 href="product_delete_comment.php?time=<?php echo urlencode($c['time']); ?>&id=<?php echo (int)$product['id']; ?>">
                Xóa
              </a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
