<?php
// account.php — customer "My Account" dashboard: profile + order history
require_once __DIR__ . '/includes/config.php';
require_customer();

$customer = current_customer();
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    require_csrf();
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm          = $_POST['confirm_password'] ?? '';

    if (!password_verify($current_password, $customer['password'])) $errors[] = 'Current password is incorrect.';
    if (strlen($new_password) < 6) $errors[] = 'New password must be at least 6 characters.';
    if ($new_password !== $confirm) $errors[] = 'New passwords do not match.';

    if (!$errors) {
        update_record('users', ['password' => password_hash($new_password, PASSWORD_DEFAULT)], 'id', $customer['id']);
        $success = 'Password updated successfully.';
        $customer = current_customer();
    }
}

// Orders placed while logged in (customer_id) plus any earlier guest orders
// placed with the same email, so registering later still surfaces order history.
$orders = fetch_all(
    "SELECT * FROM orders WHERE customer_id=? OR customer_email=? ORDER BY created_at DESC",
    'is', $customer['id'], $customer['email']
);

$meta_title = 'My Account | ' . setting('site_name');
$meta_robots = 'noindex, follow';
include __DIR__ . '/includes/site-header.php';
$hero_title = 'My Account'; $hero_eyebrow = 'Welcome back, ' . ($customer['first_name'] ?? ''); $hero_crumbs = ['Account' => null];
include __DIR__ . '/includes/page-hero.php';
?>
<main id="main" class="container-x max-w-5xl py-12">
  <div class="flex items-center justify-end mb-8">
    <div class="flex items-center gap-4">
      <a href="<?= url('wishlist') ?>" class="text-sm font-semibold text-slate-500 hover:text-ignite"><i class="fa-solid fa-heart mr-1"></i>Wishlist</a>
      <form method="POST" action="<?= url('logout') ?>"><?= csrf_field() ?><button type="submit" class="text-sm font-semibold text-slate-500 hover:text-ignite">Sign Out</button></form>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-2">
      <h2 class="font-display font-semibold text-lg mb-4">Order History</h2>
      <?php if ($orders): ?>
      <div class="space-y-3">
        <?php foreach ($orders as $o): ?>
        <a href="<?= url('track-order?order=' . urlencode($o['order_number']) . '&email=' . urlencode($o['customer_email'])) ?>" class="flex items-center justify-between rounded-xl border border-slate-200 p-4 hover:border-ignite transition">
          <div>
            <p class="font-semibold text-sm text-slate-800"><?= h($o['order_number']) ?></p>
            <p class="text-xs text-slate-400 mt-0.5"><?= date('M j, Y', strtotime($o['created_at'])) ?> &middot; <?= (int)fetch_one("SELECT COUNT(*) c FROM order_items WHERE order_id=?", 'i', $o['id'])['c'] ?> item(s)</p>
          </div>
          <div class="text-right">
            <p class="font-bold text-sm text-ink"><?= format_price($o['total']) ?></p>
            <span class="inline-block mt-1 text-[10px] font-bold uppercase px-2 py-0.5 rounded-full <?= $o['status'] === 'delivered' ? 'bg-emerald-100 text-emerald-700' : ($o['status'] === 'cancelled' || $o['status'] === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') ?>"><?= h(ucfirst($o['status'])) ?></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-400">
        <i class="fa-solid fa-box-open text-3xl mb-3"></i>
        <p class="font-medium">No orders yet.</p>
        <a href="<?= url('') ?>" class="text-ignite font-semibold text-sm mt-2 inline-block">Start Shopping &rarr;</a>
      </div>
      <?php endif; ?>
    </div>

    <div>
      <h2 class="font-display font-semibold text-lg mb-4">Profile</h2>
      <div class="rounded-xl border border-slate-200 p-5 mb-6 text-sm">
        <p class="font-semibold text-slate-800"><?= h(trim($customer['first_name'] . ' ' . ($customer['last_name'] ?? ''))) ?></p>
        <p class="text-slate-500 mt-1"><?= h($customer['email']) ?></p>
      </div>

      <h2 class="font-display font-semibold text-lg mb-4">Change Password</h2>
      <?php if ($success): ?><div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-2.5 text-sm font-medium"><?= h($success) ?></div><?php endif; ?>
      <?php if ($errors): ?>
      <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 p-3 text-sm">
        <ul class="list-disc pl-4 space-y-0.5"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
      </div>
      <?php endif; ?>
      <form method="POST" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">
        <input type="password" name="current_password" placeholder="Current password" required class="input">
        <input type="password" name="new_password" placeholder="New password" required minlength="6" class="input">
        <input type="password" name="confirm_password" placeholder="Confirm new password" required minlength="6" class="input">
        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-ink hover:bg-slate-800 text-white font-display font-semibold uppercase tracking-wide text-xs px-6 py-3 rounded-full transition">Update Password</button>
      </form>
    </div>
  </div>
</main>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
