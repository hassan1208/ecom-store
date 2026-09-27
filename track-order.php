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
?>
<main class="max-w-2xl mx-auto px-4 sm:px-6 py-16">
  <h1 class="font-display font-bold text-3xl mb-2 text-center">Track Your Order</h1>
  <p class="text-slate-500 text-sm mb-8 text-center">Enter your order number and the email you used at checkout.</p>

  <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-10">
    <input type="text" name="order" required value="<?= h($order_number) ?>" placeholder="Order number (e.g. ORD-XXXXXXXX-20260101)" class="rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite sm:col-span-2">
    <input type="email" name="email" required value="<?= h($order_email) ?>" placeholder="Email used at checkout" class="rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite sm:col-span-2">
    <button type="submit" class="sm:col-span-2 inline-flex items-center justify-center gap-2 bg-ignite hover:bg-ignite-dark text-white font-display font-semibold uppercase tracking-wide text-sm px-7 py-3 rounded-full transition">
      Track Order
    </button>
  </form>

  <?php if ($not_found): ?>
  <div class="rounded-lg border border-red-200 bg-red-50 text-red-700 p-4 text-sm text-center">We couldn't find an order matching that number and email. Please check and try again.</div>
  <?php endif; ?>

  <?php if ($order): ?>
  <div class="rounded-2xl border border-slate-200 p-6">
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
