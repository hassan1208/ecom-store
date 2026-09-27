<?php
// includes/invoice-template.php — shared invoice HTML for both the public
// customer-facing route and the admin order view. Expects $order and $items.
$site_name = setting('site_name', '');
$logo      = setting('site_logo', '');
$phone     = setting('contact_phone', '');
$email     = setting('contact_email', '');
$address   = setting('contact_address', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Invoice <?= h($order['order_number']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body { font-family: 'Inter', Arial, sans-serif; color: #1e293b; margin: 0; background: #f1f5f9; }
  .toolbar { background: #0b0f14; padding: 14px 20px; display: flex; justify-content: center; gap: 10px; }
  .toolbar button, .toolbar a { background: <?= h(setting('theme_primary_color', '#ff4d2e')) ?>; color: #fff; border: none; padding: 10px 20px; border-radius: 999px; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; }
  .sheet { max-width: 800px; margin: 30px auto; background: #fff; padding: 48px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
  .head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 36px; flex-wrap: wrap; gap: 20px; }
  .head img { height: 40px; }
  .head h1 { font-size: 28px; margin: 0 0 6px; letter-spacing: 1px; }
  .meta { text-align: right; font-size: 13px; color: #64748b; }
  .meta strong { color: #1e293b; }
  .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 32px; }
  .label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; font-weight: 600; margin-bottom: 6px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; border-bottom: 2px solid #e2e8f0; padding: 8px 4px; }
  td { padding: 10px 4px; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; }
  .right { text-align: right; }
  .totals { margin-left: auto; width: 260px; }
  .totals div { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13.5px; }
  .totals .grand { border-top: 2px solid #1e293b; margin-top: 6px; padding-top: 10px; font-weight: 700; font-size: 16px; }
  .status { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: #f1f5f9; color: #475569; }
  .foot { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center; }
  @media print {
    .toolbar { display: none; }
    body { background: #fff; }
    .sheet { box-shadow: none; margin: 0; padding: 20px; }
  }
</style>
</head>
<body>
<div class="toolbar">
  <button onclick="window.print()">🖨 Print / Save as PDF</button>
</div>
<div class="sheet">
  <div class="head">
    <div>
      <?php if ($logo): ?><img src="<?= UPLOAD_URL . h($logo) ?>" alt="<?= h($site_name) ?>"><?php else: ?><h1><?= h($site_name) ?></h1><?php endif; ?>
      <?php if ($address): ?><p style="font-size:12.5px;color:#64748b;margin:8px 0 0;max-width:260px"><?= h($address) ?></p><?php endif; ?>
      <?php if ($phone || $email): ?><p style="font-size:12.5px;color:#64748b;margin:4px 0 0"><?= h($phone) ?><?= ($phone && $email) ? ' &middot; ' : '' ?><?= h($email) ?></p><?php endif; ?>
    </div>
    <div class="meta">
      <h2 style="margin:0 0 8px;font-size:22px;color:#1e293b;letter-spacing:1px">INVOICE</h2>
      <div>Order #: <strong><?= h($order['order_number']) ?></strong></div>
      <div>Date: <strong><?= format_date($order['created_at'], 'M d, Y') ?></strong></div>
      <div style="margin-top:8px"><span class="status"><?= h(ucfirst($order['status'])) ?></span></div>
    </div>
  </div>

  <div class="grid2">
    <div>
      <div class="label">Bill To</div>
      <div style="font-weight:600"><?= h($order['customer_name']) ?></div>
      <div style="font-size:13px;color:#64748b;margin-top:2px"><?= h($order['customer_email']) ?></div>
      <?php if ($order['customer_phone']): ?><div style="font-size:13px;color:#64748b"><?= h($order['customer_phone']) ?></div><?php endif; ?>
    </div>
    <div>
      <div class="label">Ship To</div>
      <div style="font-size:13px;color:#334155"><?= h($order['shipping_address']) ?></div>
      <div style="font-size:13px;color:#334155"><?= h(trim(($order['shipping_city'] ?: '') . ', ' . ($order['shipping_country'] ?: ''), ', ')) ?></div>
    </div>
  </div>

  <table>
    <thead><tr><th>Item</th><th class="right">Qty</th><th class="right">Price</th><th class="right">Subtotal</th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td><?= h($item['product_name']) ?><?php if ($item['variation_key']): ?><div style="font-size:11.5px;color:#94a3b8"><?= h($item['variation_key']) ?></div><?php endif; ?></td>
        <td class="right"><?= (int)$item['quantity'] ?></td>
        <td class="right"><?= format_price($item['price']) ?></td>
        <td class="right"><?= format_price($item['subtotal']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="totals">
    <div><span>Subtotal</span><span><?= format_price($order['subtotal']) ?></span></div>
    <div><span>Shipping</span><span><?= format_price($order['shipping_cost']) ?></span></div>
    <?php if ($order['discount'] > 0): ?><div><span>Discount<?= $order['coupon_code'] ? ' (' . h($order['coupon_code']) . ')' : '' ?></span><span>-<?= format_price($order['discount']) ?></span></div><?php endif; ?>
    <div class="grand"><span>Total</span><span><?= format_price($order['total']) ?></span></div>
  </div>

  <div class="foot">
    Payment method: <?= h($payment_method_name ?? ucfirst($order['payment_method'])) ?> &middot; Thank you for your order!
  </div>
</div>
</body>
</html>
