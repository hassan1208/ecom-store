<?php
// admin/pages/abandoned-carts.php — carts started but never turned into an order
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Abandoned Carts';

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    db()->query("DELETE FROM abandoned_carts WHERE id=" . (int)$_GET['id']);
    set_flash('success', 'Removed.');
    header('Location: ' . ADMIN_URL . '/pages/abandoned-carts.php'); exit;
}

$status_filter = $_GET['status'] ?? 'active';
$where = in_array($status_filter, ['active', 'recovered']) ? "status='" . db()->real_escape_string($status_filter) . "'" : '1=1';

$carts = fetch_all("SELECT * FROM abandoned_carts WHERE $where ORDER BY updated_at DESC");
$active_count = (int)(fetch_one("SELECT COUNT(*) c FROM abandoned_carts WHERE status='active'")['c'] ?? 0);
$active_value = (float)(fetch_one("SELECT SUM(cart_total) s FROM abandoned_carts WHERE status='active'")['s'] ?? 0);

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Active Abandoned Carts</div>
    <div class="text-2xl font-extrabold text-slate-800"><?= $active_count ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Potential Recovery Value</div>
    <div class="text-2xl font-extrabold text-slate-800"><?= format_price($active_value) ?></div>
  </div>
</div>

<div class="flex items-center gap-2 mb-5">
  <?php foreach (['active' => 'Active', 'recovered' => 'Recovered', 'all' => 'All'] as $key => $label): ?>
  <a href="?status=<?= $key ?>" class="px-4 py-2 rounded-lg text-sm font-semibold transition <?= $status_filter === $key ? 'bg-brand text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
          <th class="px-5 py-3 font-semibold">Contact</th>
          <th class="px-5 py-3 font-semibold">Cart Contents</th>
          <th class="px-5 py-3 font-semibold">Value</th>
          <th class="px-5 py-3 font-semibold">Status</th>
          <th class="px-5 py-3 font-semibold">Last Activity</th>
          <th class="px-5 py-3 font-semibold text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($carts as $c): ?>
        <?php $items = json_decode($c['cart_data'] ?? '[]', true) ?: []; ?>
        <tr class="hover:bg-slate-50/60 align-top">
          <td class="px-5 py-3">
            <?php if ($c['customer_name'] || $c['email'] || $c['phone']): ?>
            <div class="font-medium text-slate-800 text-[13px]"><?= h($c['customer_name'] ?: 'Guest') ?></div>
            <?php if ($c['email']): ?><div class="text-[11px] text-slate-500"><a href="mailto:<?= h($c['email']) ?>" class="hover:text-brand"><?= h($c['email']) ?></a></div><?php endif; ?>
            <?php if ($c['phone']): ?><div class="text-[11px] text-slate-500"><a href="tel:<?= h($c['phone']) ?>" class="hover:text-brand"><?= h($c['phone']) ?></a></div><?php endif; ?>
            <?php else: ?>
            <span class="text-xs text-slate-400 italic">Anonymous visitor</span>
            <?php endif; ?>
          </td>
          <td class="px-5 py-3 text-slate-500">
            <?= (int)$c['item_count'] ?> item<?= $c['item_count'] != 1 ? 's' : '' ?>
            <div class="text-[11px] text-slate-400 truncate max-w-xs"><?= h(implode(', ', array_column($items, 'name'))) ?></div>
          </td>
          <td class="px-5 py-3 font-semibold text-slate-800"><?= format_price($c['cart_total']) ?></td>
          <td class="px-5 py-3"><span class="<?= $c['status'] === 'active' ? 'badge bg-amber-100 text-amber-700' : 'badge-active' ?>"><?= ucfirst($c['status']) ?></span></td>
          <td class="px-5 py-3 text-slate-400 text-xs"><?= time_ago($c['updated_at']) ?></td>
          <td class="px-5 py-3 text-right">
            <a href="?action=delete&id=<?= (int)$c['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Remove this record?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($carts)): ?>
        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-400">No abandoned carts here.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
