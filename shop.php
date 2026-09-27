<?php
// shop.php — all products, with filters / sort / pagination (served at /shop)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/product-listing.php';

$f = listing_params();
$result = listing_query('', '', [], $f);
$categories = fetch_all("SELECT id, name, slug FROM categories WHERE parent_id IS NULL AND status='active' ORDER BY nav_order ASC, name ASC");
$base_url = url('shop');

$meta_title = 'Shop All Sports Equipment' . ($result['page'] > 1 ? ' — Page ' . $result['page'] : '') . ' | ' . setting('site_name');
$meta_description = 'Browse every product from ' . setting('site_name') . ': ' . implode(', ', array_slice(array_column($categories, 'name'), 0, 5)) . '. Wholesale, bulk & custom team orders.';
listing_seo($base_url, $f, $result, 'All products');
$page_schema[] = [
    '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Shop', 'item' => $base_url],
    ],
];

include __DIR__ . '/includes/site-header.php';
?>
<main id="main">
  <section class="relative bg-ink text-white overflow-hidden">
    <div class="absolute inset-0 bg-grid opacity-50" aria-hidden="true"></div>
    <div class="absolute -right-32 -top-32 w-96 h-96 rounded-full bg-ignite/30 blur-[100px]" aria-hidden="true"></div>
    <div class="container-x relative py-14 sm:py-20">
      <nav class="text-xs text-white/50 mb-4" aria-label="Breadcrumb"><a href="<?= url('') ?>" class="hover:text-white">Home</a> <i class="fa-solid fa-chevron-right text-[8px] mx-1.5 opacity-60"></i> <span class="text-white/80" aria-current="page">Shop</span></nav>
      <h1 class="font-display font-bold uppercase text-4xl sm:text-6xl leading-none">Shop all</h1>
      <p class="text-white/60 mt-3 max-w-xl">Match-grade gear made in Sialkot — every product can be ordered in bulk, most can be customised in 3D.</p>
      <div class="flex flex-wrap gap-2 mt-8">
        <?php foreach ($categories as $c): ?><a href="<?= url('category/' . $c['slug']) ?>" class="rounded-full border border-white/20 px-4 py-2 text-xs font-semibold uppercase tracking-wider hover:bg-white hover:text-ink transition"><?= h($c['name']) ?></a><?php endforeach; ?>
      </div>
    </div>
  </section>
  <div class="container-x py-12"><?= render_listing($base_url, $f, $result, ['categories' => $categories]) ?></div>
</main>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
