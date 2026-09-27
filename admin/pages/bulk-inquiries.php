<?php
// admin/pages/bulk-inquiries.php — bulk order leads captured from the storefront
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Bulk Inquiries';

if (($_GET['action'] ?? '') === 'status' && isset($_GET['id'], $_GET['to'])) {
    require_csrf_get();
    if (in_array($_GET['to'], ['new', 'contacted', 'quoted', 'closed'])) {
        update_record('bulk_inquiries', ['status' => $_GET['to']], 'id', (int)$_GET['id']);
    }
    header('Location: ' . ADMIN_URL . '/pages/bulk-inquiries.php'); exit;
}
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    db()->query("DELETE FROM bulk_inquiries WHERE id=" . (int)$_GET['id']);
    set_flash('success', 'Inquiry deleted.');
    header('Location: ' . ADMIN_URL . '/pages/bulk-inquiries.php'); exit;
}

$status_filter = $_GET['status'] ?? '';
$where = '1=1'; $types = ''; $params = [];
if (in_array($status_filter, ['new', 'contacted', 'quoted', 'closed'])) { $where = 'status=?'; $types = 's'; $params = [$status_filter]; }

$inquiries = fetch_all("SELECT * FROM bulk_inquiries WHERE $where ORDER BY created_at DESC", $types, ...$params);
$counts = fetch_all("SELECT status, COUNT(*) c FROM bulk_inquiries GROUP BY status");
$count_map = array_column($counts, 'c', 'status');
$total = array_sum($count_map);

$status_colors = ['new' => 'bg-sky-100 text-sky-700', 'contacted' => 'bg-amber-100 text-amber-700', 'quoted' => 'bg-violet-100 text-violet-700', 'closed' => 'bg-slate-100 text-slate-500'];

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex items-center gap-2 mb-5 overflow-x-auto">
  <?php foreach (['' => 'All', 'new' => 'New', 'contacted' => 'Contacted', 'quoted' => 'Quoted', 'closed' => 'Closed'] as $key => $label): ?>
  <a href="?status=<?= $key ?>" class="px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition <?= $status_filter === $key ? 'bg-brand text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
    <?= $label ?> <span class="opacity-70">(<?= $key === '' ? $total : ($count_map[$key] ?? 0) ?>)</span>
  </a>
  <?php endforeach; ?>
</div>

<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
          <th class="px-5 py-3 font-semibold">Customer</th>
          <th class="px-5 py-3 font-semibold">Product</th>
          <th class="px-5 py-3 font-semibold">Qty Needed</th>
          <th class="px-5 py-3 font-semibold">Message</th>
          <th class="px-5 py-3 font-semibold">Received</th>
          <th class="px-5 py-3 font-semibold">Status</th>
          <th class="px-5 py-3 font-semibold text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($inquiries as $inq): ?>
        <tr class="hover:bg-slate-50/60 align-top">
          <td class="px-5 py-3">
            <div class="font-semibold text-slate-800 text-[13px]"><?= h($inq['customer_name']) ?></div>
            <div class="text-[11px] text-slate-400"><?= h($inq['company'] ?: '') ?></div>
            <div class="text-[11px] text-slate-500 mt-1"><a href="mailto:<?= h($inq['email']) ?>" class="hover:text-brand"><i class="fa-solid fa-envelope mr-1"></i><?= h($inq['email']) ?></a></div>
            <?php if ($inq['phone']): ?><div class="text-[11px] text-slate-500"><a href="tel:<?= h($inq['phone']) ?>" class="hover:text-brand"><i class="fa-solid fa-phone mr-1"></i><?= h($inq['phone']) ?></a></div><?php endif; ?>
          </td>
          <td class="px-5 py-3 text-slate-600"><?= h($inq['product_name'] ?: '—') ?></td>
          <td class="px-5 py-3 font-semibold text-slate-700"><?= $inq['quantity_needed'] ? number_format($inq['quantity_needed']) : '—' ?></td>
          <td class="px-5 py-3 text-slate-500 max-w-xs"><?= h($inq['message'] ?: '—') ?></td>
          <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap"><?= format_date($inq['created_at'], 'M d, Y') ?></td>
          <td class="px-5 py-3">
            <select onchange="location.href='?action=status&id=<?= (int)$inq['id'] ?>&to='+this.value+'&csrf_token=<?= csrf_token() ?>'" class="text-xs font-semibold rounded-full px-2.5 py-1 border-0 <?= $status_colors[$inq['status']] ?>">
              <?php foreach (['new', 'contacted', 'quoted', 'closed'] as $st): ?>
              <option value="<?= $st ?>" <?= $inq['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td class="px-5 py-3 text-right">
            <a href="?action=delete&id=<?= (int)$inq['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this inquiry?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($inquiries)): ?>
        <tr><td colspan="7" class="px-5 py-14 text-center text-slate-400">No bulk inquiries yet. They'll show up here when a customer requests a bulk quote.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
