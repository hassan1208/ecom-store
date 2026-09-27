<?php
// includes/not-found.php — branded 404 page (proper 404 status, noindex).
// Optional before include: $nf_title, $nf_message, $nf_back_url, $nf_back_label.
http_response_code(404);
$meta_title       = ($nf_title ?? 'Page Not Found') . ' | ' . setting('site_name');
$meta_description = $nf_message ?? 'The page you were looking for could not be found.';
$meta_robots      = 'noindex, follow';
$canonical_url    = url('');
$nf_popular       = fetch_all("SELECT * FROM products WHERE status='active' ORDER BY is_featured DESC, views DESC LIMIT 4");
include __DIR__ . '/site-header.php';
?>
<main id="main" class="container-x py-20">
  <div class="relative text-center max-w-2xl mx-auto">
    <p class="font-poster text-[140px] sm:text-[200px] leading-none text-stroke select-none" style="--stroke: rgb(11 15 20 / .15)" aria-hidden="true">404</p>
    <h1 class="section-title -mt-10 sm:-mt-16 mb-4"><?= h($nf_title ?? 'Page Not Found') ?></h1>
    <p class="text-slate-500 mb-8"><?= h($nf_message ?? 'This page may have been moved or is no longer available.') ?></p>
    <form method="GET" action="<?= url('search') ?>" class="flex gap-2 max-w-md mx-auto mb-6" role="search">
      <input type="search" name="q" placeholder="Search products…" aria-label="Search products" class="input !rounded-full flex-1">
      <button class="btn-primary !px-5" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
    <div class="flex flex-wrap justify-center gap-3">
      <a href="<?= h($nf_back_url ?? url('')) ?>" class="btn-dark btn-sm"><i class="fa-solid fa-arrow-left"></i> <?= h($nf_back_label ?? 'Back to home') ?></a>
      <a href="<?= url('shop') ?>" class="btn-outline btn-sm">Browse the shop</a>
    </div>
  </div>
  <?php if ($nf_popular): ?>
  <section class="mt-20" aria-labelledby="nf-popular">
    <h2 id="nf-popular" class="font-display font-bold uppercase text-2xl mb-6">Popular right now</h2>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-5 gap-y-10"><?php foreach ($nf_popular as $p) echo render_product_card($p); ?></div>
  </section>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/site-footer.php'; exit;
