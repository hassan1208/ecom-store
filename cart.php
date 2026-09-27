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

$cross_sell = [];
if ($cart) {
    $ids = array_values(array_unique(array_map(fn($i) => (int)$i['product_id'], $cart)));
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $cross_sell = fetch_all("SELECT * FROM products WHERE status='active' AND id NOT IN ($ph) AND category_id IN (SELECT category_id FROM products WHERE id IN ($ph)) ORDER BY is_featured DESC, views DESC LIMIT 4", str_repeat('i', count($ids) * 2), ...$ids, ...$ids);
}
$shipping = (float)setting('shipping_cost', '0');

include __DIR__ . '/includes/site-header.php';
$hero_title = 'Your Cart'; $hero_eyebrow = cart_count() . ' item' . (cart_count() === 1 ? '' : 's'); $hero_crumbs = ['Cart' => null];
include __DIR__ . '/includes/page-hero.php';
$success = get_flash('success'); $error = get_flash('error');
?>
<main id="main" class="container-x py-12">
  <?php if ($success): ?><div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700 px-5 py-4 text-sm font-medium" role="status"><i class="fa-solid fa-circle-check mr-1"></i><?= h($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="mb-6 rounded-2xl border border-red-200 bg-red-50 text-red-700 px-5 py-4 text-sm font-medium" role="alert"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= h($error) ?></div><?php endif; ?>

  <?php if (empty($cart)): ?>
  <div class="card text-center py-20 px-6 max-w-2xl mx-auto">
    <div class="w-20 h-20 rounded-3xl bg-ignite/10 text-ignite flex items-center justify-center mx-auto mb-6 text-3xl"><i class="fa-solid fa-bag-shopping"></i></div>
    <h2 class="font-display font-bold uppercase text-2xl mb-2">Your cart is empty</h2>
    <p class="text-slate-500 mb-8">Find your gear — or design a custom kit in 3D.</p>
    <div class="flex flex-wrap justify-center gap-3">
      <a href="<?= url('shop') ?>" class="btn-primary btn-shine">Shop now <i class="fa-solid fa-arrow-right"></i></a>
      <a href="<?= url('kit-builder') ?>" class="btn-outline"><i class="fa-solid fa-cube"></i> Kit Builder</a>
    </div>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <form method="POST" class="lg:col-span-8 space-y-4" id="cartForm">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="key" value="">
      <?php foreach ($cart as $key => $item): $cz = $item['customization'] ?? null; $cz_thumb = $cz ? (($cz['render_front_path'] ?? '') ?: ($cz['preview_path'] ?? '')) : ''; ?>
      <article class="card p-4 sm:p-5 flex gap-4 sm:gap-5">
        <a href="<?= url('product/' . $item['slug']) ?>" class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-slate-100 overflow-hidden shrink-0">
          <?php if ($cz_thumb): ?><img src="<?= UPLOAD_URL . h($cz_thumb) ?>" alt="Your custom design" class="w-full h-full object-cover">
          <?php elseif ($item['image']): ?><img src="<?= UPLOAD_URL . h($item['image']) ?>" alt="<?= h($item['name']) ?>" class="w-full h-full object-cover"><?php endif; ?>
        </a>
        <div class="flex-1 min-w-0 flex flex-col">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <a href="<?= url('product/' . $item['slug']) ?>" class="font-display font-semibold uppercase leading-tight hover:text-ignite"><?= h($item['name']) ?></a>
              <?php if ($item['variation_key']): ?><p class="text-xs text-slate-500 mt-0.5">Option: <?= h($item['variation_key']) ?></p><?php endif; ?>
            </div>
            <button type="submit" name="action" value="remove" formnovalidate onclick="this.form.key.value=<?= h(json_encode($key)) ?>" class="w-9 h-9 rounded-full text-slate-400 hover:text-red-500 hover:bg-red-50 shrink-0" aria-label="Remove <?= h($item['name']) ?>"><i class="fa-solid fa-trash-can"></i></button>
          </div>
          <?php if ($cz): $back_text = trim(($cz['back_name'] ?? '') . ' ' . ($cz['back_number'] ?? '')); ?>
          <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px] font-semibold">
            <span class="inline-flex items-center gap-1 rounded-full bg-ignite/10 text-ignite px-2.5 py-1"><i class="fa-solid fa-wand-magic-sparkles"></i> Custom design</span>
            <?php if ($back_text !== ''): ?><span class="rounded-full bg-slate-100 text-slate-600 px-2.5 py-1">&ldquo;<?= h($back_text) ?>&rdquo;</span><?php endif; ?>
            <?php foreach (['base_color', 'sleeve_color', 'trim_color'] as $ck): if (!empty($cz[$ck])): ?><span class="w-4 h-4 rounded-full border border-black/10" style="background:<?= h($cz[$ck]) ?>" title="<?= h($cz[$ck]) ?>"></span><?php endif; endforeach; ?>
            <?php if (!empty($cz['team_order'])): ?><span class="rounded-full bg-slate-100 text-slate-600 px-2.5 py-1"><i class="fa-solid fa-users mr-1"></i>Team</span><?php endif; ?>
          </div>
          <?php endif; ?>
          <div class="mt-auto pt-3 flex items-center justify-between gap-3">
            <div class="flex items-center rounded-full border-2 border-slate-200 bg-white h-10">
              <button type="button" class="w-9 h-full" onclick="const i=this.nextElementSibling;i.value=Math.max(1,(+i.value||1)-1);cartChanged()" aria-label="Decrease">−</button>
              <input type="number" name="qty[<?= h($key) ?>]" value="<?= (int)$item['qty'] ?>" min="1" aria-label="Quantity" oninput="cartChanged()" class="w-10 text-center text-sm font-semibold bg-transparent focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
              <button type="button" class="w-9 h-full" onclick="const i=this.previousElementSibling;i.value=(+i.value||1)+1;cartChanged()" aria-label="Increase">+</button>
            </div>
            <div class="text-right">
              <p class="font-display font-bold text-lg leading-none"><?= format_price($item['price'] * $item['qty']) ?></p>
              <?php if ($item['qty'] > 1): ?><p class="text-xs text-slate-400"><?= format_price($item['price']) ?> each</p><?php endif; ?>
            </div>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
      <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
        <a href="<?= url('shop') ?>" class="link-arrow"><i class="fa-solid fa-arrow-left"></i> Continue shopping</a>
        <button type="submit" id="updateCartBtn" class="btn-outline btn-sm opacity-50" disabled><i class="fa-solid fa-rotate"></i> Update cart</button>
      </div>
    </form>

    <aside class="lg:col-span-4">
      <div class="card p-6 lg:sticky lg:top-40">
        <h2 class="font-display font-bold uppercase text-xl mb-5">Order summary</h2>
        <dl class="space-y-3 text-sm">
          <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-semibold"><?= format_price($total) ?></dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Shipping</dt><dd class="font-semibold"><?= $shipping > 0 ? format_price($shipping) : 'Free' ?></dd></div>
          <div class="flex justify-between text-xs text-slate-400"><dt>Coupons</dt><dd>Apply at checkout</dd></div>
        </dl>
        <div class="flex justify-between items-baseline border-t border-slate-100 mt-5 pt-5">
          <span class="font-semibold">Total</span>
          <span class="font-display font-bold text-3xl"><?= format_price($total + $shipping) ?></span>
        </div>
        <a href="<?= url('checkout') ?>" class="btn-primary btn-shine w-full mt-6">Checkout <i class="fa-solid fa-lock text-xs"></i></a>
        <ul class="mt-6 space-y-2 text-xs text-slate-500">
          <li><i class="fa-solid fa-shield-halved text-emerald-500 w-4"></i> Secure checkout</li>
          <li><i class="fa-solid fa-palette text-emerald-500 w-4"></i> Free design proof for custom items</li>
          <li><i class="fa-solid fa-boxes-stacked text-emerald-500 w-4"></i> Bulk pricing applied per product</li>
        </ul>
      </div>
    </aside>
  </div>

  <?php if ($cross_sell): ?>
  <section class="mt-20" aria-labelledby="xsell">
    <div class="section-head"><div><p class="eyebrow mb-3">Complete the kit</p><h2 id="xsell" class="section-title">You might also need</h2></div></div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-5 gap-y-10"><?php foreach ($cross_sell as $p) echo render_product_card($p); ?></div>
  </section>
  <?php endif; ?>
  <?php endif; ?>
</main>
<script>
function cartChanged() { const b = document.getElementById('updateCartBtn'); if (b) { b.disabled = false; b.classList.remove('opacity-50'); b.classList.replace('btn-outline', 'btn-dark'); } }
</script>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
