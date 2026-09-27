<?php
// admin/pages/orders.php — order management (status tabs, search, CSV export)
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Orders';

if (($_GET['action'] ?? '') === 'status' && isset($_GET['id'], $_GET['to'])) {
    require_csrf_get();
    $valid = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'failed', 'refunded'];
    if (in_array($_GET['to'], $valid)) update_record('orders', ['status' => $_GET['to']], 'id', (int)$_GET['id']);
    header('Location: ' . ADMIN_URL . '/pages/orders.php' . (!empty($_GET['ref']) ? '?' . $_GET['ref'] : ''));
    exit;
}

$status_filter = $_GET['status'] ?? '';
$search        = sanitize($_GET['q'] ?? '');
$statuses      = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'failed', 'refunded'];

$where = ['1=1']; $types = ''; $params = [];
if (in_array($status_filter, $statuses)) { $where[] = 'status=?'; $types .= 's'; $params[] = $status_filter; }
if ($search !== '') {
    $where[] = '(order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)';
    $types .= 'sss'; $like = "%$search%"; $params[] = $like; $params[] = $like; $params[] = $like;
}
$where_sql = implode(' AND ', $where);
$payment_names = array_column(fetch_all("SELECT code, name FROM payment_methods"), 'name', 'code');

$orders = fetch_all(
    "SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) AS item_count,
            (SELECT SUM(quantity) FROM order_items WHERE order_id=o.id) AS qty_count
     FROM orders o WHERE $where_sql ORDER BY o.created_at DESC",
    $types, ...$params
);

$counts = fetch_all("SELECT status, COUNT(*) c FROM orders GROUP BY status");
$count_map = array_column($counts, 'c', 'status');
$total_count = array_sum($count_map);

$ref = http_build_query(array_filter(['status' => $status_filter, 'q' => $search]));

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex items-start justify-between mb-5">
  <p class="text-slate-500 text-sm"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?></p>
  <a href="orders-export.php?<?= h($ref) ?>" class="btn-outline"><i class="fa-solid fa-download"></i> Export CSV</a>
</div>

<div class="flex items-center gap-2 mb-5 overflow-x-auto pb-1">
  <a href="?<?= $search ? 'q=' . urlencode($search) : '' ?>" class="px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition flex items-center gap-2 <?= $status_filter === '' ? 'bg-brand text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
    All <span class="text-xs opacity-80">(<?= $total_count ?>)</span>
  </a>
  <?php foreach ($statuses as $st): ?>
  <a href="?status=<?= $st ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition flex items-center gap-2 <?= $status_filter === $st ? 'bg-brand text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
    <?= ucfirst($st) ?> <span class="text-xs opacity-80">(<?= (int)($count_map[$st] ?? 0) ?>)</span>
  </a>
  <?php endforeach; ?>
</div>

<form method="GET" class="flex items-center gap-2 mb-5">
  <?php if ($status_filter): ?><input type="hidden" name="status" value="<?= h($status_filter) ?>"><?php endif; ?>
  <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search order #, name, or email..." class="f-input flex-1">
  <button type="submit" class="btn-primary shrink-0">Search</button>
</form>

<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
          <th class="px-5 py-3 font-semibold">Order #</th>
          <th class="px-5 py-3 font-semibold">Customer</th>
          <th class="px-5 py-3 font-semibold">Items</th>
          <th class="px-5 py-3 font-semibold">Total</th>
          <th class="px-5 py-3 font-semibold">Payment</th>
          <th class="px-5 py-3 font-semibold">Status</th>
          <th class="px-5 py-3 font-semibold">Date</th>
          <th class="px-5 py-3 font-semibold text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($orders as $o): ?>
        <tr class="hover:bg-slate-50/60">
          <td class="px-5 py-3 font-semibold text-slate-800"><?= h($o['order_number']) ?></td>
          <td class="px-5 py-3">
            <div class="text-slate-700 font-medium text-[13px]"><?= h($o['customer_name']) ?></div>
            <div class="text-[11px] text-slate-400"><?= h($o['customer_email']) ?></div>
          </td>
          <td class="px-5 py-3 text-slate-500"><?= (int)$o['qty_count'] ?> item<?= $o['qty_count'] != 1 ? 's' : '' ?></td>
          <td class="px-5 py-3 font-semibold text-slate-800"><?= format_price($o['total']) ?></td>
          <td class="px-5 py-3 text-slate-500"><?= h($payment_names[$o['payment_method']] ?? ucfirst($o['payment_method'])) ?></td>
          <td class="px-5 py-3">
            <select onchange="location.href='?action=status&id=<?= (int)$o['id'] ?>&to='+this.value+'&ref=<?= urlencode($ref) ?>&csrf_token=<?= csrf_token() ?>'" class="f-select" style="padding:6px 28px 6px 10px;font-size:12.5px">
              <?php foreach ($statuses as $st): ?>
              <option value="<?= $st ?>" <?= $o['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap"><?= format_date($o['created_at'], 'M d, Y') ?></td>
          <td class="px-5 py-3 text-right">
            <a href="order-detail.php?id=<?= (int)$o['id'] ?>" class="btn-outline btn-sm">View</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
        <tr><td colspan="8" class="px-5 py-14 text-center text-slate-400">No orders yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
