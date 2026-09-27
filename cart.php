<?php
// cart.php — public shopping cart
require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'update') {
        foreach ($_POST['qty'] ?? [] as $key => $qty) update_cart_qty($key, (int)$qty);
        set_flash('success', 'Cart updated.');
    } elseif ($action === 'remove') {
        remove_from_cart($_POST['key'] ?? '');
        set_flash('success', 'Item removed.');
    }
    header('Location: ' . url('cart'));
    exit;
}

$cart  = get_cart();
$total = cart_total();
$meta_title = 'Your Cart | ' . setting('site_name');
$meta_robots = 'noindex, nofollow';

include __DIR__ . '/includes/site-header.php';
?>
<main id="main" class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
  <h1 class="font-display font-bold text-3xl mb-8">Your Cart</h1>

  <?php $success = get_flash('success'); $error = get_flash('error'); ?>
  <?php if ($success): ?><div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3 text-sm"><?= h($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm"><?= h($error) ?></div><?php endif; ?>

  <?php if (empty($cart)): ?>
  <div class="text-center py-20">
    <i class="fa-solid fa-cart-shopping text-4xl text-slate-200 mb-4"></i>
    <p class="text-slate-500 mb-6">Your cart is empty.</p>
    <a href="<?= url('') ?>" class="inline-flex items-center gap-2 bg-ignite hover:bg-ignite-dark text-white font-display font-semibold uppercase tracking-wide text-sm px-7 py-3.5 rounded-full transition">Continue Shopping</a>
  </div>
  <?php else: ?>
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="update">
    <div class="rounded-2xl border border-slate-200 overflow-hidden mb-8">
      <?php foreach ($cart as $key => $item): ?>
      <div class="flex items-center gap-4 p-4 border-b border-slate-100 last:border-0">
        <div class="w-16 h-16 rounded-lg bg-slate-100 overflow-hidden shrink-0">
          <?php $cz_thumb = $item['customization']['render_front_path'] ?? '' ?: ($item['customization']['preview_path'] ?? ''); if ($cz_thumb): ?>
          <img src="<?= UPLOAD_URL . h($cz_thumb) ?>" alt="Your custom design" class="w-full h-full object-cover">
          <?php elseif ($item['image']): ?>
          <img src="<?= UPLOAD_URL . h($item['image']) ?>" alt="<?= h($item['name']) ?>" class="w-full h-full object-cover">
          <?php endif; ?>
        </div>
        <div class="flex-1 min-w-0">
          <a href="<?= url('product/' . $item['slug']) ?>" class="font-semibold text-sm text-slate-800 hover:text-ignite"><?= h($item['name']) ?></a>
          <?php if ($item['variation_key']): ?><div class="text-xs text-slate-400"><?= h($item['variation_key']) ?></div><?php endif; ?>
          <?php if (!empty($item['customization'])): $cz = $item['customization']; $back_text = trim(($cz['back_name'] ?? '') . ' ' . ($cz['back_number'] ?? '')); ?>
          <div class="text-xs text-indigo-600 font-semibold mt-0.5">
            <i class="fa-solid fa-wand-magic-sparkles mr-1"></i>Custom Design<?= $back_text !== '' ? ': "' . h($back_text) . '"' : '' ?>
          </div>
          <?php endif; ?>
          <div class="text-sm font-semibold text-slate-600 mt-1"><?= format_price($item['price']) ?></div>
        </div>
        <input type="number" name="qty[<?= h($key) ?>]" value="<?= (int)$item['qty'] ?>" min="1" class="w-16 rounded-lg border border-slate-300 px-2 py-2 text-sm text-center">
        <div class="w-24 text-right font-semibold text-sm"><?= format_price($item['price'] * $item['qty']) ?></div>
        <button type="submit" formaction="<?= url('cart') ?>" formnovalidate name="action" value="remove" onclick="this.form.key.value='<?= h($key) ?>'" class="text-slate-300 hover:text-red-500 shrink-0"><i class="fa-solid fa-trash"></i></button>
      </div>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="key" value="">

    <div class="flex items-center justify-between mb-8">
      <button type="submit" class="inline-flex items-center gap-2 border border-slate-300 text-slate-600 hover:bg-slate-50 font-semibold text-sm px-5 py-2.5 rounded-full transition">
        <i class="fa-solid fa-rotate"></i> Update Cart
      </button>
      <div class="text-right">
        <div class="text-sm text-slate-400">Subtotal</div>
        <div class="text-2xl font-bold text-ink"><?= format_price($total) ?></div>
      </div>
    </div>
  </form>

  <div class="text-right">
    <a href="<?= url('checkout') ?>" class="inline-flex items-center gap-2 bg-ignite hover:bg-ignite-dark text-white font-display font-semibold uppercase tracking-wide text-sm px-8 py-3.5 rounded-full transition">
      Proceed to Checkout <i class="fa-solid fa-arrow-right"></i>
    </a>
  </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
