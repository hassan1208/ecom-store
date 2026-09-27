<?php
// admin/pages/menu.php — navbar builder (category links + custom links, with dropdowns)
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Navbar Menu';

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    db()->query("DELETE FROM menu_items WHERE id=" . (int)$_GET['id']);
    set_flash('success', 'Menu item removed.');
    header('Location: ' . ADMIN_URL . '/pages/menu.php'); exit;
}
if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $m = fetch_one("SELECT status FROM menu_items WHERE id=?", 'i', $id);
    if ($m) update_record('menu_items', ['status' => $m['status'] === 'active' ? 'inactive' : 'active'], 'id', $id);
    header('Location: ' . ADMIN_URL . '/pages/menu.php'); exit;
}
if (($_GET['action'] ?? '') === 'move' && isset($_GET['id'], $_GET['dir'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $item = fetch_one("SELECT parent_id, sort_order FROM menu_items WHERE id=?", 'i', $id);
    $dir = $_GET['dir'] === 'up' ? 'ASC' : 'DESC';
    $cmp = $_GET['dir'] === 'up' ? '<' : '>';
    $sql = "SELECT id, sort_order FROM menu_items WHERE sort_order $cmp ? AND " . ($item['parent_id'] ? "parent_id=?" : "parent_id IS NULL") . " ORDER BY sort_order $dir LIMIT 1";
    $neighbor = $item['parent_id']
        ? fetch_one($sql, 'ii', $item['sort_order'], $item['parent_id'])
        : fetch_one($sql, 'i', $item['sort_order']);
    if ($neighbor) {
        update_record('menu_items', ['sort_order' => $neighbor['sort_order']], 'id', $id);
        update_record('menu_items', ['sort_order' => $item['sort_order']], 'id', $neighbor['id']);
    }
    header('Location: ' . ADMIN_URL . '/pages/menu.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $mid = (int)($_POST['id'] ?? 0);
    $link_type = ($_POST['link_type'] ?? 'custom') === 'category' ? 'category' : 'custom';
    $data = [
        'label'        => sanitize($_POST['label'] ?? ''),
        'link_type'    => $link_type,
        'category_id'  => $link_type === 'category' ? ((int)($_POST['category_id'] ?? 0) ?: null) : null,
        'custom_url'   => $link_type === 'custom' ? sanitize($_POST['custom_url'] ?? '') : null,
        'parent_id'    => (int)($_POST['parent_id'] ?? 0) ?: null,
        'open_new_tab' => isset($_POST['open_new_tab']) ? 1 : 0,
        'status'       => isset($_POST['status']) ? 'active' : 'inactive',
    ];
    if ($data['label'] === '') {
        set_flash('error', 'Menu label is required.');
    } elseif ($link_type === 'category' && !$data['category_id']) {
        set_flash('error', 'Please choose a category.');
    } elseif ($link_type === 'custom' && !$data['custom_url']) {
        set_flash('error', 'Please enter a URL.');
    } else {
        if ($mid) { update_record('menu_items', $data, 'id', $mid); set_flash('success', 'Menu item updated.'); }
        else {
            $data['sort_order'] = (int)(fetch_one("SELECT MAX(sort_order) m FROM menu_items WHERE " . ($data['parent_id'] ? "parent_id={$data['parent_id']}" : "parent_id IS NULL"))['m'] ?? 0) + 1;
            insert('menu_items', $data);
            set_flash('success', 'Menu item added.');
        }
    }
    header('Location: ' . ADMIN_URL . '/pages/menu.php'); exit;
}

$edit = isset($_GET['edit']) ? fetch_one("SELECT * FROM menu_items WHERE id=?", 'i', (int)$_GET['edit']) : null;
$top_items = fetch_all("SELECT * FROM menu_items WHERE parent_id IS NULL ORDER BY sort_order ASC, id ASC");
foreach ($top_items as &$ti) {
    $ti['children'] = fetch_all("SELECT * FROM menu_items WHERE parent_id=? ORDER BY sort_order ASC, id ASC", 'i', $ti['id']);
}
unset($ti);
$categories = fetch_all("SELECT id, name FROM categories WHERE status='active' ORDER BY name ASC");
$parent_options = fetch_all("SELECT id, label FROM menu_items WHERE parent_id IS NULL ORDER BY label ASC");

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
  <div class="lg:col-span-2">
    <div class="card p-6 sticky top-20">
      <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-slate-800"><?= $edit ? 'Edit Menu Item' : 'Add Menu Item' ?></h2>
        <?php if ($edit): ?><a href="<?= ADMIN_URL ?>/pages/menu.php" class="text-xs font-semibold text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i> Cancel</a><?php endif; ?>
      </div>
      <form method="POST">
        <?= csrf_field() ?>
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

        <div class="mb-4">
          <label class="f-label">Label *</label>
          <input type="text" name="label" required class="f-input" value="<?= h($edit['label'] ?? '') ?>" placeholder="e.g. Shop">
        </div>

        <div class="mb-4">
          <label class="f-label">Placement</label>
          <select name="parent_id" class="f-select">
            <option value="">— Top Level (main navbar) —</option>
            <?php foreach ($parent_options as $p): if ($edit && $p['id'] == $edit['id']) continue; ?>
            <option value="<?= (int)$p['id'] ?>" <?= (($edit['parent_id'] ?? 0) == $p['id']) ? 'selected' : '' ?>>Dropdown under: <?= h($p['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-4">
          <label class="f-label">Link Type</label>
          <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center gap-2 border border-slate-300 rounded-lg px-3 py-2.5 cursor-pointer has-[:checked]:border-brand has-[:checked]:bg-brand-light/40">
              <input type="radio" name="link_type" value="category" class="accent-brand" <?= (($edit['link_type'] ?? '') === 'category') ? 'checked' : '' ?> onchange="toggleLinkType()"> <span class="text-sm">Category</span>
            </label>
            <label class="flex items-center gap-2 border border-slate-300 rounded-lg px-3 py-2.5 cursor-pointer has-[:checked]:border-brand has-[:checked]:bg-brand-light/40">
              <input type="radio" name="link_type" value="custom" class="accent-brand" <?= (($edit['link_type'] ?? 'custom') === 'custom') ? 'checked' : '' ?> onchange="toggleLinkType()"> <span class="text-sm">Custom URL</span>
            </label>
          </div>
        </div>

        <div class="mb-4" id="categoryField">
          <label class="f-label">Category</label>
          <select name="category_id" class="f-select">
            <option value="">— Select —</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (($edit['category_id'] ?? 0) == $c['id']) ? 'selected' : '' ?>><?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-4" id="customField">
          <label class="f-label">URL</label>
          <input type="text" name="custom_url" class="f-input" value="<?= h($edit['custom_url'] ?? '') ?>" placeholder="/ or https://...">
        </div>

        <div class="flex items-center gap-2 mb-4">
          <input type="checkbox" name="open_new_tab" id="mNewTab" value="1" class="w-4 h-4 rounded accent-brand" <?= !empty($edit['open_new_tab']) ? 'checked' : '' ?>>
          <label for="mNewTab" class="text-sm font-medium text-slate-700">Open in new tab</label>
        </div>
        <div class="flex items-center gap-2 mb-6">
          <input type="checkbox" name="status" id="mStatus" value="1" class="w-4 h-4 rounded accent-brand" <?= (!$edit || $edit['status'] === 'active') ? 'checked' : '' ?>>
          <label for="mStatus" class="text-sm font-medium text-slate-700">Active</label>
        </div>

        <button type="submit" class="btn-primary w-full"><i class="fa-solid <?= $edit ? 'fa-floppy-disk' : 'fa-plus' ?>"></i> <?= $edit ? 'Update Item' : 'Add to Menu' ?></button>
      </form>
    </div>
  </div>

  <div class="lg:col-span-3">
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-800">Navbar Structure</h2></div>
      <div class="divide-y divide-slate-100">
        <?php foreach ($top_items as $i => $item): ?>
        <div class="px-5 py-3">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-grip-lines-vertical text-slate-300"></i>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-sm text-slate-800"><?= h($item['label']) ?></div>
              <div class="text-xs text-slate-400"><?= $item['link_type'] === 'category' ? 'Category link' : h($item['custom_url']) ?></div>
            </div>
            <span class="<?= $item['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($item['status']) ?></span>
            <a href="?action=move&id=<?= (int)$item['id'] ?>&dir=up&csrf_token=<?= csrf_token() ?>" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-700 <?= $i === 0 ? 'invisible' : '' ?>"><i class="fa-solid fa-arrow-up text-xs"></i></a>
            <a href="?action=move&id=<?= (int)$item['id'] ?>&dir=down&csrf_token=<?= csrf_token() ?>" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-700 <?= $i === count($top_items) - 1 ? 'invisible' : '' ?>"><i class="fa-solid fa-arrow-down text-xs"></i></a>
            <a href="?edit=<?= (int)$item['id'] ?>" class="btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
            <a href="?action=toggle&id=<?= (int)$item['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-outline btn-sm"><i class="fa-solid <?= $item['status'] === 'active' ? 'fa-eye-slash' : 'fa-eye' ?>"></i></a>
            <a href="?action=delete&id=<?= (int)$item['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Remove this menu item?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
          </div>
          <?php foreach ($item['children'] as $j => $child): ?>
          <div class="flex items-center gap-3 mt-2 ml-8 pl-3 border-l-2 border-slate-100">
            <i class="fa-solid fa-arrow-turn-up fa-rotate-90 text-slate-300 text-xs"></i>
            <div class="flex-1 min-w-0">
              <div class="font-medium text-sm text-slate-700"><?= h($child['label']) ?></div>
              <div class="text-xs text-slate-400"><?= $child['link_type'] === 'category' ? 'Category link' : h($child['custom_url']) ?></div>
            </div>
            <span class="<?= $child['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($child['status']) ?></span>
            <a href="?edit=<?= (int)$child['id'] ?>" class="btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
            <a href="?action=toggle&id=<?= (int)$child['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-outline btn-sm"><i class="fa-solid <?= $child['status'] === 'active' ? 'fa-eye-slash' : 'fa-eye' ?>"></i></a>
            <a href="?action=delete&id=<?= (int)$child['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Remove this menu item?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <?php if (empty($top_items)): ?><div class="px-5 py-10 text-center text-slate-400">No menu items yet. Build your navbar using the form.</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function toggleLinkType() {
  const type = document.querySelector('input[name=link_type]:checked')?.value || 'custom';
  document.getElementById('categoryField').style.display = type === 'category' ? '' : 'none';
  document.getElementById('customField').style.display = type === 'custom' ? '' : 'none';
}
toggleLinkType();
</script>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
