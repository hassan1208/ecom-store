<?php
// admin/pages/pages.php — static content pages (About, Privacy Policy, Terms, FAQ, etc.)
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Pages';

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    db()->query("DELETE FROM pages WHERE id=" . (int)$_GET['id']);
    set_flash('success', 'Page deleted.');
    header('Location: ' . ADMIN_URL . '/pages/pages.php'); exit;
}
if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $p = fetch_one("SELECT status FROM pages WHERE id=?", 'i', $id);
    if ($p) update_record('pages', ['status' => $p['status'] === 'published' ? 'draft' : 'published'], 'id', $id);
    header('Location: ' . ADMIN_URL . '/pages/pages.php'); exit;
}

$pages = fetch_all("SELECT * FROM pages ORDER BY title ASC");

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex items-center justify-between mb-5">
  <p class="text-slate-500 text-sm"><?= count($pages) ?> page<?= count($pages) !== 1 ? 's' : '' ?></p>
  <a href="page-edit.php" class="btn-primary"><i class="fa-solid fa-plus"></i> New Page</a>
</div>

<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
          <th class="px-5 py-3 font-semibold">Title</th>
          <th class="px-5 py-3 font-semibold">Slug</th>
          <th class="px-5 py-3 font-semibold">Status</th>
          <th class="px-5 py-3 font-semibold text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($pages as $p): ?>
        <tr class="hover:bg-slate-50/60">
          <td class="px-5 py-3 font-semibold text-slate-800"><?= h($p['title']) ?></td>
          <td class="px-5 py-3"><span class="text-brand-dark font-mono text-xs">/<?= h($p['slug']) ?></span></td>
          <td class="px-5 py-3">
            <a href="?action=toggle&id=<?= (int)$p['id'] ?>&csrf_token=<?= csrf_token() ?>" class="<?= $p['status'] === 'published' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($p['status']) ?></a>
          </td>
          <td class="px-5 py-3 text-right">
            <div class="inline-flex items-center gap-2">
              <a href="page-edit.php?id=<?= (int)$p['id'] ?>" class="btn-outline btn-sm">Edit</a>
              <a href="<?= SITE_URL ?>/<?= h($p['slug']) ?>" target="_blank" class="btn-outline btn-sm">View</a>
              <a href="?action=delete&id=<?= (int)$p['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this page?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($pages)): ?>
        <tr><td colspan="4" class="px-5 py-14 text-center text-slate-400">No pages yet — add About Us, Privacy Policy, Terms &amp; Conditions, FAQ, etc.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
