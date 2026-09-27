<?php
// admin/pages/customer-detail.php — one customer's full order history
require_once __DIR__ . '/../../includes/config.php';
require_admin();

$email = sanitize($_GET['email'] ?? '');
$orders = $email ? fetch_all("SELECT * FROM orders WHERE customer_email=? ORDER BY created_at DESC", 's', $email) : [];
if (!$orders) { set_flash('error', 'Customer not found.'); header('Location: ' . ADMIN_URL . '/pages/customers.php'); exit; }

$latest = $orders[0];
$total_spent = array_sum(array_column($orders, 'total'));
$page_title = $latest['customer_name'];

include __DIR__ . '/../includes/admin-header.php';
?>
<a href="customers.php" class="text-sm font-semibold text-slate-400 hover:text-slate-600 inline-block mb-5"><i class="fa-solid fa-arrow-left mr-1"></i> Back to Customers</a>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Total Orders</div>
    <div class="text-2xl font-extrabold text-slate-800"><?= count($orders) ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Total Spent</div>
    <div class="text-2xl font-extrabold text-slate-800"><?= format_price($total_spent) ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Customer Since</div>
    <div class="text-2xl font-extrabold text-slate-800"><?= format_date(end($orders)['created_at'], 'M Y') ?></div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-800">Order History</h2></div>
    <table class="w-full text-sm">
      <thead><tr class="text-left text-xs uppercase tracking-wider text-slate-400 bg-slate-50">
        <th class="px-5 py-2.5 font-semibold">Order #</th>
        <th class="px-5 py-2.5 font-semibold">Total</th>
        <th class="px-5 py-2.5 font-semibold">Status</th>
        <th class="px-5 py-2.5 font-semibold">Date</th>
        <th class="px-5 py-2.5 font-semibold text-right">Actions</th>
      </tr></thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($orders as $o): ?>
        <tr>
          <td class="px-5 py-3 font-semibold text-slate-800"><?= h($o['order_number']) ?></td>
          <td class="px-5 py-3"><?= format_price($o['total']) ?></td>
          <td class="px-5 py-3"><span class="<?= $o['status'] === 'delivered' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($o['status']) ?></span></td>
          <td class="px-5 py-3 text-slate-400 text-xs"><?= format_date($o['created_at']) ?></td>
          <td class="px-5 py-3 text-right"><a href="order-detail.php?id=<?= (int)$o['id'] ?>" class="btn-outline btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card p-5">
    <h2 class="font-bold text-slate-800 mb-4">Contact Info</h2>
    <div class="space-y-2.5 text-sm">
      <div class="flex gap-2"><i class="fa-solid fa-user text-slate-300 mt-0.5"></i><span class="text-slate-700"><?= h($latest['customer_name']) ?></span></div>
      <div class="flex gap-2"><i class="fa-solid fa-envelope text-slate-300 mt-0.5"></i><a href="mailto:<?= h($email) ?>" class="text-slate-700 hover:text-brand"><?= h($email) ?></a></div>
      <?php if ($latest['customer_phone']): ?><div class="flex gap-2"><i class="fa-solid fa-phone text-slate-300 mt-0.5"></i><a href="tel:<?= h($latest['customer_phone']) ?>" class="text-slate-700 hover:text-brand"><?= h($latest['customer_phone']) ?></a></div><?php endif; ?>
      <?php if ($latest['shipping_address']): ?><div class="flex gap-2"><i class="fa-solid fa-location-dot text-slate-300 mt-0.5"></i><span class="text-slate-700"><?= h($latest['shipping_address']) ?><?= $latest['shipping_city'] ? ', ' . h($latest['shipping_city']) : '' ?></span></div><?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
