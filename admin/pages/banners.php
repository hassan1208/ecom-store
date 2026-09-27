<?php
// admin/pages/banners.php — hero slider banners AND promo banners (same table, split by placement)
require_once __DIR__ . '/../../includes/config.php';
require_admin();

$placement = ($_GET['type'] ?? 'hero') === 'promo' ? 'promo' : 'hero';
$page_title = $placement === 'promo' ? 'Promo Banners' : 'Hero Slider Banners';

if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $b = fetch_one("SELECT status FROM banners WHERE id=?", 'i', $id);
    if ($b) update_record('banners', ['status' => $b['status'] === 'active' ? 'inactive' : 'active'], 'id', $id);
    header('Location: ' . ADMIN_URL . '/pages/banners.php?type=' . $placement); exit;
}
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $b = fetch_one("SELECT image FROM banners WHERE id=?", 'i', $id);
    db()->query("DELETE FROM banners WHERE id=" . $id);
    if ($b && $b['image'] && file_exists(UPLOAD_PATH . $b['image'])) @unlink(UPLOAD_PATH . $b['image']);
    set_flash('success', 'Banner deleted.');
    header('Location: ' . ADMIN_URL . '/pages/banners.php?type=' . $placement); exit;
}
if (($_GET['action'] ?? '') === 'move' && isset($_GET['id'], $_GET['dir'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'] === 'up' ? 'ASC' : 'DESC';
    $cmp = $_GET['dir'] === 'up' ? '<' : '>';
    $current = fetch_one("SELECT sort_order, placement FROM banners WHERE id=?", 'i', $id);
    $neighbor = fetch_one("SELECT id, sort_order FROM banners WHERE sort_order $cmp ? AND placement=? ORDER BY sort_order $dir LIMIT 1", 'is', $current['sort_order'], $current['placement']);
    if ($neighbor) {
        update_record('banners', ['sort_order' => $neighbor['sort_order']], 'id', $id);
        update_record('banners', ['sort_order' => $current['sort_order']], 'id', $neighbor['id']);
    }
    header('Location: ' . ADMIN_URL . '/pages/banners.php?type=' . $placement); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $bid = (int)($_POST['id'] ?? 0);
    $post_placement = ($_POST['placement'] ?? 'hero') === 'promo' ? 'promo' : 'hero';
    $data = [
        'title'       => sanitize($_POST['title'] ?? ''),
        'subtitle'    => sanitize($_POST['subtitle'] ?? ''),
        'image_alt'   => sanitize($_POST['image_alt'] ?? ''),
        'button_text' => sanitize($_POST['button_text'] ?? ''),
        'button_url'  => sanitize($_POST['button_url'] ?? ''),
        'placement'   => $post_placement,
        'status'      => isset($_POST['status']) ? 'active' : 'inactive',
    ];
    if (!empty($_FILES['image']['name'])) {
        $dims = $post_placement === 'promo' ? [1200, 500] : [1920, 1080];
        $res = upload_image($_FILES['image'], 'banners', $dims[0], $dims[1], 'banner');
        if (isset($res['error'])) { set_flash('error', $res['error']); header('Location: ' . ADMIN_URL . '/pages/banners.php?type=' . $post_placement); exit; }
        $data['image'] = $res['filename'];
    } elseif (!$bid) {
        set_flash('error', 'Please choose a banner image.');
        header('Location: ' . ADMIN_URL . '/pages/banners.php?type=' . $post_placement); exit;
    }
    if ($bid) {
        update_record('banners', $data, 'id', $bid);
        set_flash('success', 'Banner updated.');
    } else {
        $data['sort_order'] = (int)(fetch_one("SELECT MAX(sort_order) m FROM banners WHERE placement=?", 's', $post_placement)['m'] ?? 0) + 1;
        insert('banners', $data);
        set_flash('success', 'Banner added.');
    }
    header('Location: ' . ADMIN_URL . '/pages/banners.php?type=' . $post_placement); exit;
}

$edit = isset($_GET['edit']) ? fetch_one("SELECT * FROM banners WHERE id=?", 'i', (int)$_GET['edit']) : null;
$banners = fetch_all("SELECT * FROM banners WHERE placement=? ORDER BY sort_order ASC, id ASC", 's', $placement);

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex items-center gap-2 mb-5">
  <a href="?type=hero" class="px-4 py-2 rounded-lg text-sm font-semibold transition <?= $placement === 'hero' ? 'bg-brand text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">Hero Slider</a>
  <a href="?type=promo" class="px-4 py-2 rounded-lg text-sm font-semibold transition <?= $placement === 'promo' ? 'bg-brand text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">Promo Banners</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
  <div class="lg:col-span-2">
    <div class="card p-6 sticky top-20">
      <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-slate-800"><?= $edit ? 'Edit Banner' : 'Add Banner' ?></h2>
        <?php if ($edit): ?><a href="<?= ADMIN_URL ?>/pages/banners.php?type=<?= $placement ?>" class="text-xs font-semibold text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i> Cancel</a><?php endif; ?>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <input type="hidden" name="placement" value="<?= $placement ?>">

        <div class="mb-4">
          <label class="f-label">Banner Image *</label>
          <?php if (!empty($edit['image'])): ?><img src="<?= UPLOAD_URL . h($edit['image']) ?>" class="w-full h-32 object-cover rounded-lg border border-slate-200 mb-2" alt=""><?php endif; ?>
          <input type="file" name="image" accept="image/*" class="f-input">
          <p class="f-hint">Recommended: <?= $placement === 'promo' ? '1200×500px' : '1920×1080px (widescreen)' ?>, under 500KB.</p>
        </div>
        <div class="mb-4">
          <label class="f-label">Image Alt Text <span class="text-slate-400 font-normal">(SEO)</span></label>
          <input type="text" name="image_alt" class="f-input" value="<?= h($edit['image_alt'] ?? '') ?>" placeholder="Describe the banner image">
        </div>
        <div class="mb-4">
          <label class="f-label">Headline</label>
          <input type="text" name="title" class="f-input" value="<?= h($edit['title'] ?? '') ?>" placeholder="e.g. New Season Sportswear">
        </div>
        <div class="mb-4">
          <label class="f-label">Eyebrow / Subtitle</label>
          <input type="text" name="subtitle" class="f-input" value="<?= h($edit['subtitle'] ?? '') ?>" placeholder="e.g. LIMITED DROP">
        </div>
        <div class="grid grid-cols-2 gap-3 mb-5">
          <div>
            <label class="f-label">Button Text</label>
            <input type="text" name="button_text" class="f-input" value="<?= h($edit['button_text'] ?? '') ?>" placeholder="Shop Now">
          </div>
          <div>
            <label class="f-label">Button Link</label>
            <input type="text" name="button_url" class="f-input" value="<?= h($edit['button_url'] ?? '') ?>" placeholder="/category/...">
          </div>
        </div>
        <div class="flex items-center gap-2 mb-6">
          <input type="checkbox" name="status" id="bStatus" value="1" class="w-4 h-4 rounded accent-brand" <?= (!$edit || $edit['status'] === 'active') ? 'checked' : '' ?>>
          <label for="bStatus" class="text-sm font-medium text-slate-700">Active</label>
        </div>
        <button type="submit" class="btn-primary w-full"><i class="fa-solid <?= $edit ? 'fa-floppy-disk' : 'fa-plus' ?>"></i> <?= $edit ? 'Update Banner' : 'Add Banner' ?></button>
      </form>
    </div>
  </div>

  <div class="lg:col-span-3">
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-800">All Banners <span class="text-slate-400 font-normal">(<?= count($banners) ?>)</span></h2></div>
      <div class="divide-y divide-slate-100">
        <?php foreach ($banners as $i => $b): ?>
        <div class="flex items-center gap-4 px-5 py-3">
          <img src="<?= UPLOAD_URL . h($b['image']) ?>" class="w-20 h-12 object-cover rounded-lg border border-slate-200" alt="">
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-sm text-slate-800 truncate"><?= h($b['title'] ?: '(no headline)') ?></div>
            <div class="text-xs text-slate-400 truncate"><?= h($b['subtitle']) ?></div>
          </div>
          <span class="<?= $b['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($b['status']) ?></span>
          <div class="flex items-center gap-1">
            <a href="?type=<?= $placement ?>&action=move&id=<?= (int)$b['id'] ?>&dir=up&csrf_token=<?= csrf_token() ?>" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-700 <?= $i === 0 ? 'invisible' : '' ?>"><i class="fa-solid fa-arrow-up text-xs"></i></a>
            <a href="?type=<?= $placement ?>&action=move&id=<?= (int)$b['id'] ?>&dir=down&csrf_token=<?= csrf_token() ?>" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-700 <?= $i === count($banners) - 1 ? 'invisible' : '' ?>"><i class="fa-solid fa-arrow-down text-xs"></i></a>
          </div>
          <a href="?type=<?= $placement ?>&edit=<?= (int)$b['id'] ?>" class="btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
          <a href="?type=<?= $placement ?>&action=toggle&id=<?= (int)$b['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-outline btn-sm"><i class="fa-solid <?= $b['status'] === 'active' ? 'fa-eye-slash' : 'fa-eye' ?>"></i></a>
          <a href="?type=<?= $placement ?>&action=delete&id=<?= (int)$b['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this banner permanently?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
        </div>
        <?php endforeach; ?>
        <?php if (empty($banners)): ?><div class="px-5 py-10 text-center text-slate-400">No banners yet.</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
