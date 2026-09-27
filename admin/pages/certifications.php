<?php
// admin/pages/certifications.php — chamber membership / certificates shown
// on the homepage as a trust section, and (once added) in the catalog PDF.
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Certifications';

if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $c = fetch_one("SELECT status FROM certifications WHERE id=?", 'i', $id);
    if ($c) update_record('certifications', ['status' => $c['status'] === 'active' ? 'inactive' : 'active'], 'id', $id);
    header('Location: ' . ADMIN_URL . '/pages/certifications.php'); exit;
}
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $c = fetch_one("SELECT image FROM certifications WHERE id=?", 'i', $id);
    db()->query("DELETE FROM certifications WHERE id=" . $id);
    if ($c && $c['image'] && file_exists(UPLOAD_PATH . $c['image'])) @unlink(UPLOAD_PATH . $c['image']);
    set_flash('success', 'Certification deleted.');
    header('Location: ' . ADMIN_URL . '/pages/certifications.php'); exit;
}
if (($_GET['action'] ?? '') === 'move' && isset($_GET['id'], $_GET['dir'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'] === 'up' ? 'ASC' : 'DESC';
    $cmp = $_GET['dir'] === 'up' ? '<' : '>';
    $current = fetch_one("SELECT sort_order FROM certifications WHERE id=?", 'i', $id);
    $neighbor = fetch_one("SELECT id, sort_order FROM certifications WHERE sort_order $cmp ? ORDER BY sort_order $dir LIMIT 1", 'i', $current['sort_order']);
    if ($neighbor) {
        update_record('certifications', ['sort_order' => $neighbor['sort_order']], 'id', $id);
        update_record('certifications', ['sort_order' => $current['sort_order']], 'id', $neighbor['id']);
    }
    header('Location: ' . ADMIN_URL . '/pages/certifications.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $cid = (int)($_POST['id'] ?? 0);
    $data = [
        'title'              => sanitize($_POST['title'] ?? ''),
        'issuer'             => sanitize($_POST['issuer'] ?? ''),
        'certificate_number' => sanitize($_POST['certificate_number'] ?? ''),
        'issued_date'        => ($_POST['issued_date'] ?? '') !== '' ? $_POST['issued_date'] : null,
        'status'             => isset($_POST['status']) ? 'active' : 'inactive',
    ];
    if ($data['title'] === '') {
        set_flash('error', 'Title is required.');
        header('Location: ' . ADMIN_URL . '/pages/certifications.php'); exit;
    }
    if (!empty($_FILES['image']['name'])) {
        $res = upload_image($_FILES['image'], 'certifications', 500, 500, 'cert');
        if (!isset($res['error'])) $data['image'] = $res['filename'];
    }
    if ($cid) { update_record('certifications', $data, 'id', $cid); set_flash('success', 'Certification updated.'); }
    else {
        $data['sort_order'] = (int)(fetch_one("SELECT MAX(sort_order) m FROM certifications")['m'] ?? 0) + 1;
        insert('certifications', $data);
        set_flash('success', 'Certification added.');
    }
    header('Location: ' . ADMIN_URL . '/pages/certifications.php');
    exit;
}

$edit = isset($_GET['edit']) ? fetch_one("SELECT * FROM certifications WHERE id=?", 'i', (int)$_GET['edit']) : null;
$certifications = fetch_all("SELECT * FROM certifications ORDER BY sort_order ASC, id ASC");

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
  <div class="lg:col-span-2">
    <div class="card p-6 sticky top-20">
      <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-slate-800"><?= $edit ? 'Edit Certification' : 'Add Certification' ?></h2>
        <?php if ($edit): ?><a href="<?= ADMIN_URL ?>/pages/certifications.php" class="text-xs font-semibold text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i> Cancel</a><?php endif; ?>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

        <div class="mb-4">
          <label class="f-label">Certificate Image <span class="text-slate-400 font-normal">(optional)</span></label>
          <?php if (!empty($edit['image'])): ?><img src="<?= UPLOAD_URL . h($edit['image']) ?>" class="w-24 h-24 rounded-lg object-cover mb-2 border border-slate-200"><?php endif; ?>
          <input type="file" name="image" accept="image/*" class="f-input">
        </div>
        <div class="mb-4">
          <label class="f-label">Title *</label>
          <input type="text" name="title" required class="f-input" value="<?= h($edit['title'] ?? '') ?>" placeholder="e.g. Sialkot Chamber of Commerce Membership">
        </div>
        <div class="mb-4">
          <label class="f-label">Issuing Body</label>
          <input type="text" name="issuer" class="f-input" value="<?= h($edit['issuer'] ?? '') ?>" placeholder="e.g. SCCI">
        </div>
        <div class="mb-4">
          <label class="f-label">Certificate / Membership No.</label>
          <input type="text" name="certificate_number" class="f-input" value="<?= h($edit['certificate_number'] ?? '') ?>">
        </div>
        <div class="mb-6">
          <label class="f-label">Issued Date</label>
          <input type="date" name="issued_date" class="f-input" value="<?= h($edit['issued_date'] ?? '') ?>">
        </div>
        <div class="flex items-center gap-2 mb-6">
          <input type="checkbox" name="status" id="cStatus" value="1" class="w-4 h-4 rounded accent-brand" <?= (!$edit || $edit['status'] === 'active') ? 'checked' : '' ?>>
          <label for="cStatus" class="text-sm font-medium text-slate-700">Active</label>
        </div>
        <button type="submit" class="btn-primary w-full"><i class="fa-solid <?= $edit ? 'fa-floppy-disk' : 'fa-plus' ?>"></i> <?= $edit ? 'Update' : 'Add Certification' ?></button>
      </form>
    </div>
  </div>

  <div class="lg:col-span-3">
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-800">All Certifications <span class="text-slate-400 font-normal">(<?= count($certifications) ?>)</span></h2></div>
      <div class="divide-y divide-slate-100">
        <?php foreach ($certifications as $i => $c): ?>
        <div class="flex items-center gap-4 px-5 py-3">
          <?php if ($c['image']): ?><img src="<?= UPLOAD_URL . h($c['image']) ?>" class="w-10 h-10 rounded-lg object-cover"><?php else: ?><div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-300 flex items-center justify-center"><i class="fa-solid fa-certificate"></i></div><?php endif; ?>
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-sm text-slate-800 truncate"><?= h($c['title']) ?></div>
            <div class="text-xs text-slate-400 truncate"><?= h($c['issuer']) ?></div>
          </div>
          <span class="<?= $c['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($c['status']) ?></span>
          <a href="?action=move&id=<?= (int)$c['id'] ?>&dir=up&csrf_token=<?= csrf_token() ?>" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-700 <?= $i === 0 ? 'invisible' : '' ?>"><i class="fa-solid fa-arrow-up text-xs"></i></a>
          <a href="?action=move&id=<?= (int)$c['id'] ?>&dir=down&csrf_token=<?= csrf_token() ?>" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-700 <?= $i === count($certifications) - 1 ? 'invisible' : '' ?>"><i class="fa-solid fa-arrow-down text-xs"></i></a>
          <a href="?edit=<?= (int)$c['id'] ?>" class="btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
          <a href="?action=toggle&id=<?= (int)$c['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-outline btn-sm"><i class="fa-solid <?= $c['status'] === 'active' ? 'fa-eye-slash' : 'fa-eye' ?>"></i></a>
          <a href="?action=delete&id=<?= (int)$c['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this certification?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
        </div>
        <?php endforeach; ?>
        <?php if (empty($certifications)): ?><div class="px-5 py-10 text-center text-slate-400">No certifications yet.</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
