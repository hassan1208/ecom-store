<?php
// invoice.php — public invoice view (order number + matching email required)
require_once __DIR__ . '/includes/config.php';

$order_number = sanitize($_GET['order'] ?? '');
$email = sanitize($_GET['email'] ?? '');
$order = ($order_number && $email) ? fetch_one("SELECT * FROM orders WHERE order_number=? AND customer_email=?", 'ss', $order_number, $email) : null;

if (!$order) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;text-align:center;padding:80px 20px;color:#334155">'
       . '<h1>Invoice Not Found</h1><p>Check the order number and email address, or use the link from your confirmation email.</p>'
       . '<a href="' . url('') . '" style="color:' . h(setting('theme_primary_color', '#ff4d2e')) . '">&larr; Back to Home</a></body></html>';
    exit;
}

$items = fetch_all("SELECT * FROM order_items WHERE order_id=?", 'i', $order['id']);
$payment_method_name = fetch_one("SELECT name FROM payment_methods WHERE code=?", 's', $order['payment_method'])['name'] ?? null;

include __DIR__ . '/includes/invoice-template.php';
