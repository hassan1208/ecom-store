<?php
// order-success.php — thank-you page shown right after placing an order
require_once __DIR__ . '/includes/config.php';

$order_number = sanitize($_GET['order'] ?? '');
$order = $order_number ? fetch_one("SELECT * FROM orders WHERE order_number=?", 's', $order_number) : null;

// Only the browser that just placed this order (or the logged-in account it
// belongs to) may view it here — otherwise this page would leak any customer's
// name/address/phone to anyone who requests the URL with their order number.
$customer = current_customer();
$owns_order = $order && (
    in_array($order_number, $_SESSION['recent_order_numbers'] ?? [], true)
    || ($customer && (int)$order['customer_id'] === (int)$customer['id'])
);

if (!$order || !$owns_order) { header('Location: ' . url('')); exit; }

$items = fetch_all("SELECT * FROM order_items WHERE order_id=?", 'i', $order['id']);

$meta_title = 'Order Confirmed | ' . setting('site_name');
$meta_robots = 'noindex, nofollow';
include __DIR__ . '/includes/site-header.php';
?>
<main id="main" class="max-w-2xl mx-auto px-4 sm:px-6 py-16 text-center">
  <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-5 text-2xl">
    <i class="fa-solid fa-check"></i>
  </div>
  <h1 class="font-display font-bold text-3xl mb-2">Order Placed!</h1>
  <p class="text-slate-500 mb-1">Thank you, <?= h($order['customer_name']) ?>. We'll contact you to confirm delivery.</p>
  <p class="text-sm text-slate-400 mb-8">Order number: <span class="font-semibold text-slate-700"><?= h($order['order_number']) ?></span></p>

  <div class="mb-8 max-w-md mx-auto">
    <?= render_order_timeline($order['status']) ?>
  </div>

  <div class="rounded-2xl border border-slate-200 p-5 text-left mb-8">
    <?php foreach ($items as $item): ?>
    <div class="flex justify-between text-sm py-2 border-b border-slate-100 last:border-0">
      <span class="text-slate-600"><?= h($item['product_name']) ?> <?= $item['variation_key'] ? '(' . h($item['variation_key']) . ')' : '' ?> &times;<?= (int)$item['quantity'] ?></span>
      <span class="font-medium text-slate-800"><?= format_price($item['subtotal']) ?></span>
    </div>
    <?php endforeach; ?>
    <div class="flex justify-between text-sm pt-3 mt-2 border-t border-slate-100"><span class="text-slate-500">Shipping</span><span><?= $order['shipping_cost'] > 0 ? format_price($order['shipping_cost']) : 'Free' ?></span></div>
    <div class="flex justify-between font-bold text-base text-ink pt-2"><span>Total</span><span><?= format_price($order['total']) ?></span></div>
  </div>

  <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
    <a href="<?= url('invoice/' . $order['order_number'] . '?email=' . urlencode($order['customer_email'])) ?>" target="_blank" class="inline-flex items-center gap-2 border-2 border-ink text-ink font-display font-semibold uppercase tracking-wide text-sm px-7 py-3.5 rounded-full hover:bg-ink hover:text-white transition">
      <i class="fa-solid fa-file-invoice"></i> View &amp; Print Invoice
    </a>
    <a href="<?= url('track-order?order=' . urlencode($order['order_number']) . '&email=' . urlencode($order['customer_email'])) ?>" class="inline-flex items-center gap-2 border-2 border-ink text-ink font-display font-semibold uppercase tracking-wide text-sm px-7 py-3.5 rounded-full hover:bg-ink hover:text-white transition">
      <i class="fa-solid fa-truck-fast"></i> Track Order
    </a>
    <a href="<?= url('') ?>" class="inline-flex items-center gap-2 bg-ignite hover:bg-ignite-dark text-white font-display font-semibold uppercase tracking-wide text-sm px-7 py-3.5 rounded-full transition">
      Continue Shopping <i class="fa-solid fa-arrow-right"></i>
    </a>
  </div>
</main>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
