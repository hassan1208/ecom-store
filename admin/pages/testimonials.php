<?php
// admin/pages/testimonials.php — customer testimonials shown on the homepage
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Testimonials';

if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $t = fetch_one("SELECT status FROM testimonials WHERE id=?", 'i', $id);
    if ($t) update_record('testimonials', ['status' => $t['status'] === 'active' ? 'inactive' : 'active'], 'id', $id);
    header('Location: ' . ADMIN_URL . '/pages/testimonials.php'); exit;
}
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $t = fetch_one("SELECT photo FROM testimonials WHERE id=?", 'i', $id);
    db()->query("DELETE FROM testimonials WHERE id=" . $id);
    if ($t && $t['photo'] && file_exists(UPLOAD_PATH . $t['photo'])) @unlink(UPLOAD_PATH . $t['photo']);
    set_flash('success', 'Testimonial deleted.');
    header('Location: ' . ADMIN_URL . '/pages/testimonials.php'); exit;
}
if (($_GET['action'] ?? '') === 'move' && isset($_GET['id'], $_GET['dir'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'] === 'up' ? 'ASC' : 'DESC';
    $cmp = $_GET['dir'] === 'up' ? '<' : '>';
    $current = fetch_one("SELECT sort_order FROM testimonials WHERE id=?", 'i', $id);
    $neighbor = fetch_one("SELECT id, sort_order FROM testimonials WHERE sort_order $cmp ? ORDER BY sort_order $dir LIMIT 1", 'i', $current['sort_order']);
    if ($neighbor) {
        update_record('testimonials', ['sort_order' => $neighbor['sort_order']], 'id', $id);
        update_record('testimonials', ['sort_order' => $current['sort_order']], 'id', $neighbor['id']);
    }
    header('Location: ' . ADMIN_URL . '/pages/testimonials.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $tid = (int)($_POST['id'] ?? 0);
    $data = [
        'name'   => sanitize($_POST['name'] ?? ''),
        'role'   => sanitize($_POST['role'] ?? ''),
        'quote'  => sanitize($_POST['quote'] ?? ''),
        'rating' => max(1, min(5, (int)($_POST['rating'] ?? 5))),
        'status' => isset($_POST['status']) ? 'active' : 'inactive',
    ];
    if ($data['name'] === '' || $data['quote'] === '') {
        set_flash('error', 'Name and quote are required.');
        header('Location: ' . ADMIN_URL . '/pages/testimonials.php'); exit;
    }
    if (!empty($_FILES['photo']['name'])) {
        $res = upload_image($_FILES['photo'], 'testimonials', 200, 200, 'testi');
        if (!isset($res['error'])) $data['photo'] = $res['filename'];
    }
    if ($tid) { update_record('testimonials', $data, 'id', $tid); set_flash('success', 'Testimonial updated.'); }
    else {
        $data['sort_order'] = (int)(fetch_one("SELECT MAX(sort_order) m FROM testimonials")['m'] ?? 0) + 1;
        insert('testimonials', $data);
        set_flash('success', 'Testimonial added.');
    }
    header('Location: ' . ADMIN_URL . '/pages/testimonials.php');
    exit;
}

$edit = isset($_GET['edit']) ? fetch_one("SELECT * FROM testimonials WHERE id=?", 'i', (int)$_GET['edit']) : null;
$testimonials = fetch_all("SELECT * FROM testimonials ORDER BY sort_order ASC, id ASC");

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
  <div class="lg:col-span-2">
    <div class="card p-6 sticky top-20">
      <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-slate-800"><?= $edit ? 'Edit Testimonial' : 'Add Testimonial' ?></h2>
        <?php if ($edit): ?><a href="<?= ADMIN_URL ?>/pages/testimonials.php" class="text-xs font-semibold text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i> Cancel</a><?php endif; ?>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

        <div class="mb-4">
          <label class="f-label">Photo <span class="text-slate-400 font-normal">(optional)</span></label>
          <?php if (!empty($edit['photo'])): ?><img src="<?= UPLOAD_URL . h($edit['photo']) ?>" class="w-14 h-14 rounded-full object-cover mb-2"><?php endif; ?>
          <input type="file" name="photo" accept="image/*" class="f-input">
        </div>
        <div class="mb-4">
          <label class="f-label">Name *</label>
          <input type="text" name="name" required class="f-input" value="<?= h($edit['name'] ?? '') ?>">
        </div>
        <div class="mb-4">
          <label class="f-label">Role / Company</label>
          <input type="text" name="role" class="f-input" value="<?= h($edit['role'] ?? '') ?>" placeholder="e.g. Gym Owner">
        </div>
        <div class="mb-4">
          <label class="f-label">Rating</label>
          <select name="rating" class="f-select">
            <?php for ($i = 5; $i >= 1; $i--): ?>
            <option value="<?= $i ?>" <?= (($edit['rating'] ?? 5) == $i) ? 'selected' : '' ?>><?= str_repeat('★', $i) ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="mb-6">
          <label class="f-label">Quote *</label>
          <textarea name="quote" rows="3" required class="f-textarea"><?= h($edit['quote'] ?? '') ?></textarea>
        </div>
        <div class="flex items-center gap-2 mb-6">
          <input type="checkbox" name="status" id="tStatus" value="1" class="w-4 h-4 rounded accent-brand" <?= (!$edit || $edit['status'] === 'active') ? 'checked' : '' ?>>
          <label for="tStatus" class="text-sm font-medium text-slate-700">Active</label>
        </div>
        <button type="submit" class="btn-primary w-full"><i class="fa-solid <?= $edit ? 'fa-floppy-disk' : 'fa-plus' ?>"></i> <?= $edit ? 'Update' : 'Add Testimonial' ?></button>
      </form>
    </div>
  </div>

  <div class="lg:col-span-3">
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-800">All Testimonials <span class="text-slate-400 font-normal">(<?= count($testimonials) ?>)</span></h2></div>
      <div class="divide-y divide-slate-100">
        <?php foreach ($testimonials as $i => $t): ?>
        <div class="flex items-center gap-4 px-5 py-3">
          <?php if ($t['photo']): ?><img src="<?= UPLOAD_URL . h($t['photo']) ?>" class="w-10 h-10 rounded-full object-cover"><?php else: ?><div class="w-10 h-10 rounded-full bg-slate-100 text-slate-300 flex items-center justify-center"><i class="fa-solid fa-user"></i></div><?php endif; ?>
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-sm text-slate-800 truncate"><?= h($t['name']) ?> <span class="text-xs text-amber-400"><?= str_repeat('★', $t['rating']) ?></span></div>
            <div class="text-xs text-slate-400 truncate"><?= h($t['quote']) ?></div>
          </div>
          <span class="<?= $t['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($t['status']) ?></span>
          <a href="?action=move&id=<?= (int)$t['id'] ?>&dir=up&csrf_token=<?= csrf_token() ?>" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-700 <?= $i === 0 ? 'invisible' : '' ?>"><i class="fa-solid fa-arrow-up text-xs"></i></a>
          <a href="?action=move&id=<?= (int)$t['id'] ?>&dir=down&csrf_token=<?= csrf_token() ?>" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-700 <?= $i === count($testimonials) - 1 ? 'invisible' : '' ?>"><i class="fa-solid fa-arrow-down text-xs"></i></a>
          <a href="?edit=<?= (int)$t['id'] ?>" class="btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
          <a href="?action=toggle&id=<?= (int)$t['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-outline btn-sm"><i class="fa-solid <?= $t['status'] === 'active' ? 'fa-eye-slash' : 'fa-eye' ?>"></i></a>
          <a href="?action=delete&id=<?= (int)$t['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this testimonial?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
        </div>
        <?php endforeach; ?>
        <?php if (empty($testimonials)): ?><div class="px-5 py-10 text-center text-slate-400">No testimonials yet.</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
