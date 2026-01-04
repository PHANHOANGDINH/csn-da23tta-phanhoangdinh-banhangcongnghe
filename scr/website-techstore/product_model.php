<?php
/**
 * product_model.php
 * Các hàm làm việc với data/products.json (sản phẩm + kho)
 */

if (!defined('PRODUCTS_FILE')) {
    define('PRODUCTS_FILE', __DIR__ . '/data/products.json');
}

/**
 * Đọc danh sách sản phẩm từ JSON
 * Trả về mảng (array). Nếu lỗi thì trả về mảng rỗng.
 */
function loadProducts() {
    if (!file_exists(PRODUCTS_FILE)) {
        return [];
    }

    $json = file_get_contents(PRODUCTS_FILE);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        return [];
    }

    return $data;
}

/**
 * Ghi danh sách sản phẩm ra JSON
 */
function saveProducts($products) {
    file_put_contents(
        PRODUCTS_FILE,
        json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

/**
 * Tìm vị trí (index) sản phẩm trong mảng theo id
 * - Không tìm thấy -> trả về -1
 */
function findProductIndexById($products, $id) {
    foreach ($products as $i => $p) {
        if (isset($p['id']) && (string)$p['id'] === (string)$id) {
            return $i;
        }
    }
    return -1;
}

/**
 * Lấy 1 sản phẩm theo id
 * - Không tìm thấy -> trả về null
 */
function getProductById($id) {
    $products = loadProducts();
    $idx = findProductIndexById($products, $id);
    if ($idx === -1) {
        return null;
    }
    return $products[$idx];
}

/**
 * Thay đổi tồn kho (stock) của 1 sản phẩm theo delta
 *  - delta < 0 : trừ kho
 *  - delta > 0 : cộng kho
 *  - Không cho tồn kho âm
 * Trả về true nếu thành công, false nếu không đủ hàng hoặc không tìm thấy
 */
function changeProductStock($id, $delta) {
    $products = loadProducts();
    $idx = findProductIndexById($products, $id);
    if ($idx === -1) {
        return false;
    }

    $currentStock = isset($products[$idx]['stock']) ? (int)$products[$idx]['stock'] : 0;
    $newStock = $currentStock + (int)$delta;

    if ($newStock < 0) {
        // Không đủ hàng
        return false;
    }

    $products[$idx]['stock'] = $newStock;
    saveProducts($products);
    return true;
}

/**
 * Set tồn kho trực tiếp
 *  - Nếu stock < 0 thì ép về 0
 */
function setProductStock($id, $stock) {
    $stock = (int)$stock;
    if ($stock < 0) {
        $stock = 0;
    }

    $products = loadProducts();
    $idx = findProductIndexById($products, $id);
    if ($idx === -1) {
        return false;
    }

    $products[$idx]['stock'] = $stock;
    saveProducts($products);
    return true;
}
