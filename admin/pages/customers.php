<?php
// admin/pages/customers.php — customer list, derived from orders (guest checkout, no accounts)
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Customers';

$search = sanitize($_GET['q'] ?? '');
$where = '1=1'; $types = ''; $params = [];
if ($search !== '') {
    $where = '(o.customer_name LIKE ? OR o.customer_email LIKE ?)';
    $types = 'ss'; $like = "%$search%"; $params = [$like, $like];
}

$customers = fetch_all(
    "SELECT o.customer_email,
            (SELECT customer_name FROM orders o2 WHERE o2.customer_email=o.customer_email ORDER BY created_at DESC LIMIT 1) AS customer_name,
            (SELECT customer_phone FROM orders o2 WHERE o2.customer_email=o.customer_email ORDER BY created_at DESC LIMIT 1) AS customer_phone,
            COUNT(*) AS order_count, SUM(o.total) AS total_spent,
            MAX(o.created_at) AS last_order_at, MIN(o.created_at) AS first_order_at
     FROM orders o WHERE $where
     GROUP BY o.customer_email
     ORDER BY last_order_at DESC",
    $types, ...$params
);

$total_customers = count($customers);
$repeat_customers = count(array_filter($customers, fn($c) => $c['order_count'] > 1));

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Total Customers</div>
    <div class="text-2xl font-extrabold text-slate-800"><?= $total_customers ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Repeat Customers</div>
    <div class="text-2xl font-extrabold text-slate-800"><?= $repeat_customers ?></div>
  </div>
</div>

<form method="GET" class="flex items-center gap-2 mb-5 max-w-lg">
  <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search name or email…" class="f-input">
  <button type="submit" class="btn-outline shrink-0"><i class="fa-solid fa-magnifying-glass"></i></button>
</form>

<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
          <th class="px-5 py-3 font-semibold">Customer</th>
          <th class="px-5 py-3 font-semibold">Orders</th>
          <th class="px-5 py-3 font-semibold">Total Spent</th>
          <th class="px-5 py-3 font-semibold">First Order</th>
          <th class="px-5 py-3 font-semibold">Last Order</th>
          <th class="px-5 py-3 font-semibold text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($customers as $c): ?>
        <tr class="hover:bg-slate-50/60">
          <td class="px-5 py-3">
            <div class="font-semibold text-slate-800 text-[13px]"><?= h($c['customer_name']) ?></div>
            <div class="text-[11px] text-slate-400"><?= h($c['customer_email']) ?></div>
          </td>
          <td class="px-5 py-3">
            <?= (int)$c['order_count'] ?>
            <?php if ($c['order_count'] > 1): ?><span class="badge-active ml-1">Repeat</span><?php endif; ?>
          </td>
          <td class="px-5 py-3 font-semibold text-slate-800"><?= format_price($c['total_spent']) ?></td>
          <td class="px-5 py-3 text-slate-400 text-xs"><?= format_date($c['first_order_at']) ?></td>
          <td class="px-5 py-3 text-slate-400 text-xs"><?= format_date($c['last_order_at']) ?></td>
          <td class="px-5 py-3 text-right">
            <a href="customer-detail.php?email=<?= urlencode($c['customer_email']) ?>" class="btn-outline btn-sm">View</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?>
        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-400">No customers yet — they'll appear here after the first order.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
