<?php
// File JSON lưu sản phẩm
const PRODUCTS_FILE = __DIR__ . '/data/products.json';

/**
 * Đọc danh sách sản phẩm từ JSON
 * Trả về mảng:
 *  [
 *    [
 *      'id'       => 1,
 *      'name'     => '...',
 *      'price'    => 1000000,
 *      'category' => 'Laptop',
 *      'image'    => 'ten_file.png',
 *      'specs'    => [ ... ],   // mảng thông số
 *      'brand'    => 'Apple',   // thương hiệu
 *      'colors'   => [...],
 *      'images'   => [...],
 *      'stock'    => 10         // tồn kho
 *    ],
 *    ...
 *  ]
 */
function loadProducts(): array
{
    if (!file_exists(PRODUCTS_FILE)) {
        return [];
    }

    $json = file_get_contents(PRODUCTS_FILE);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        return [];
    }

    $products = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }

        // BẮT BUỘC phải có 3 field này, thiếu thì bỏ qua
        if (!isset($row['id'], $row['name'], $row['price'])) {
            continue;
        }

        // Chuẩn hóa kiểu
        $row['id']    = (int)$row['id'];
        $row['name']  = (string)$row['name'];
        $row['price'] = (int)$row['price'];

        // Bổ sung mặc định cho các field còn lại
        $row['category'] = $row['category'] ?? '';
        $row['image']    = $row['image']    ?? '';

        // specs: luôn là MẢNG (ví dụ: ['Màn hình' => '6.1 inch', ...])
        if (!isset($row['specs']) || !is_array($row['specs'])) {
            $row['specs'] = [];
        }

        // Thương hiệu
        $row['brand'] = $row['brand'] ?? '';

        // Colors: luôn là mảng
        if (!isset($row['colors']) || !is_array($row['colors'])) {
            $row['colors'] = [];
        }

        // Images: luôn là mảng
        if (!isset($row['images']) || !is_array($row['images'])) {
            // nếu chưa có, mặc định lấy từ image đơn
            $row['images'] = $row['image'] ? [$row['image']] : [];
        }

        // Tồn kho: luôn là số nguyên
        $row['stock'] = isset($row['stock']) ? (int)$row['stock'] : 0;

        $products[] = $row;
    }

    return $products;
}

/**
 * Ghi toàn bộ mảng sản phẩm ra file JSON
 */
function saveProducts(array $products): void
{
    // đảm bảo không giữ key lẻ, chỉ lưu dạng mảng tuần tự
    $products = array_values($products);

    file_put_contents(
        PRODUCTS_FILE,
        json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

/**
 * Tạo ID mới (lớn hơn ID hiện tại lớn nhất)
 */
function getNextProductId(array $products): int
{
    $max = 0;
    foreach ($products as $p) {
        if (isset($p['id']) && (int)$p['id'] > $max) {
            $max = (int)$p['id'];
        }
    }
    return $max + 1;
}

/**
 * Parse chuỗi specs từ textarea admin thành mảng
 * Input ví dụ:
 *   "Màn hình: 6.1\" OLED\nCPU: Apple A16\nRAM: 6GB"
 * Output:
 *   [
 *     'Màn hình' => '6.1" OLED',
 *     'CPU'      => 'Apple A16',
 *     'RAM'      => '6GB'
 *   ]
 */
function parseSpecsFromTextarea(string $specsRaw): array
{
    $specs = [];

    if ($specsRaw === '') {
        return $specs;
    }

    $lines = preg_split('/\r\n|\r|\n/', $specsRaw);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        // ưu tiên tách theo dấu :
        if (mb_strpos($line, ':') !== false) {
            [$k, $v] = explode(':', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if ($k === '') continue;
            $specs[$k] = $v;
        } else {
            // nếu không có dấu :, lưu nguyên dòng làm key
            $specs[$line] = '';
        }
    }

    return $specs;
}

/**
 * Thêm sản phẩm mới
 *
 * $specsRaw: chuỗi nhiều dòng từ form admin (textarea)
 * $brandOrStock:
 *   - Code cũ: có thể là số (stock) – createProduct(..., 10)
 *   - Code mới: là chuỗi brand – createProduct(..., 'Apple', 10)
 * $stock: tồn kho ban đầu (dùng cho code mới)
 *
 * Ví dụ dùng:
 *  - Cũ, không brand, không stock:
 *      createProduct($name,$price,$cat,$img,$specsRaw);
 *  - Cũ, có stock:
 *      createProduct($name,$price,$cat,$img,$specsRaw, 10);
 *  - Mới, có brand + stock:
 *      createProduct($name,$price,$cat,$img,$specsRaw, 'Apple', 10);
 */
function createProduct(
    string $name,
    int $price,
    string $category = '',
    string $image = '',
    string $specsRaw = '',
    $brandOrStock = '',
    int $stock = 0
): int {
    $list = loadProducts();
    $id   = getNextProductId($list);

    $specs = parseSpecsFromTextarea($specsRaw);

    $brand = '';

    // Giữ tương thích:
    // - Nếu tham số thứ 6 là số và $stock chưa truyền (==0) => đó là stock kiểu cũ
    if ($brandOrStock !== '' &&
        (is_int($brandOrStock) || ctype_digit((string)$brandOrStock)) &&
        $stock === 0) {
        $stock = (int)$brandOrStock;
        $brand = '';
    } else {
        // Kiểu mới: tham số thứ 6 là brand, tham số thứ 7 là stock
        $brand = (string)$brandOrStock;
    }

    $list[] = [
        'id'       => $id,
        'name'     => $name,
        'price'    => $price,
        'category' => $category,
        'image'    => $image,
        'specs'    => $specs,
        'brand'    => $brand,
        'colors'   => [],
        'images'   => $image ? [$image] : [],
        'stock'    => (int)$stock
    ];

    saveProducts($list);
    return $id;
}

/**
 * Xóa sản phẩm theo id
 */
function deleteProduct($id): bool
{
    $id = (int)$id;
    if ($id <= 0) return false;

    $list = loadProducts();
    $new  = [];

    foreach ($list as $p) {
        // giữ lại tất cả sản phẩm KHÔNG trùng id
        if ((int)($p['id'] ?? 0) !== $id) {
            $new[] = $p;
        }
    }

    // nếu số lượng không đổi thì coi như không xóa được
    if (count($new) === count($list)) {
        return false;
    }

    saveProducts($new);
    return true;
}
