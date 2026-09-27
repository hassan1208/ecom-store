<?php
// wishlist-toggle.php — add/remove a product from the logged-in customer's
// wishlist, then bounce back to wherever the heart button was clicked from.
require_once __DIR__ . '/includes/config.php';

$redirect = $_POST['redirect'] ?? url('');
// Only allow redirecting back within this site (never an attacker-supplied
// absolute URL from a forged form), since $redirect is taken from POST data.
if (parse_url($redirect, PHP_URL_HOST) !== parse_url(SITE_URL, PHP_URL_HOST)) {
    $redirect = url('');
}

require_customer(); // sends guests to /login?redirect=... instead

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $product_id = (int)($_POST['product_id'] ?? 0);
    $customer_id = current_user_id();

    $existing = fetch_one("SELECT id FROM wishlists WHERE customer_id=? AND product_id=?", 'ii', $customer_id, $product_id);
    if ($existing) {
        db()->query("DELETE FROM wishlists WHERE id=" . (int)$existing['id']);
    } elseif (fetch_one("SELECT id FROM products WHERE id=? AND status='active'", 'i', $product_id)) {
        insert('wishlists', ['customer_id' => $customer_id, 'product_id' => $product_id]);
    }
}

header('Location: ' . $redirect);
exit;
