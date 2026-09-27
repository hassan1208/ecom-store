<?php
// admin/pages/features.php — USP / trust strip shown below the hero banner
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Homepage Features';

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    db()->query("DELETE FROM features WHERE id=" . (int)$_GET['id']);
    set_flash('success', 'Feature removed.');
    header('Location: ' . ADMIN_URL . '/pages/features.php'); exit;
}
if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $f = fetch_one("SELECT status FROM features WHERE id=?", 'i', $id);
    if ($f) update_record('features', ['status' => $f['status'] === 'active' ? 'inactive' : 'active'], 'id', $id);
    header('Location: ' . ADMIN_URL . '/pages/features.php'); exit;
}
if (($_GET['action'] ?? '') === 'move' && isset($_GET['id'], $_GET['dir'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'] === 'up' ? 'ASC' : 'DESC';
    $cmp = $_GET['dir'] === 'up' ? '<' : '>';
    $current = fetch_one("SELECT sort_order FROM features WHERE id=?", 'i', $id);
    $neighbor = fetch_one("SELECT id, sort_order FROM features WHERE sort_order $cmp ? ORDER BY sort_order $dir LIMIT 1", 'i', $current['sort_order']);
    if ($neighbor) {
        update_record('features', ['sort_order' => $neighbor['sort_order']], 'id', $id);
        update_record('features', ['sort_order' => $current['sort_order']], 'id', $neighbor['id']);
    }
    header('Location: ' . ADMIN_URL . '/pages/features.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $fid = (int)($_POST['id'] ?? 0);
    $data = [
        'icon'        => sanitize($_POST['icon'] ?? 'fa-star'),
        'title'       => sanitize($_POST['title'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'status'      => isset($_POST['status']) ? 'active' : 'inactive',
    ];
    if ($data['title'] === '') {
        set_flash('error', 'Title is required.');
    } elseif ($fid) {
        update_record('features', $data, 'id', $fid);
        set_flash('success', 'Feature updated.');
    } else {
        $data['sort_order'] = (int)(fetch_one("SELECT MAX(sort_order) m FROM features")['m'] ?? 0) + 1;
        insert('features', $data);
        set_flash('success', 'Feature added.');
    }
    header('Location: ' . ADMIN_URL . '/pages/features.php'); exit;
}

$edit = isset($_GET['edit']) ? fetch_one("SELECT * FROM features WHERE id=?", 'i', (int)$_GET['edit']) : null;
$features = fetch_all("SELECT * FROM features ORDER BY sort_order ASC, id ASC");
$icon_suggestions = ['fa-truck-fast', 'fa-shield-halved', 'fa-headset', 'fa-rotate-left', 'fa-lock', 'fa-medal', 'fa-tag', 'fa-star'];

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
  <div class="lg:col-span-2">
    <div class="card p-6 sticky top-20">
      <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-slate-800"><?= $edit ? 'Edit Feature' : 'Add Feature' ?></h2>
        <?php if ($edit): ?><a href="<?= ADMIN_URL ?>/pages/features.php" class="text-xs font-semibold text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i> Cancel</a><?php endif; ?>
      </div>
      <form method="POST">
        <?= csrf_field() ?>
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

        <div class="mb-4">
          <label class="f-label">Icon</label>
          <div class="flex items-center gap-2">
            <span class="w-11 h-11 rounded-lg bg-brand-light text-brand-dark flex items-center justify-center"><i id="iconPreview" class="fa-solid <?= h($edit['icon'] ?? 'fa-star') ?>"></i></span>
            <input type="text" name="icon" id="iconInput" class="f-input" value="<?= h($edit['icon'] ?? 'fa-star') ?>" placeholder="fa-truck-fast" oninput="document.getElementById('iconPreview').className='fa-solid '+this.value">
          </div>
          <div class="flex flex-wrap gap-1.5 mt-2">
            <?php foreach ($icon_suggestions as $ic): ?>
            <button type="button" class="text-xs px-2 py-1 rounded-md border border-slate-200 hover:bg-slate-50" onclick="document.getElementById('iconInput').value='<?= $ic ?>';document.getElementById('iconPreview').className='fa-solid <?= $ic ?>'"><i class="fa-solid <?= $ic ?>"></i></button>
            <?php endforeach; ?>
          </div>
          <p class="f-hint">Font Awesome icon name (without the "fa-solid" prefix).</p>
        </div>
        <div class="mb-4">
          <label class="f-label">Title *</label>
          <input type="text" name="title" required class="f-input" value="<?= h($edit['title'] ?? '') ?>" placeholder="e.g. Free Shipping">
        </div>
        <div class="mb-6">
          <label class="f-label">Description</label>
          <input type="text" name="description" class="f-input" value="<?= h($edit['description'] ?? '') ?>" placeholder="e.g. On orders over $100">
        </div>
        <div class="flex items-center gap-2 mb-6">
          <input type="checkbox" name="status" id="fStatus" value="1" class="w-4 h-4 rounded accent-brand" <?= (!$edit || $edit['status'] === 'active') ? 'checked' : '' ?>>
          <label for="fStatus" class="text-sm font-medium text-slate-700">Active</label>
        </div>
        <button type="submit" class="btn-primary w-full"><i class="fa-solid <?= $edit ? 'fa-floppy-disk' : 'fa-plus' ?>"></i> <?= $edit ? 'Update Feature' : 'Add Feature' ?></button>
      </form>
    </div>
  </div>

  <div class="lg:col-span-3">
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-800">Features <span class="text-slate-400 font-normal">(<?= count($features) ?>)</span></h2></div>
      <div class="divide-y divide-slate-100">
        <?php foreach ($features as $i => $f): ?>
        <div class="flex items-center gap-4 px-5 py-3">
          <span class="w-10 h-10 rounded-lg bg-brand-light text-brand-dark flex items-center justify-center shrink-0"><i class="fa-solid <?= h($f['icon']) ?>"></i></span>
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-sm text-slate-800"><?= h($f['title']) ?></div>
            <div class="text-xs text-slate-400 truncate"><?= h($f['description']) ?></div>
          </div>
          <span class="<?= $f['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($f['status']) ?></span>
          <a href="?action=move&id=<?= (int)$f['id'] ?>&dir=up&csrf_token=<?= csrf_token() ?>" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-700 <?= $i === 0 ? 'invisible' : '' ?>"><i class="fa-solid fa-arrow-up text-xs"></i></a>
          <a href="?action=move&id=<?= (int)$f['id'] ?>&dir=down&csrf_token=<?= csrf_token() ?>" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-700 <?= $i === count($features) - 1 ? 'invisible' : '' ?>"><i class="fa-solid fa-arrow-down text-xs"></i></a>
          <a href="?edit=<?= (int)$f['id'] ?>" class="btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
          <a href="?action=toggle&id=<?= (int)$f['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-outline btn-sm"><i class="fa-solid <?= $f['status'] === 'active' ? 'fa-eye-slash' : 'fa-eye' ?>"></i></a>
          <a href="?action=delete&id=<?= (int)$f['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Remove this feature?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
        </div>
        <?php endforeach; ?>
        <?php if (empty($features)): ?><div class="px-5 py-10 text-center text-slate-400">No features yet.</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
