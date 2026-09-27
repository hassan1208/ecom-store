<?php
// admin/pages/newsletter.php — newsletter subscriber list
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Newsletter Subscribers';

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    db()->query("DELETE FROM newsletter_subscribers WHERE id=" . (int)$_GET['id']);
    set_flash('success', 'Subscriber removed.');
    header('Location: ' . ADMIN_URL . '/pages/newsletter.php'); exit;
}
if (($_GET['action'] ?? '') === 'export') {
    $subs = fetch_all("SELECT email, status, created_at FROM newsletter_subscribers ORDER BY created_at DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter-subscribers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Status', 'Subscribed At']);
    foreach ($subs as $s) fputcsv($out, [$s['email'], $s['status'], $s['created_at']]);
    fclose($out);
    exit;
}

$search = sanitize($_GET['q'] ?? '');
$where = '1=1'; $types = ''; $params = [];
if ($search !== '') { $where = 'email LIKE ?'; $types = 's'; $params = ["%$search%"]; }

$subscribers = fetch_all("SELECT * FROM newsletter_subscribers WHERE $where ORDER BY created_at DESC", $types, ...$params);
$total = (int)(fetch_one("SELECT COUNT(*) c FROM newsletter_subscribers WHERE status='subscribed'")['c'] ?? 0);

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex items-center justify-between mb-5">
  <div class="card p-5 inline-flex items-center gap-3">
    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-violet-400 to-violet-600 text-white flex items-center justify-center text-lg"><i class="fa-solid fa-envelope"></i></div>
    <div>
      <div class="text-2xl font-extrabold text-slate-800"><?= $total ?></div>
      <div class="text-xs text-slate-500 font-medium">Active Subscribers</div>
    </div>
  </div>
  <a href="?action=export" class="btn-outline"><i class="fa-solid fa-download"></i> Export CSV</a>
</div>

<form method="GET" class="flex items-center gap-2 mb-5 max-w-lg">
  <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search email…" class="f-input">
  <button type="submit" class="btn-outline shrink-0"><i class="fa-solid fa-magnifying-glass"></i></button>
</form>

<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
          <th class="px-5 py-3 font-semibold">Email</th>
          <th class="px-5 py-3 font-semibold">Status</th>
          <th class="px-5 py-3 font-semibold">Subscribed</th>
          <th class="px-5 py-3 font-semibold text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($subscribers as $s): ?>
        <tr class="hover:bg-slate-50/60">
          <td class="px-5 py-3 font-medium text-slate-800"><?= h($s['email']) ?></td>
          <td class="px-5 py-3"><span class="<?= $s['status'] === 'subscribed' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($s['status']) ?></span></td>
          <td class="px-5 py-3 text-slate-400 text-xs"><?= format_date($s['created_at']) ?></td>
          <td class="px-5 py-3 text-right"><a href="?action=delete&id=<?= (int)$s['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Remove this subscriber?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($subscribers)): ?>
        <tr><td colspan="4" class="px-5 py-14 text-center text-slate-400">No subscribers yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
