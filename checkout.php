<?php
// checkout.php — guest checkout: active payment methods (manual verification via
// screenshot for methods like Remitly), optional coupon code
require_once __DIR__ . '/includes/config.php';

$cart = get_cart();
if (empty($cart)) {
    header('Location: ' . url('cart'));
    exit;
}

$errors = [];
$subtotal = cart_total();
$shipping_cost = (float)setting('shipping_cost', '0');
$payment_methods = get_active_payment_methods();
$customer = current_customer();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$coupon_code = trim($_POST['coupon_code'] ?? '');
$discount = 0;
$applied_coupon = null;
if ($coupon_code !== '') {
    $cr = validate_coupon($coupon_code, $subtotal);
    if (isset($cr['error'])) { $errors[] = $cr['error']; }
    else { $discount = $cr['discount']; $applied_coupon = $cr['coupon']; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['apply_coupon_only'])) {
    $name    = sanitize($_POST['name'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $phone   = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city    = sanitize($_POST['city'] ?? '');
    $country = sanitize($_POST['country'] ?? '');
    $notes   = sanitize($_POST['notes'] ?? '');
    $method_code = sanitize($_POST['payment_method'] ?? '');
    $method = null;
    foreach ($payment_methods as $pm) { if ($pm['code'] === $method_code) $method = $pm; }

    if ($name === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($phone === '') $errors[] = 'Phone number is required.';
    if ($address === '') $errors[] = 'Shipping address is required.';
    if (!$method) $errors[] = 'Please choose a payment method.';

    $screenshot_filename = null;
    if ($method && $method['requires_screenshot']) {
        if (empty($_FILES['payment_screenshot']['name'])) {
            $errors[] = 'Please upload a screenshot of your payment confirmation.';
        } else {
            $res = upload_image($_FILES['payment_screenshot'], 'payment-screenshots', 1200, 1200, 'pay');
            if (isset($res['error'])) $errors[] = $res['error'];
            else $screenshot_filename = $res['filename'];
        }
    }

    // Let a checkout attempt (even if abandoned) capture contact details for follow-up.
    track_abandoned_cart($email ?: null, $name ?: null, $phone ?: null);

    // Re-check stock at the moment of order placement (cart may be stale — another
    // order or an admin stock edit could have happened since items were added).
    if (!$errors) {
        foreach ($cart as $item) {
            if ($item['variation_id']) {
                $row = fetch_one("SELECT pv.stock_quantity, p.track_stock FROM product_variations pv JOIN products p ON p.id=pv.product_id WHERE pv.id=?", 'i', $item['variation_id']);
            } else {
                $row = fetch_one("SELECT stock_quantity, track_stock FROM products WHERE id=?", 'i', $item['product_id']);
            }
            if ($row && $row['track_stock'] && (int)$item['qty'] > (int)$row['stock_quantity']) {
                $errors[] = $item['name'] . ' — only ' . (int)$row['stock_quantity'] . ' left in stock. Please update your cart.';
            }
        }
    }

    if (!$errors) {
        $total = max(0, $subtotal + $shipping_cost - $discount);
        $order_number = generate_order_number();

        $order_id = insert('orders', [
            'order_number'       => $order_number,
            'customer_id'        => $customer['id'] ?? null,
            'customer_name'      => $name,
            'customer_email'     => $email,
            'customer_phone'     => $phone,
            'shipping_address'   => $address,
            'shipping_city'      => $city,
            'shipping_country'   => $country,
            'notes'              => $notes,
            'subtotal'           => $subtotal,
            'shipping_cost'      => $shipping_cost,
            'discount'           => $discount,
            'coupon_code'        => $applied_coupon['code'] ?? null,
            'total'              => $total,
            'payment_method'     => $method['code'],
            'payment_screenshot' => $screenshot_filename,
            'status'             => 'pending',
        ]);

        foreach ($cart as $item) {
            insert('order_items', [
                'order_id'      => $order_id,
                'product_id'    => $item['product_id'],
                'product_name'  => $item['name'],
                'variation_key' => $item['variation_key'],
                'sku'           => $item['sku'],
                'customization' => !empty($item['customization']) ? json_encode($item['customization']) : null,
                'price'         => $item['price'],
                'quantity'      => $item['qty'],
                'subtotal'      => $item['price'] * $item['qty'],
            ]);
            if ($item['variation_id']) {
                db()->query("UPDATE product_variations SET stock_quantity = GREATEST(0, stock_quantity - " . (int)$item['qty'] . ") WHERE id=" . (int)$item['variation_id']);
            } else {
                db()->query("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - " . (int)$item['qty'] . ") WHERE id=" . (int)$item['product_id']);
            }
        }

        if ($applied_coupon) db()->query("UPDATE coupons SET used_count = used_count + 1 WHERE id=" . (int)$applied_coupon['id']);

        db()->query("UPDATE abandoned_carts SET status='recovered' WHERE session_id='" . db()->real_escape_string(session_id()) . "' AND status='active'");
        clear_cart();

        $new_order = fetch_one("SELECT * FROM orders WHERE id=?", 'i', $order_id);
        $new_order_items = fetch_all("SELECT * FROM order_items WHERE order_id=?", 'i', $order_id);
        send_order_confirmation_email($new_order, $new_order_items);
        send_admin_new_order_email($new_order);

        // Lets order-success.php confirm this browser is the one that just placed
        // the order, instead of showing anyone's name/address/phone to whoever
        // requests the URL.
        $_SESSION['recent_order_numbers'][] = $order_number;

        header('Location: ' . url('order-success?order=' . urlencode($order_number)));
        exit;
    }
}

$meta_title = 'Checkout | ' . setting('site_name');
$meta_robots = 'noindex, nofollow';
include __DIR__ . '/includes/site-header.php';
?>
<main class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
  <h1 class="font-display font-bold text-3xl mb-8">Checkout</h1>

  <?php if ($errors): ?>
  <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 p-4 text-sm">
    <ul class="list-disc pl-4 space-y-0.5"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>

  <?php if (empty($payment_methods)): ?>
  <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-700 p-4 text-sm">No payment methods are currently available. Please check back soon.</div>
  <?php else: ?>
  <div class="grid grid-cols-1 lg:grid-cols-5 gap-10">
    <div class="lg:col-span-3">
      <form method="POST" enctype="multipart/form-data" class="space-y-4" id="checkoutForm">
        <?= csrf_field() ?>
        <h2 class="font-display font-semibold text-lg mb-2">Shipping Details</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-sm font-semibold text-slate-700 mb-1.5">Full Name *</label><input type="text" name="name" required value="<?= h($_POST['name'] ?? ($customer ? trim($customer['first_name'] . ' ' . ($customer['last_name'] ?? '')) : '')) ?>" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite"></div>
          <div><label class="block text-sm font-semibold text-slate-700 mb-1.5">Email *</label><input type="email" name="email" required value="<?= h($_POST['email'] ?? ($customer['email'] ?? '')) ?>" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-sm font-semibold text-slate-700 mb-1.5">Phone *</label><input type="text" name="phone" required value="<?= h($_POST['phone'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite"></div>
          <div><label class="block text-sm font-semibold text-slate-700 mb-1.5">City</label><input type="text" name="city" value="<?= h($_POST['city'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite"></div>
        </div>
        <div><label class="block text-sm font-semibold text-slate-700 mb-1.5">Address *</label><input type="text" name="address" required value="<?= h($_POST['address'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite"></div>
        <div><label class="block text-sm font-semibold text-slate-700 mb-1.5">Country</label><input type="text" name="country" value="<?= h($_POST['country'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite"></div>
        <div><label class="block text-sm font-semibold text-slate-700 mb-1.5">Order Notes</label><textarea name="notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite"><?= h($_POST['notes'] ?? '') ?></textarea></div>

        <h2 class="font-display font-semibold text-lg mb-2 pt-2">Payment Method</h2>
        <div class="space-y-3">
          <?php foreach ($payment_methods as $i => $pm): $checked = ($_POST['payment_method'] ?? ($i === 0 ? $pm['code'] : '')) === $pm['code']; ?>
          <div class="rounded-xl border <?= $checked ? 'border-ignite' : 'border-slate-200' ?> p-4">
            <label class="flex items-center gap-2 font-semibold text-sm cursor-pointer">
              <input type="radio" name="payment_method" value="<?= h($pm['code']) ?>" class="accent-ignite payment-radio" data-requires-screenshot="<?= $pm['requires_screenshot'] ? '1' : '0' ?>" <?= $checked ? 'checked' : '' ?> onchange="togglePaymentPanels()">
              <?= h($pm['name']) ?>
            </label>
            <div class="payment-panel mt-3 pl-6 <?= $checked ? '' : 'hidden' ?>" data-method="<?= h($pm['code']) ?>">
              <?php if ($pm['instructions']): ?><p class="text-sm text-slate-500 mb-2"><?= h($pm['instructions']) ?></p><?php endif; ?>
              <?php if ($pm['account_details']): ?>
              <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-sm text-slate-600 whitespace-pre-line mb-2"><?= h($pm['account_details']) ?></div>
              <?php endif; ?>
              <?php if ($pm['youtube_url']): ?>
              <a href="<?= h($pm['youtube_url']) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-semibold text-red-600 hover:text-red-700 mb-3">
                <i class="fa-brands fa-youtube text-lg"></i> Watch payment tutorial
              </a>
              <?php endif; ?>
              <?php if ($pm['requires_screenshot']): ?>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Upload Payment Screenshot *</label>
                <input type="file" name="payment_screenshot" accept="image/*" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-ignite hover:bg-ignite-dark text-white font-display font-semibold uppercase tracking-wide text-sm px-7 py-3.5 rounded-full transition mt-4">
          Place Order <i class="fa-solid fa-check"></i>
        </button>
      </form>
    </div>

    <div class="lg:col-span-2">
      <div class="rounded-2xl border border-slate-200 p-5">
        <h2 class="font-display font-semibold text-lg mb-4">Order Summary</h2>
        <div class="space-y-3 mb-4">
          <?php foreach ($cart as $item): ?>
          <div class="flex justify-between text-sm">
            <span class="text-slate-600"><?= h($item['name']) ?> <?= $item['variation_key'] ? '(' . h($item['variation_key']) . ')' : '' ?> &times;<?= (int)$item['qty'] ?></span>
            <span class="font-medium text-slate-800"><?= format_price($item['price'] * $item['qty']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <form method="POST" class="flex gap-2 mb-4">
          <?= csrf_field() ?>
          <input type="text" name="coupon_code" value="<?= h($coupon_code) ?>" placeholder="Coupon code" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase">
          <button type="submit" name="apply_coupon_only" value="1" class="px-4 rounded-lg border border-slate-300 text-sm font-semibold text-slate-600 hover:bg-slate-50">Apply</button>
        </form>
        <?php if ($applied_coupon): ?><p class="text-xs text-emerald-600 font-medium mb-3"><i class="fa-solid fa-circle-check mr-1"></i>Coupon "<?= h($applied_coupon['code']) ?>" applied.</p><?php endif; ?>

        <div class="border-t border-slate-100 pt-3 space-y-2 text-sm">
          <div class="flex justify-between text-slate-500"><span>Subtotal</span><span><?= format_price($subtotal) ?></span></div>
          <div class="flex justify-between text-slate-500"><span>Shipping</span><span><?= $shipping_cost > 0 ? format_price($shipping_cost) : 'Free' ?></span></div>
          <?php if ($discount > 0): ?><div class="flex justify-between text-emerald-600"><span>Discount</span><span>-<?= format_price($discount) ?></span></div><?php endif; ?>
          <div class="flex justify-between font-bold text-base text-ink pt-2 border-t border-slate-100"><span>Total</span><span><?= format_price(max(0, $subtotal + $shipping_cost - $discount)) ?></span></div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</main>

<script>
function togglePaymentPanels() {
  document.querySelectorAll('.payment-panel').forEach(p => p.classList.add('hidden'));
  const checked = document.querySelector('.payment-radio:checked');
  if (checked) {
    document.querySelector(`.payment-panel[data-method="${checked.value}"]`)?.classList.remove('hidden');
    document.querySelectorAll('[name=payment_method]').forEach(r => r.closest('.rounded-xl').classList.toggle('border-ignite', r.checked));
  }
}
</script>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
