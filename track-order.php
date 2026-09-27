<?php
// track-order.php — public order status lookup (order number + matching email,
// same ownership check as invoice.php) with a visual progress timeline.
require_once __DIR__ . '/includes/config.php';

$order_number = trim(sanitize($_GET['order'] ?? ''));
$order_email  = trim(sanitize($_GET['email'] ?? ''));
$order = ($order_number && $order_email) ? fetch_one("SELECT * FROM orders WHERE order_number=? AND customer_email=?", 'ss', $order_number, $order_email) : null;
$not_found = ($order_number && $order_email && !$order);

$meta_title = 'Track Your Order | ' . setting('site_name');
$meta_robots = 'noindex, follow';
include __DIR__ . '/includes/site-header.php';
$hero_title = 'Track Your Order'; $hero_eyebrow = 'Order status'; $hero_sub = 'Enter your order number and the email you used at checkout.'; $hero_crumbs = ['Track order' => null];
include __DIR__ . '/includes/page-hero.php';
?>
<main id="main" class="container-x max-w-2xl py-12">

  <form method="GET" class="card p-6 grid grid-cols-1 sm:grid-cols-2 gap-3 mb-8">
    <input type="text" name="order" required value="<?= h($order_number) ?>" placeholder="Order number (e.g. ORD-XXXXXXXX-20260101)" class="input sm:col-span-2" aria-label="Order details">
    <input type="email" name="email" required value="<?= h($order_email) ?>" placeholder="Email used at checkout" class="input sm:col-span-2" aria-label="Order details">
    <button type="submit" class="sm:col-span-2 btn-primary btn-shine">
      Track Order
    </button>
  </form>

  <?php if ($not_found): ?>
  <div class="rounded-lg border border-red-200 bg-red-50 text-red-700 p-4 text-sm text-center">We couldn't find an order matching that number and email. Please check and try again.</div>
  <?php endif; ?>

  <?php if ($order): ?>
  <div class="card p-6 sm:p-8">
    <div class="flex items-center justify-between mb-6">
      <div>
        <p class="text-xs text-slate-400">Order Number</p>
        <p class="font-semibold text-slate-800"><?= h($order['order_number']) ?></p>
      </div>
      <div class="text-right">
        <p class="text-xs text-slate-400">Placed On</p>
        <p class="font-semibold text-slate-800"><?= date('M j, Y', strtotime($order['created_at'])) ?></p>
      </div>
    </div>

    <?= render_order_timeline($order['status']) ?>

    <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between">
      <span class="text-sm text-slate-500">Total: <span class="font-bold text-ink"><?= format_price($order['total']) ?></span></span>
      <a href="<?= url('invoice/' . $order['order_number'] . '?email=' . urlencode($order['customer_email'])) ?>" class="text-sm font-semibold text-ignite hover:text-ignite-dark">View Invoice &rarr;</a>
    </div>
  </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
