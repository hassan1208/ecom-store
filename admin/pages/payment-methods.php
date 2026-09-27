<?php
// admin/pages/payment-methods.php — enable/disable payment methods + their checkout instructions
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Payment Methods';

if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $m = fetch_one("SELECT status FROM payment_methods WHERE id=?", 'i', $id);
    if ($m) update_record('payment_methods', ['status' => $m['status'] === 'active' ? 'inactive' : 'active'], 'id', $id);
    header('Location: ' . ADMIN_URL . '/pages/payment-methods.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'method') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    update_record('payment_methods', [
        'name'                => sanitize($_POST['name'] ?? ''),
        'instructions'        => sanitize($_POST['instructions'] ?? ''),
        'youtube_url'         => sanitize($_POST['youtube_url'] ?? ''),
        'account_details'     => sanitize($_POST['account_details'] ?? ''),
        'requires_screenshot' => isset($_POST['requires_screenshot']) ? 1 : 0,
        'status'              => isset($_POST['status']) ? 'active' : 'inactive',
    ], 'id', $id);
    set_flash('success', 'Payment method updated.');
    header('Location: ' . ADMIN_URL . '/pages/payment-methods.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'payfast_credentials') {
    require_csrf();
    foreach (['payfast_merchant_id', 'payfast_merchant_key', 'payfast_passphrase', 'payfast_mode'] as $key) {
        $val = sanitize($_POST[$key] ?? '');
        $stmt = db()->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param('sss', $key, $val, $val);
        $stmt->execute();
    }
    set_flash('success', 'PayFast credentials saved.');
    header('Location: ' . ADMIN_URL . '/pages/payment-methods.php');
    exit;
}

$methods = fetch_all("SELECT * FROM payment_methods ORDER BY sort_order ASC, id ASC");
$s = get_settings();

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <?php foreach ($methods as $m): ?>
  <div class="card p-6">
    <div class="flex items-center justify-between mb-5">
      <div class="flex items-center gap-3">
        <h2 class="font-bold text-slate-800"><?= h($m['name']) ?></h2>
        <span class="<?= $m['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($m['status']) ?></span>
      </div>
      <a href="?action=toggle&id=<?= (int)$m['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-outline btn-sm">
        <i class="fa-solid <?= $m['status'] === 'active' ? 'fa-toggle-off' : 'fa-toggle-on' ?>"></i>
        <?= $m['status'] === 'active' ? 'Disable' : 'Enable' ?>
      </a>
    </div>

    <form method="POST" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="form" value="method">
      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label class="f-label">Display Name</label><input type="text" name="name" class="f-input" value="<?= h($m['name']) ?>"></div>
        <?php if ($m['code'] === 'remitly'): ?>
        <div><label class="f-label">Tutorial YouTube Link</label><input type="text" name="youtube_url" class="f-input" value="<?= h($m['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/watch?v=..."></div>
        <?php endif; ?>
      </div>

      <div><label class="f-label">Checkout Instructions</label><textarea name="instructions" rows="2" class="f-textarea"><?= h($m['instructions'] ?? '') ?></textarea></div>

      <?php if ($m['code'] !== 'payfast'): ?>
      <div><label class="f-label">Account Details <span class="text-slate-400 font-normal">(shown to customer — account name/number to send payment to)</span></label><textarea name="account_details" rows="2" class="f-textarea"><?= h($m['account_details'] ?? '') ?></textarea></div>
      <?php endif; ?>

      <div class="flex items-center gap-6">
        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
          <input type="checkbox" name="requires_screenshot" value="1" class="w-4 h-4 rounded accent-brand" <?= $m['requires_screenshot'] ? 'checked' : '' ?>> Require payment screenshot
        </label>
        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
          <input type="checkbox" name="status" value="1" class="w-4 h-4 rounded accent-brand" <?= $m['status'] === 'active' ? 'checked' : '' ?>> Active at checkout
        </label>
      </div>

      <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    </form>

    <?php if ($m['code'] === 'payfast'): ?>
    <div class="mt-6 pt-6 border-t border-slate-100">
      <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">PayFast Merchant Credentials</h3>
      <p class="f-hint mb-4">Fill these in once you have a live PayFast merchant account, then enable this method above. Live checkout integration activates automatically once credentials are present.</p>
      <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="payfast_credentials">
        <div><label class="f-label">Merchant ID</label><input type="text" name="payfast_merchant_id" class="f-input" value="<?= h($s['payfast_merchant_id'] ?? '') ?>"></div>
        <div><label class="f-label">Merchant Key</label><input type="text" name="payfast_merchant_key" class="f-input" value="<?= h($s['payfast_merchant_key'] ?? '') ?>"></div>
        <div><label class="f-label">Passphrase</label><input type="password" name="payfast_passphrase" class="f-input" value="<?= h($s['payfast_passphrase'] ?? '') ?>"></div>
        <div>
          <label class="f-label">Mode</label>
          <select name="payfast_mode" class="f-select">
            <option value="sandbox" <?= ($s['payfast_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox (testing)</option>
            <option value="live" <?= ($s['payfast_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live</option>
          </select>
        </div>
        <div class="sm:col-span-2"><button type="submit" class="btn-outline"><i class="fa-solid fa-key"></i> Save Credentials</button></div>
      </form>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
