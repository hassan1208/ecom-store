<?php
// admin/pages/order-invoice.php — admin-side invoice view (any order, no email check needed)
require_once __DIR__ . '/../../includes/config.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$order = $id ? fetch_one("SELECT * FROM orders WHERE id=?", 'i', $id) : null;
if (!$order) { set_flash('error', 'Order not found.'); header('Location: ' . ADMIN_URL . '/pages/orders.php'); exit; }

$items = fetch_all("SELECT * FROM order_items WHERE order_id=?", 'i', $order['id']);
$payment_method_name = fetch_one("SELECT name FROM payment_methods WHERE code=?", 's', $order['payment_method'])['name'] ?? null;

include __DIR__ . '/../../includes/invoice-template.php';
