<?php
// admin/pages/coupons.php — discount coupon codes
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Coupons';

if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $c = fetch_one("SELECT status FROM coupons WHERE id=?", 'i', $id);
    if ($c) update_record('coupons', ['status' => $c['status'] === 'active' ? 'inactive' : 'active'], 'id', $id);
    header('Location: ' . ADMIN_URL . '/pages/coupons.php'); exit;
}
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    db()->query("DELETE FROM coupons WHERE id=" . (int)$_GET['id']);
    set_flash('success', 'Coupon deleted.');
    header('Location: ' . ADMIN_URL . '/pages/coupons.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $cid = (int)($_POST['id'] ?? 0);
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    if ($code === '') {
        set_flash('error', 'Coupon code is required.');
        header('Location: ' . ADMIN_URL . '/pages/coupons.php'); exit;
    }
    $data = [
        'code'       => $code,
        'type'       => ($_POST['type'] ?? '') === 'fixed' ? 'fixed' : 'percentage',
        'value'      => (float)($_POST['value'] ?? 0),
        'min_order'  => (float)($_POST['min_order'] ?? 0),
        'max_uses'   => $_POST['max_uses'] !== '' ? (int)$_POST['max_uses'] : null,
        'expires_at' => $_POST['expires_at'] !== '' ? $_POST['expires_at'] : null,
        'status'     => isset($_POST['status']) ? 'active' : 'inactive',
    ];
    if ($cid) { update_record('coupons', $data, 'id', $cid); set_flash('success', 'Coupon updated.'); }
    else {
        $exists = fetch_one("SELECT id FROM coupons WHERE code=?", 's', $code);
        if ($exists) { set_flash('error', 'A coupon with this code already exists.'); header('Location: ' . ADMIN_URL . '/pages/coupons.php'); exit; }
        insert('coupons', $data);
        set_flash('success', 'Coupon created.');
    }
    header('Location: ' . ADMIN_URL . '/pages/coupons.php');
    exit;
}

$edit = isset($_GET['edit']) ? fetch_one("SELECT * FROM coupons WHERE id=?", 'i', (int)$_GET['edit']) : null;
$coupons = fetch_all("SELECT * FROM coupons ORDER BY created_at DESC");

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
  <div class="lg:col-span-2">
    <div class="card p-6 sticky top-20">
      <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-slate-800"><?= $edit ? 'Edit Coupon' : 'Add Coupon' ?></h2>
        <?php if ($edit): ?><a href="<?= ADMIN_URL ?>/pages/coupons.php" class="text-xs font-semibold text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i> Cancel</a><?php endif; ?>
      </div>
      <form method="POST">
        <?= csrf_field() ?>
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

        <div class="mb-4">
          <label class="f-label">Coupon Code *</label>
          <input type="text" name="code" required class="f-input uppercase" value="<?= h($edit['code'] ?? '') ?>" placeholder="e.g. REMIT10" style="text-transform:uppercase">
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4">
          <div>
            <label class="f-label">Discount Type</label>
            <select name="type" class="f-select">
              <option value="percentage" <?= (($edit['type'] ?? '') !== 'fixed') ? 'selected' : '' ?>>Percentage (%)</option>
              <option value="fixed" <?= (($edit['type'] ?? '') === 'fixed') ? 'selected' : '' ?>>Fixed Amount</option>
            </select>
          </div>
          <div>
            <label class="f-label">Value</label>
            <input type="number" step="0.01" min="0" name="value" required class="f-input" value="<?= h($edit['value'] ?? '') ?>">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4">
          <div>
            <label class="f-label">Min. Order</label>
            <input type="number" step="0.01" min="0" name="min_order" class="f-input" value="<?= h($edit['min_order'] ?? '0') ?>">
          </div>
          <div>
            <label class="f-label">Max Uses <span class="text-slate-400 font-normal">(optional)</span></label>
            <input type="number" min="1" name="max_uses" class="f-input" value="<?= h($edit['max_uses'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-4">
          <label class="f-label">Expires On <span class="text-slate-400 font-normal">(optional)</span></label>
          <input type="date" name="expires_at" class="f-input" value="<?= h($edit['expires_at'] ?? '') ?>">
        </div>

        <div class="flex items-center gap-2 mb-6">
          <input type="checkbox" name="status" id="cStatus" value="1" class="w-4 h-4 rounded accent-brand" <?= (!$edit || $edit['status'] === 'active') ? 'checked' : '' ?>>
          <label for="cStatus" class="text-sm font-medium text-slate-700">Active</label>
        </div>

        <button type="submit" class="btn-primary w-full"><i class="fa-solid <?= $edit ? 'fa-floppy-disk' : 'fa-plus' ?>"></i> <?= $edit ? 'Update Coupon' : 'Add Coupon' ?></button>
      </form>
    </div>
  </div>

  <div class="lg:col-span-3">
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-800">All Coupons <span class="text-slate-400 font-normal">(<?= count($coupons) ?>)</span></h2></div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
              <th class="px-5 py-3 font-semibold">Code</th>
              <th class="px-5 py-3 font-semibold">Discount</th>
              <th class="px-5 py-3 font-semibold">Used</th>
              <th class="px-5 py-3 font-semibold">Expires</th>
              <th class="px-5 py-3 font-semibold">Status</th>
              <th class="px-5 py-3 font-semibold text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($coupons as $c): ?>
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-semibold text-slate-800"><?= h($c['code']) ?></td>
              <td class="px-5 py-3 text-slate-600"><?= $c['type'] === 'percentage' ? (int)$c['value'] . '%' : format_price($c['value']) ?></td>
              <td class="px-5 py-3 text-slate-500"><?= (int)$c['used_count'] ?><?= $c['max_uses'] ? ' / ' . (int)$c['max_uses'] : '' ?></td>
              <td class="px-5 py-3 text-slate-500"><?= $c['expires_at'] ? format_date($c['expires_at']) : '—' ?></td>
              <td class="px-5 py-3"><span class="<?= $c['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($c['status']) ?></span></td>
              <td class="px-5 py-3 text-right">
                <a href="?edit=<?= (int)$c['id'] ?>" class="btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
                <a href="?action=toggle&id=<?= (int)$c['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-outline btn-sm"><i class="fa-solid <?= $c['status'] === 'active' ? 'fa-eye-slash' : 'fa-eye' ?>"></i></a>
                <a href="?action=delete&id=<?= (int)$c['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this coupon?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($coupons)): ?>
            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">No coupons yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
