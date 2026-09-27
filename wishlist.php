<?php
// wishlist.php — logged-in customer's saved products
require_once __DIR__ . '/includes/config.php';
require_customer();

$products = fetch_all(
    "SELECT p.* FROM wishlists w JOIN products p ON p.id=w.product_id
     WHERE w.customer_id=? AND p.status='active' ORDER BY w.created_at DESC",
    'i', current_user_id()
);

$meta_title = 'My Wishlist | ' . setting('site_name');
$meta_robots = 'noindex, follow';
include __DIR__ . '/includes/site-header.php';
$hero_title = 'My Wishlist'; $hero_eyebrow = count($products) . ' saved'; $hero_crumbs = ['Account' => url('account'), 'Wishlist' => null];
include __DIR__ . '/includes/page-hero.php';
?>
<main id="main" class="container-x py-12">

  <?php if ($products): ?>
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-5 gap-y-10">
    <?php foreach ($products as $p): ?>
    <?= render_product_card($p) ?>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-400">
    <i class="fa-regular fa-heart text-3xl mb-3"></i>
    <p class="font-medium">Your wishlist is empty.</p>
    <a href="<?= url('shop') ?>" class="btn-primary btn-sm mt-4">Start shopping</a>
  </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
