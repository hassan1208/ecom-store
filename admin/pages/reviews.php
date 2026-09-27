<?php
// admin/pages/reviews.php — moderate customer product reviews
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Reviews';

if (($_GET['action'] ?? '') === 'status' && isset($_GET['id'], $_GET['to'])) {
    require_csrf_get();
    if (in_array($_GET['to'], ['pending', 'approved', 'rejected'])) {
        update_record('reviews', ['status' => $_GET['to']], 'id', (int)$_GET['id']);
    }
    header('Location: ' . ADMIN_URL . '/pages/reviews.php' . (!empty($_GET['ref']) ? '?' . $_GET['ref'] : ''));
    exit;
}
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    db()->query("DELETE FROM reviews WHERE id=" . (int)$_GET['id']);
    set_flash('success', 'Review deleted.');
    header('Location: ' . ADMIN_URL . '/pages/reviews.php'); exit;
}

$status_filter = $_GET['status'] ?? 'pending';
$where = in_array($status_filter, ['pending', 'approved', 'rejected']) ? "r.status='" . db()->real_escape_string($status_filter) . "'" : '1=1';

$reviews = fetch_all("SELECT r.*, p.name AS product_name, p.slug AS product_slug FROM reviews r JOIN products p ON p.id=r.product_id WHERE $where ORDER BY r.created_at DESC");
$counts = fetch_all("SELECT status, COUNT(*) c FROM reviews GROUP BY status");
$count_map = array_column($counts, 'c', 'status');
$total_count = array_sum($count_map);
$ref = 'status=' . urlencode($status_filter);

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex items-center gap-2 mb-5">
  <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label): ?>
  <a href="?status=<?= $key ?>" class="px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2 <?= $status_filter === $key ? 'bg-brand text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
    <?= $label ?> <span class="text-xs opacity-80">(<?= $key === 'all' ? $total_count : (int)($count_map[$key] ?? 0) ?>)</span>
  </a>
  <?php endforeach; ?>
</div>

<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
          <th class="px-5 py-3 font-semibold">Product</th>
          <th class="px-5 py-3 font-semibold">Reviewer</th>
          <th class="px-5 py-3 font-semibold">Rating</th>
          <th class="px-5 py-3 font-semibold">Review</th>
          <th class="px-5 py-3 font-semibold">Date</th>
          <th class="px-5 py-3 font-semibold text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($reviews as $r): ?>
        <tr class="hover:bg-slate-50/60 align-top">
          <td class="px-5 py-3">
            <a href="<?= SITE_URL ?>/product/<?= h($r['product_slug']) ?>" target="_blank" class="font-medium text-slate-700 hover:text-brand text-[13px]"><?= h($r['product_name']) ?></a>
          </td>
          <td class="px-5 py-3">
            <div class="font-medium text-slate-800 text-[13px]"><?= h($r['customer_name']) ?> <?= $r['is_verified'] ? '<i class="fa-solid fa-circle-check text-emerald-500 text-[10px]" title="Verified purchase"></i>' : '' ?></div>
            <div class="text-[11px] text-slate-400"><?= h($r['customer_email']) ?></div>
          </td>
          <td class="px-5 py-3"><?= star_html($r['rating']) ?></td>
          <td class="px-5 py-3 max-w-sm">
            <?php if ($r['title']): ?><div class="font-medium text-slate-700 text-[13px]"><?= h($r['title']) ?></div><?php endif; ?>
            <div class="text-slate-500 text-[13px]"><?= h($r['comment']) ?></div>
          </td>
          <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap"><?= format_date($r['created_at']) ?></td>
          <td class="px-5 py-3 text-right">
            <div class="flex items-center justify-end gap-1.5">
              <?php if ($r['status'] !== 'approved'): ?><a href="?action=status&id=<?= (int)$r['id'] ?>&to=approved&ref=<?= urlencode($ref) ?>&csrf_token=<?= csrf_token() ?>" class="btn-outline btn-sm" title="Approve"><i class="fa-solid fa-check text-emerald-600"></i></a><?php endif; ?>
              <?php if ($r['status'] !== 'rejected'): ?><a href="?action=status&id=<?= (int)$r['id'] ?>&to=rejected&ref=<?= urlencode($ref) ?>&csrf_token=<?= csrf_token() ?>" class="btn-outline btn-sm" title="Reject"><i class="fa-solid fa-xmark text-red-500"></i></a><?php endif; ?>
              <a href="?action=delete&id=<?= (int)$r['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this review?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($reviews)): ?>
        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-400">No reviews here.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
