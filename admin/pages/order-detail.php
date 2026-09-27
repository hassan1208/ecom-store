<?php
// admin/pages/order-detail.php — single order view + status update
require_once __DIR__ . '/../../includes/config.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$order = fetch_one("SELECT * FROM orders WHERE id=?", 'i', $id);
if (!$order) { set_flash('error', 'Order not found.'); header('Location: ' . ADMIN_URL . '/pages/orders.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $valid = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'failed', 'refunded'];
    $new_status = $_POST['status'] ?? '';
    if (in_array($new_status, $valid)) {
        if ($new_status !== $order['status']) {
            update_record('orders', ['status' => $new_status], 'id', $id);
            send_order_status_email($order, $new_status);
        }
        set_flash('success', 'Order status updated.');
    }
    header('Location: ' . ADMIN_URL . '/pages/order-detail.php?id=' . $id);
    exit;
}

$page_title = 'Order ' . $order['order_number'];
$items = fetch_all("SELECT * FROM order_items WHERE order_id=?", 'i', $id);
$statuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'failed', 'refunded'];
$payment_method_info = fetch_one("SELECT name FROM payment_methods WHERE code=?", 's', $order['payment_method']);

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex items-center justify-between mb-5">
  <a href="orders.php" class="text-sm font-semibold text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left mr-1"></i> Back to Orders</a>
  <a href="order-invoice.php?id=<?= (int)$order['id'] ?>" target="_blank" class="btn-outline btn-sm"><i class="fa-solid fa-file-invoice"></i> View Invoice</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 space-y-6">
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-bold text-slate-800">Items</h2>
        <span class="badge <?= $order['status'] === 'delivered' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($order['status']) ?></span>
      </div>
      <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase tracking-wider text-slate-400 bg-slate-50">
          <th class="px-5 py-2.5 font-semibold">Product</th>
          <th class="px-5 py-2.5 font-semibold">SKU</th>
          <th class="px-5 py-2.5 font-semibold w-20">Qty</th>
          <th class="px-5 py-2.5 font-semibold w-24">Price</th>
          <th class="px-5 py-2.5 font-semibold w-28 text-right">Subtotal</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($items as $item): ?>
          <tr>
            <td class="px-5 py-3">
              <div class="font-medium text-slate-800"><?= h($item['product_name']) ?></div>
              <?php if ($item['variation_key']): ?><div class="text-xs text-slate-400"><?= h($item['variation_key']) ?></div><?php endif; ?>
              <?php if (!empty($item['customization'])): $cz = json_decode($item['customization'], true); $back_text = trim(($cz['back_name'] ?? '') . ' ' . ($cz['back_number'] ?? '')); ?>
              <div class="mt-2 flex items-start gap-2 p-2 rounded-lg bg-indigo-50 border border-indigo-100">
                <?php if (!empty($cz['preview_path'])): ?>
                <a href="<?= UPLOAD_URL . h($cz['preview_path']) ?>" target="_blank" title="Front">
                  <img src="<?= UPLOAD_URL . h($cz['preview_path']) ?>" class="w-14 h-14 rounded object-cover border border-indigo-200">
                </a>
                <?php endif; ?>
                <?php if (!empty($cz['preview_back_path'])): ?>
                <a href="<?= UPLOAD_URL . h($cz['preview_back_path']) ?>" target="_blank" title="Back">
                  <img src="<?= UPLOAD_URL . h($cz['preview_back_path']) ?>" class="w-14 h-14 rounded object-cover border border-indigo-200">
                </a>
                <?php endif; ?>
                <?php if (!empty($cz['preview_shorts_path'])): ?>
                <a href="<?= UPLOAD_URL . h($cz['preview_shorts_path']) ?>" target="_blank" title="Shorts">
                  <img src="<?= UPLOAD_URL . h($cz['preview_shorts_path']) ?>" class="w-14 h-14 rounded object-cover border border-indigo-200">
                </a>
                <?php endif; ?>
                <div class="text-xs text-indigo-700">
                  <div class="font-bold uppercase tracking-wide text-[10px] mb-0.5"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i>Custom Design</div>
                  <?php if (!empty($cz['color'])): ?><div>Variation Color: <?= h($cz['color']) ?></div><?php endif; ?>
                  <?php if (!empty($cz['garment_color'])): ?><div>Recolored: <?= h($cz['garment_color']) ?></div><?php endif; ?>
                  <?php if (!empty($cz['font'])): ?><div>Font: <?= h($cz['font']) ?></div><?php endif; ?>
                  <?php if (!empty($cz['front_logo_count'])): ?><div>Front Logos: <?= (int)$cz['front_logo_count'] ?></div><?php endif; ?>
                  <?php if (!empty($cz['front_number_enabled']) && !empty($cz['front_number'])): ?><div>Front Number: "<?= h($cz['front_number']) ?>"</div><?php endif; ?>
                  <?php if ($back_text !== ''): ?><div>Back: "<?= h($back_text) ?>" (<?= h($cz['text_color'] ?? '') ?>)</div><?php endif; ?>
                  <?php if (!empty($cz['logo_vectors'])): foreach ($cz['logo_vectors'] as $i => $lv): if (empty($lv['vector_path'])) continue; ?>
                  <div><a href="<?= UPLOAD_URL . h($lv['vector_path']) ?>" target="_blank" class="underline font-semibold">Logo <?= $i + 1 ?> Vector</a> <?= !empty($lv['is_original_vector']) ? '(customer\'s)' : '(auto)' ?></div>
                  <?php endforeach; endif; ?>
                  <?php if (!empty($cz['shorts_logo_count'])): ?><div>Shorts Logos: <?= (int)$cz['shorts_logo_count'] ?></div><?php endif; ?>
                  <?php if (!empty($cz['shorts_logo_vectors'])): foreach ($cz['shorts_logo_vectors'] as $i => $lv): if (empty($lv['vector_path'])) continue; ?>
                  <div><a href="<?= UPLOAD_URL . h($lv['vector_path']) ?>" target="_blank" class="underline font-semibold">Shorts Logo <?= $i + 1 ?> Vector</a> <?= !empty($lv['is_original_vector']) ? '(customer\'s)' : '(auto)' ?></div>
                  <?php endforeach; endif; ?>
                  <?php foreach (customization_detail_lines($cz) as $line): if (in_array($line[0], ['Variation', 'Photo recolor', 'Font', 'Back', 'Front number'], true)) continue; ?>
                  <div class="flex items-center gap-1.5"><?php if (!empty($line[2])): ?><span class="inline-block w-3 h-3 rounded-full border border-indigo-200" style="background:<?= h($line[2]) ?>"></span><?php endif; ?><?= h($line[0]) ?>: <?= h($line[1]) ?></div>
                  <?php endforeach; ?>
                  <?php $cz_imgs = array_diff_key(customization_images($cz), ['Front mockup' => 1, 'Back mockup' => 1]); if ($cz_imgs): ?>
                  <div class="flex flex-wrap gap-2 mt-2">
                    <?php foreach ($cz_imgs as $label => $path): ?>
                    <a href="<?= UPLOAD_URL . h($path) ?>" target="_blank" download class="block text-center" title="<?= h($label) ?> — click to download">
                      <img src="<?= UPLOAD_URL . h($path) ?>" alt="<?= h($label) ?>" class="w-16 h-16 rounded object-contain border border-indigo-200 bg-[repeating-conic-gradient(#eef2ff_0_25%,#fff_0_50%)] [background-size:10px_10px]">
                      <span class="text-[9px] font-semibold"><?= h($label) ?></span>
                    </a>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                  <?php if (!empty($cz['email'])): ?><div>Email: <?= h($cz['email']) ?></div><?php endif; ?>
                  <?php if (!empty($cz['whatsapp'])): ?><div>WhatsApp: <a href="https://wa.me/<?= h(preg_replace('/\D/', '', $cz['whatsapp'])) ?>" target="_blank" class="underline font-semibold">Chat</a></div><?php endif; ?>
                </div>
              </div>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3 text-slate-500"><?= h($item['sku'] ?: '—') ?></td>
            <td class="px-5 py-3"><?= (int)$item['quantity'] ?></td>
            <td class="px-5 py-3"><?= format_price($item['price']) ?></td>
            <td class="px-5 py-3 text-right font-semibold"><?= format_price($item['subtotal']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="px-5 py-4 border-t border-slate-100 flex justify-end">
        <div class="w-56 space-y-1.5 text-sm">
          <div class="flex justify-between text-slate-500"><span>Subtotal</span><span><?= format_price($order['subtotal']) ?></span></div>
          <div class="flex justify-between text-slate-500"><span>Shipping</span><span><?= format_price($order['shipping_cost']) ?></span></div>
          <?php if ($order['discount'] > 0): ?><div class="flex justify-between text-slate-500"><span>Discount <?= $order['coupon_code'] ? '(' . h($order['coupon_code']) . ')' : '' ?></span><span>-<?= format_price($order['discount']) ?></span></div><?php endif; ?>
          <div class="flex justify-between font-bold text-slate-800 text-base pt-1.5 border-t border-slate-100"><span>Total</span><span><?= format_price($order['total']) ?></span></div>
        </div>
      </div>
    </div>

    <?php if ($order['notes']): ?>
    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-2">Customer Notes</h2>
      <p class="text-sm text-slate-600"><?= nl2br(h($order['notes'])) ?></p>
    </div>
    <?php endif; ?>

    <?php if ($order['payment_screenshot']): ?>
    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-3"><i class="fa-solid fa-receipt text-brand mr-1.5"></i>Payment Screenshot</h2>
      <a href="<?= UPLOAD_URL . h($order['payment_screenshot']) ?>" target="_blank" rel="noopener">
        <img src="<?= UPLOAD_URL . h($order['payment_screenshot']) ?>" class="max-w-sm rounded-lg border border-slate-200 hover:opacity-90 transition" alt="Payment proof">
      </a>
      <p class="f-hint mt-2">Click to view full size.</p>
    </div>
    <?php endif; ?>
  </div>

  <div class="space-y-6">
    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-4">Update Status</h2>
      <form method="POST" class="flex gap-2">
        <?= csrf_field() ?>
        <select name="status" class="f-select flex-1">
          <?php foreach ($statuses as $st): ?>
          <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary shrink-0">Save</button>
      </form>
    </div>

    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-4">Customer</h2>
      <div class="space-y-2.5 text-sm">
        <div class="flex gap-2"><i class="fa-solid fa-user text-slate-300 mt-0.5"></i><span class="text-slate-700"><?= h($order['customer_name']) ?></span></div>
        <div class="flex gap-2"><i class="fa-solid fa-envelope text-slate-300 mt-0.5"></i><a href="mailto:<?= h($order['customer_email']) ?>" class="text-slate-700 hover:text-brand"><?= h($order['customer_email']) ?></a></div>
        <?php if ($order['customer_phone']): ?><div class="flex gap-2"><i class="fa-solid fa-phone text-slate-300 mt-0.5"></i><a href="tel:<?= h($order['customer_phone']) ?>" class="text-slate-700 hover:text-brand"><?= h($order['customer_phone']) ?></a></div><?php endif; ?>
      </div>
    </div>

    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-4">Shipping Address</h2>
      <p class="text-sm text-slate-600 leading-relaxed">
        <?= h($order['shipping_address']) ?><br>
        <?= h(trim(($order['shipping_city'] ?: '') . ', ' . ($order['shipping_country'] ?: ''), ', ')) ?>
      </p>
    </div>

    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-2">Order Info</h2>
      <div class="text-sm text-slate-500 space-y-1.5">
        <div>Order #: <span class="font-semibold text-slate-700"><?= h($order['order_number']) ?></span></div>
        <div>Placed: <span class="text-slate-700"><?= format_date($order['created_at'], 'M d, Y h:i A') ?></span></div>
        <div>Payment: <span class="text-slate-700"><?= h($payment_method_info['name'] ?? ucfirst($order['payment_method'])) ?></span></div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
