<?php
require_once 'auth.php';
require_once 'orders.php';

requireLogin();
requireAdmin();

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';

if ($id <= 0) {
    die('ID không hợp lệ');
}

$order = findOrderById($id);
if (!$order) {
    die('Không tìm thấy đơn');
}

switch ($action) {
    case 'approve':
        // Chờ duyệt -> Đang được giao
        if ($order['status'] === 'pending') {
            updateOrderStatus($id, 'shipping');
        }
        break;

    case 'done':
        // Đang được giao -> Hoàn thành
        if ($order['status'] === 'shipping') {
            updateOrderStatus($id, 'done');
        }
        break;

    case 'cancel':
        // Cho phép hủy khi còn pending hoặc đang giao
        if (in_array($order['status'], ['pending', 'shipping'], true)) {
            updateOrderStatus($id, 'cancel');
        }
        break;

    default:
        // action lạ thì bỏ qua, redirect về
        break;
}

header('Location: admin_orders.php');
exit;
