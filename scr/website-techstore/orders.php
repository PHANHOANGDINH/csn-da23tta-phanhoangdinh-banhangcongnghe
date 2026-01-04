<?php
// đặt mặc định ngày giờ theo Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// File lưu trữ đơn hàng
const ORDERS_FILE = __DIR__ . '/data/orders.json';

/**
 * Đọc toàn bộ đơn hàng từ file JSON
 * @return array
 */
function loadOrders() {
    if (!file_exists(ORDERS_FILE)) return [];

    $json = file_get_contents(ORDERS_FILE);
    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

/**
 * Ghi danh sách đơn hàng vào file JSON
 * @param array $orders
 * @return void
 */
function saveOrders($orders) {
    file_put_contents(
        ORDERS_FILE,
        json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

/**
 * Hiển thị nhãn trạng thái đơn hàng
 * @param string $status
 * @return string
 */
function getStatusLabel($status) {
    switch ($status) {
        case 'pending':  return 'Chờ duyệt';
        case 'shipping': return 'Đang được giao';
        case 'done':     return 'Hoàn thành';
        case 'cancel':   return 'Đã hủy';
        default:         return $status;
    }
}

/**
 * Hiển thị nhãn trạng thái thanh toán
 * @param string $payment_status
 * @return string
 */
function getPaymentStatusLabel($payment_status) {
    switch ($payment_status) {
        case 'unpaid':          return 'Chưa thanh toán';
        case 'waiting_confirm': return 'Chờ xác nhận';
        case 'paid':            return 'Đã thanh toán';
        case 'cod':             return 'COD';
        default:                return $payment_status;
    }
}

/**
 * Chuẩn hóa payment_method input
 * @param string $pm
 * @return string
 */
function normalizePaymentMethod($pm) {
    $pm = (string)$pm;
    // Hỗ trợ dữ liệu cũ: 'code' => coi như qr_bank
    if ($pm === 'code') return 'qr_bank';
    if ($pm === 'qr_bank' || $pm === 'cod') return $pm;
    return 'cod';
}

/**
 * Tạo đơn hàng mới
 *
 * @param string $username
 * @param array  $items
 * @param int    $total
 * @param string $payment_method 'qr_bank' hoặc 'cod' (dữ liệu cũ 'code' vẫn được map)
 * @param array  $shipping_info
 * @return int ID đơn hàng mới
 */
function createOrder($username, $items, $total, $payment_method = 'cod', $shipping_info = []) {
    $orders = loadOrders();

    // Tự tăng ID
    $id = count($orders) > 0 ? (max(array_column($orders, 'id')) + 1) : 1;

    $pm = normalizePaymentMethod($payment_method);

    // payment_status theo phương thức
    $payment_status = ($pm === 'qr_bank') ? 'unpaid' : 'cod';

    $order = [
        'id'             => (int)$id,
        'username'       => (string)$username,
        'items'          => is_array($items) ? $items : [],
        'total'          => (int)$total,
        'status'         => 'pending', // chờ admin duyệt
        'payment_method' => $pm,
        'payment_status' => $payment_status, // ✅ NEW
        'shipping'       => [
            'fullname' => $shipping_info['fullname'] ?? '',
            'phone'    => $shipping_info['phone'] ?? '',
            'address'  => $shipping_info['address'] ?? ''
        ],
        'created_at'     => date('Y-m-d H:i:s'),
        // 'paid_at' sẽ có khi admin xác nhận thanh toán
        // 'delivered_at' sẽ có khi đơn hoàn thành
    ];

    $orders[] = $order;
    saveOrders($orders);

    return (int)$id;
}

/**
 * Lấy danh sách đơn hàng theo username
 * @param string $username
 * @return array
 */
function getOrdersByUser($username) {
    $orders = loadOrders();
    $result = [];

    foreach ($orders as $o) {
        if (($o['username'] ?? '') === $username) $result[] = $o;
    }

    return $result;
}

/**
 * Tìm 1 đơn hàng theo ID
 * @param int $id
 * @return array|null
 */
function findOrderById($id) {
    $orders = loadOrders();

    foreach ($orders as $o) {
        if ((int)($o['id'] ?? 0) === (int)$id) return $o;
    }

    return null;
}

/**
 * Cập nhật toàn bộ thông tin 1 đơn hàng
 * @param array $updatedOrder
 * @return bool
 */
function updateOrder($updatedOrder) {
    $orders = loadOrders();
    $found  = false;

    foreach ($orders as &$o) {
        if ((int)($o['id'] ?? 0) === (int)($updatedOrder['id'] ?? -1)) {
            // đảm bảo không bị thiếu field mới
            if (!isset($updatedOrder['payment_method'])) {
                $updatedOrder['payment_method'] = normalizePaymentMethod($o['payment_method'] ?? 'cod');
            } else {
                $updatedOrder['payment_method'] = normalizePaymentMethod($updatedOrder['payment_method']);
            }

            if (!isset($updatedOrder['payment_status'])) {
                $pm = $updatedOrder['payment_method'];
                $updatedOrder['payment_status'] = ($pm === 'qr_bank') ? ($o['payment_status'] ?? 'unpaid') : 'cod';
            }

            $o = $updatedOrder;
            $found = true;
            break;
        }
    }
    unset($o);

    if ($found) saveOrders($orders);
    return $found;
}

/**
 * Chỉ cập nhật trạng thái đơn hàng
 * Nếu chuyển sang 'done' -> lưu delivered_at
 * @param int $id
 * @param string $status
 * @return bool
 */
function updateOrderStatus($id, $status) {
    $orders = loadOrders();
    $found  = false;
    $now    = date('Y-m-d H:i:s');

    foreach ($orders as &$o) {
        if ((int)($o['id'] ?? 0) === (int)$id) {
            $o['status'] = $status;

            if ($status === 'done') {
                $o['delivered_at'] = $now;
            }

            $found = true;
            break;
        }
    }
    unset($o);

    if ($found) saveOrders($orders);
    return $found;
}

/**
 * Cập nhật trạng thái thanh toán
 * @param int $id
 * @param string $payment_status unpaid|waiting_confirm|paid|cod
 * @return bool
 */
function updatePaymentStatus($id, $payment_status) {
    $orders = loadOrders();
    $found  = false;
    $now    = date('Y-m-d H:i:s');

    foreach ($orders as &$o) {
        if ((int)($o['id'] ?? 0) === (int)$id) {
            // nếu COD thì ép trạng thái cod
            $pm = normalizePaymentMethod($o['payment_method'] ?? 'cod');
            $o['payment_method'] = $pm;

            if ($pm === 'cod') {
                $o['payment_status'] = 'cod';
            } else {
                $allowed = ['unpaid','waiting_confirm','paid'];
                if (!in_array($payment_status, $allowed, true)) return false;

                $o['payment_status'] = $payment_status;
                if ($payment_status === 'paid') {
                    $o['paid_at'] = $now;
                }
            }

            $found = true;
            break;
        }
    }
    unset($o);

    if ($found) saveOrders($orders);
    return $found;
}

/**
 * Admin duyệt nhanh: xác nhận đã thanh toán + hoàn thành đơn
 * @param int $id
 * @return bool
 */
function confirmPaidAndComplete($id) {
    $orders = loadOrders();
    $found  = false;
    $now    = date('Y-m-d H:i:s');

    foreach ($orders as &$o) {
        if ((int)($o['id'] ?? 0) === (int)$id) {
            $pm = normalizePaymentMethod($o['payment_method'] ?? 'cod');
            $o['payment_method'] = $pm;

            if ($pm === 'qr_bank') {
                $o['payment_status'] = 'paid';
                $o['paid_at'] = $now;
            } else {
                $o['payment_status'] = 'cod';
            }

            $o['status'] = 'done';
            $o['delivered_at'] = $now;

            $found = true;
            break;
        }
    }
    unset($o);

    if ($found) saveOrders($orders);
    return $found;
}
?>
