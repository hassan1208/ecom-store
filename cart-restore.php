<?php
// cart-restore.php — restores a saved cart from an abandoned-cart reminder email
// link (identified by a random token, not the order/session id, since the
// customer may be opening this on a different device or after their original
// session expired).
require_once __DIR__ . '/includes/config.php';

$token = sanitize($_GET['token'] ?? '');
$cart_row = $token ? fetch_one("SELECT * FROM abandoned_carts WHERE restore_token=? AND status='active'", 's', $token) : null;

if (!$cart_row) {
    set_flash('error', 'This cart link has expired or was already used.');
    header('Location: ' . url('cart'));
    exit;
}

$items = json_decode($cart_row['cart_data'], true) ?: [];
$restored = 0;
foreach ($items as $item) {
    $res = add_to_cart($item['product_id'], $item['variation_id'] ?: null, $item['qty']);
    if (isset($res['success'])) $restored++;
}

set_flash('success', $restored > 0
    ? 'Welcome back! Your cart has been restored.'
    : 'Sorry, the items in this cart are no longer available.');
header('Location: ' . url('cart'));
exit;
