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
$hero_title = 'Checkout'; $hero_crumbs = ['Cart' => url('cart'), 'Checkout' => null];
include __DIR__ . '/includes/page-hero.php';
$grand_total = max(0, $subtotal + $shipping_cost - $discount);
function co_field($name, $label, $value, $opts = []) {
    $type = $opts['type'] ?? 'text';
    $req = !empty($opts['required']);
    $ac = $opts['autocomplete'] ?? '';
    return '<div class="' . h($opts['class'] ?? '') . '"><label for="co_' . h($name) . '" class="label">' . h($label) . ($req ? ' <span class="text-ignite">*</span>' : '') . '</label>'
         . '<input id="co_' . h($name) . '" type="' . h($type) . '" name="' . h($name) . '" value="' . h($value) . '"' . ($req ? ' required' : '') . ($ac ? ' autocomplete="' . h($ac) . '"' : '') . ' class="input"></div>';
}
?>
<main id="main" class="container-x py-12">
  <ol class="flex items-center gap-3 text-xs font-bold uppercase tracking-wider mb-10" aria-label="Checkout steps">
    <li class="flex items-center gap-2 text-emerald-600"><span class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center"><i class="fa-solid fa-check"></i></span>Cart</li>
    <li class="h-px w-10 bg-slate-300" aria-hidden="true"></li>
    <li class="flex items-center gap-2 text-ink" aria-current="step"><span class="w-7 h-7 rounded-full bg-ink text-white flex items-center justify-center">2</span>Details &amp; payment</li>
    <li class="h-px w-10 bg-slate-300" aria-hidden="true"></li>
    <li class="flex items-center gap-2 text-slate-400"><span class="w-7 h-7 rounded-full border-2 border-slate-300 flex items-center justify-center">3</span>Done</li>
  </ol>

  <?php if ($errors): ?>
  <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 text-red-700 p-5 text-sm" role="alert">
    <ul class="list-disc pl-4 space-y-0.5"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>

  <?php if (empty($payment_methods)): ?>
  <div class="rounded-2xl border border-amber-200 bg-amber-50 text-amber-700 p-5 text-sm">No payment methods are currently available. Please check back soon.</div>
  <?php else: ?>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <div class="lg:col-span-7">
      <form method="POST" enctype="multipart/form-data" class="space-y-6" id="checkoutForm">
        <?= csrf_field() ?>
        <section class="card p-6 sm:p-8">
          <h2 class="font-display font-bold uppercase text-xl mb-6 flex items-center gap-3"><span class="w-8 h-8 rounded-xl bg-ignite/10 text-ignite flex items-center justify-center text-sm"><i class="fa-solid fa-truck-fast"></i></span>Shipping details</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= co_field('name', 'Full name', $_POST['name'] ?? ($customer ? trim($customer['first_name'] . ' ' . ($customer['last_name'] ?? '')) : ''), ['required' => 1, 'autocomplete' => 'name']) ?>
            <?= co_field('email', 'Email', $_POST['email'] ?? ($customer['email'] ?? ''), ['required' => 1, 'type' => 'email', 'autocomplete' => 'email']) ?>
            <?= co_field('phone', 'Phone / WhatsApp', $_POST['phone'] ?? '', ['required' => 1, 'type' => 'tel', 'autocomplete' => 'tel']) ?>
            <?= co_field('city', 'City', $_POST['city'] ?? '', ['autocomplete' => 'address-level2']) ?>
            <?= co_field('address', 'Address', $_POST['address'] ?? '', ['required' => 1, 'autocomplete' => 'street-address', 'class' => 'sm:col-span-2']) ?>
            <?= co_field('country', 'Country', $_POST['country'] ?? '', ['autocomplete' => 'country-name']) ?>
            <div class="sm:col-span-2"><label for="co_notes" class="label">Order notes</label><textarea id="co_notes" name="notes" rows="2" class="input" placeholder="Delivery instructions, deadline for your event…"><?= h($_POST['notes'] ?? '') ?></textarea></div>
          </div>
        </section>

        <section class="card p-6 sm:p-8">
          <h2 class="font-display font-bold uppercase text-xl mb-6 flex items-center gap-3"><span class="w-8 h-8 rounded-xl bg-ignite/10 text-ignite flex items-center justify-center text-sm"><i class="fa-solid fa-credit-card"></i></span>Payment method</h2>
          <div class="space-y-3">
            <?php foreach ($payment_methods as $i => $pm): $checked = ($_POST['payment_method'] ?? ($i === 0 ? $pm['code'] : '')) === $pm['code']; ?>
            <div class="payment-option rounded-2xl border-2 <?= $checked ? 'border-ignite bg-ignite/[0.03]' : 'border-slate-200' ?> p-4 transition">
              <label class="flex items-center gap-3 font-semibold cursor-pointer">
                <input type="radio" name="payment_method" value="<?= h($pm['code']) ?>" class="w-4 h-4 accent-ignite payment-radio" data-requires-screenshot="<?= $pm['requires_screenshot'] ? '1' : '0' ?>" <?= $checked ? 'checked' : '' ?> onchange="togglePaymentPanels()">
                <?= h($pm['name']) ?>
              </label>
              <div class="payment-panel mt-3 pl-7 <?= $checked ? '' : 'hidden' ?>" data-method="<?= h($pm['code']) ?>">
                <?php if ($pm['instructions']): ?><p class="text-sm text-slate-500 mb-2"><?= h($pm['instructions']) ?></p><?php endif; ?>
                <?php if ($pm['account_details']): ?><div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-sm text-slate-600 whitespace-pre-line mb-2 font-mono"><?= h($pm['account_details']) ?></div><?php endif; ?>
                <?php if ($pm['youtube_url']): ?>
                <a href="<?= h($pm['youtube_url']) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-semibold text-red-600 hover:text-red-700 mb-3"><i class="fa-brands fa-youtube text-lg"></i> Watch payment tutorial</a>
                <?php endif; ?>
                <?php if ($pm['requires_screenshot']): ?>
                <label class="studio-dropzone cursor-pointer mt-1"><i class="fa-solid fa-receipt text-xl text-ignite"></i><span class="text-sm font-semibold">Upload payment screenshot *</span><input type="file" name="payment_screenshot" accept="image/*" class="text-xs"></label>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </section>

        <button type="submit" class="btn-primary btn-shine w-full !py-4 text-base">Place order · <?= format_price($grand_total) ?> <i class="fa-solid fa-lock text-xs"></i></button>
        <p class="text-xs text-center text-slate-400"><i class="fa-solid fa-shield-halved mr-1"></i>Your details are only used to process and ship this order.</p>
      </form>
    </div>

    <aside class="lg:col-span-5">
      <div class="card p-6 lg:sticky lg:top-40">
        <h2 class="font-display font-bold uppercase text-xl mb-5">Order summary</h2>
        <ul class="space-y-4 mb-6 max-h-[340px] overflow-y-auto pr-1">
          <?php foreach ($cart as $item): $cz = $item['customization'] ?? null; $thumb = $cz ? (($cz['render_front_path'] ?? '') ?: ($cz['preview_path'] ?? '')) : ''; $thumb = $thumb ?: $item['image']; ?>
          <li class="flex items-center gap-3">
            <div class="relative w-14 h-14 rounded-xl bg-slate-100 overflow-hidden shrink-0">
              <?php if ($thumb): ?><img src="<?= UPLOAD_URL . h($thumb) ?>" alt="" class="w-full h-full object-cover"><?php endif; ?>
              <span class="absolute -top-0.5 -right-0.5 min-w-[20px] h-5 px-1 rounded-full bg-ink text-white text-[10px] font-bold flex items-center justify-center"><?= (int)$item['qty'] ?></span>
            </div>
            <div class="flex-1 min-w-0 text-sm">
              <p class="font-semibold truncate"><?= h($item['name']) ?></p>
              <p class="text-xs text-slate-500"><?= $item['variation_key'] ? h($item['variation_key']) : '' ?><?= $cz ? ($item['variation_key'] ? ' · ' : '') . 'Custom design' : '' ?></p>
            </div>
            <span class="text-sm font-semibold"><?= format_price($item['price'] * $item['qty']) ?></span>
          </li>
          <?php endforeach; ?>
        </ul>

        <form method="POST" class="flex gap-2 mb-2">
          <?= csrf_field() ?>
          <label for="couponCode" class="sr-only">Coupon code</label>
          <input id="couponCode" type="text" name="coupon_code" value="<?= h($coupon_code) ?>" placeholder="Coupon code" class="input !py-2.5 uppercase flex-1">
          <button type="submit" name="apply_coupon_only" value="1" class="btn-outline btn-sm">Apply</button>
        </form>
        <?php if ($applied_coupon): ?><p class="text-xs text-emerald-600 font-semibold mb-2"><i class="fa-solid fa-circle-check mr-1"></i>Coupon "<?= h($applied_coupon['code']) ?>" applied.</p><?php endif; ?>

        <dl class="border-t border-slate-100 mt-4 pt-4 space-y-2 text-sm">
          <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-semibold"><?= format_price($subtotal) ?></dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Shipping</dt><dd class="font-semibold"><?= $shipping_cost > 0 ? format_price($shipping_cost) : 'Free' ?></dd></div>
          <?php if ($discount > 0): ?><div class="flex justify-between text-emerald-600"><dt>Discount</dt><dd class="font-semibold">-<?= format_price($discount) ?></dd></div><?php endif; ?>
        </dl>
        <div class="flex justify-between items-baseline border-t border-slate-100 mt-4 pt-4">
          <span class="font-semibold">Total</span><span class="font-display font-bold text-3xl"><?= format_price($grand_total) ?></span>
        </div>
      </div>
    </aside>
  </div>
  <?php endif; ?>
</main>

<script>
function togglePaymentPanels() {
  const checked = document.querySelector('.payment-radio:checked');
  document.querySelectorAll('.payment-option').forEach(box => {
    const on = box.querySelector('.payment-radio') === checked;
    box.querySelector('.payment-panel').classList.toggle('hidden', !on);
    // Only the selected method's screenshot input is submitted (several methods may have one).
    box.querySelectorAll('.payment-panel input').forEach(i => { i.disabled = !on; });
    box.classList.toggle('border-ignite', on); box.classList.toggle('bg-ignite/[0.03]', on); box.classList.toggle('border-slate-200', !on);
  });
}
togglePaymentPanels();
</script>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
