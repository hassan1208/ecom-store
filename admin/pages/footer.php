<?php
// admin/pages/footer.php — footer columns + links
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Footer Menu';

// Columns
if (($_POST['form'] ?? '') === 'column') {
    require_csrf();
    $title = sanitize($_POST['col_title'] ?? '');
    if ($title !== '') {
        $order = (int)(fetch_one("SELECT MAX(sort_order) m FROM footer_columns")['m'] ?? 0) + 1;
        insert('footer_columns', ['title' => $title, 'sort_order' => $order, 'status' => 'active']);
        set_flash('success', 'Footer column added.');
    }
    header('Location: ' . ADMIN_URL . '/pages/footer.php'); exit;
}
if (($_GET['action'] ?? '') === 'delete_column' && isset($_GET['id'])) {
    require_csrf_get();
    db()->query("DELETE FROM footer_columns WHERE id=" . (int)$_GET['id']);
    set_flash('success', 'Column and its links removed.');
    header('Location: ' . ADMIN_URL . '/pages/footer.php'); exit;
}

// Links
if (($_POST['form'] ?? '') === 'link') {
    require_csrf();
    $col_id = (int)($_POST['column_id'] ?? 0);
    $label = sanitize($_POST['label'] ?? '');
    $url_val = sanitize($_POST['url'] ?? '');
    if ($col_id && $label && $url_val) {
        $order = (int)(fetch_one("SELECT MAX(sort_order) m FROM footer_links WHERE column_id=?", 'i', $col_id)['m'] ?? 0) + 1;
        insert('footer_links', ['column_id' => $col_id, 'label' => $label, 'url' => $url_val, 'sort_order' => $order, 'status' => 'active']);
        set_flash('success', 'Link added.');
    } else {
        set_flash('error', 'Column, label and URL are all required.');
    }
    header('Location: ' . ADMIN_URL . '/pages/footer.php'); exit;
}
if (($_GET['action'] ?? '') === 'delete_link' && isset($_GET['id'])) {
    require_csrf_get();
    db()->query("DELETE FROM footer_links WHERE id=" . (int)$_GET['id']);
    header('Location: ' . ADMIN_URL . '/pages/footer.php'); exit;
}

$columns = get_footer_columns_full();

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
  <div class="card p-6">
    <h2 class="font-bold text-slate-800 mb-4">Add Footer Column</h2>
    <form method="POST" class="flex gap-2">
      <?= csrf_field() ?>
      <input type="hidden" name="form" value="column">
      <input type="text" name="col_title" required class="f-input" placeholder="e.g. Quick Links">
      <button type="submit" class="btn-primary shrink-0"><i class="fa-solid fa-plus"></i></button>
    </form>
  </div>
  <div class="card p-6 lg:col-span-2">
    <h2 class="font-bold text-slate-800 mb-4">Add Link</h2>
    <form method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-2">
      <?= csrf_field() ?>
      <input type="hidden" name="form" value="link">
      <select name="column_id" required class="f-select sm:col-span-1">
        <option value="">Column…</option>
        <?php foreach ($columns as $c): ?><option value="<?= (int)$c['id'] ?>"><?= h($c['title']) ?></option><?php endforeach; ?>
      </select>
      <input type="text" name="label" required class="f-input sm:col-span-1" placeholder="Label">
      <input type="text" name="url" required class="f-input sm:col-span-1" placeholder="/about or https://...">
      <button type="submit" class="btn-primary sm:col-span-1"><i class="fa-solid fa-plus"></i> Add Link</button>
    </form>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
  <?php foreach ($columns as $col): ?>
  <div class="card p-5">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-bold text-slate-800"><?= h($col['title']) ?></h3>
      <a href="?action=delete_column&id=<?= (int)$col['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this column and all its links?" class="text-slate-300 hover:text-red-500"><i class="fa-solid fa-trash text-sm"></i></a>
    </div>
    <div class="space-y-2">
      <?php foreach ($col['links'] as $link): ?>
      <div class="flex items-center justify-between gap-2 text-sm">
        <div class="truncate"><span class="text-slate-700 font-medium"><?= h($link['label']) ?></span> <span class="text-slate-400">— <?= h($link['url']) ?></span></div>
        <a href="?action=delete_link&id=<?= (int)$link['id'] ?>&csrf_token=<?= csrf_token() ?>" class="text-slate-300 hover:text-red-500 shrink-0"><i class="fa-solid fa-xmark"></i></a>
      </div>
      <?php endforeach; ?>
      <?php if (empty($col['links'])): ?><p class="text-xs text-slate-400">No links yet.</p><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($columns)): ?><p class="text-slate-400 col-span-full text-center py-10">No footer columns yet — add one above.</p><?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
