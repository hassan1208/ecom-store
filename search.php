<?php
// search.php — site-wide product search (name, short description, tags, SKU)
require_once __DIR__ . '/includes/config.php';

$q = trim(sanitize($_GET['q'] ?? ''));
$products = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $products = fetch_all(
        "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS thumb,
                (SELECT alt_text FROM product_images WHERE product_id=p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS thumb_alt
         FROM products p
         WHERE p.status='active' AND (p.name LIKE ? OR p.short_description LIKE ? OR p.tags LIKE ? OR p.sku LIKE ?)
         ORDER BY p.name ASC",
        'ssss', $like, $like, $like, $like
    );
}

$meta_title = ($q !== '' ? 'Search results for "' . $q . '"' : 'Search') . ' | ' . setting('site_name');
$canonical_url = url('search') . ($q !== '' ? '?q=' . urlencode($q) : '');
$meta_robots = 'noindex, follow';

include __DIR__ . '/includes/site-header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
  <nav class="text-xs text-slate-400 mb-4" aria-label="Breadcrumb"><a href="<?= url('') ?>" class="hover:text-ignite">Home</a> / <span class="text-slate-600">Search</span></nav>
  <h1 class="font-display font-bold text-3xl mb-6"><?= $q !== '' ? 'Search results for &ldquo;' . h($q) . '&rdquo;' : 'Search Products' ?></h1>

  <form method="GET" action="<?= url('search') ?>" class="mb-10 max-w-lg">
    <div class="relative">
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search products..." autofocus
             class="w-full rounded-full border border-slate-300 pl-5 pr-12 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite">
      <button type="submit" class="absolute right-1.5 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-ignite hover:bg-ignite-dark text-white flex items-center justify-center transition">
        <i class="fa-solid fa-magnifying-glass text-sm"></i>
      </button>
    </div>
  </form>

  <?php if ($q === ''): ?>
  <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-400">
    <i class="fa-solid fa-magnifying-glass text-3xl mb-3"></i>
    <p class="font-medium">Type something above to search our products.</p>
  </div>
  <?php elseif ($products): ?>
  <p class="text-sm text-slate-400 mb-6"><?= count($products) ?> result<?= count($products) === 1 ? '' : 's' ?> found.</p>
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
    <?php foreach ($products as $p): ?>
    <a href="<?= url('product/' . $p['slug']) ?>" class="group block">
      <div class="aspect-square rounded-xl overflow-hidden bg-slate-100 mb-3 relative">
        <?php if ($p['thumb']): ?>
        <img src="<?= UPLOAD_URL . h($p['thumb']) ?>" alt="<?= h($p['thumb_alt'] ?: $p['name']) ?>" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-image text-2xl"></i></div>
        <?php endif; ?>
        <?php if ($p['is_featured']): ?><span class="absolute top-2 left-2 bg-ignite text-white text-[10px] font-bold px-2 py-1 rounded-full uppercase">Featured</span><?php endif; ?>
      </div>
      <h3 class="font-medium text-sm text-slate-800 group-hover:text-ignite transition truncate"><?= h($p['name']) ?></h3>
      <div class="text-sm font-semibold mt-1">
        <?php if ($p['sale_price']): ?>
        <?= format_price($p['sale_price']) ?> <span class="text-xs text-slate-400 line-through ml-1"><?= format_price($p['base_price']) ?></span>
        <?php else: ?>
        <?= format_price($p['base_price']) ?>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-400">
    <i class="fa-solid fa-box-open text-3xl mb-3"></i>
    <p class="font-medium">No products found for &ldquo;<?= h($q) ?>&rdquo;.</p>
    <p class="text-sm mt-1">Try a different keyword, or <a href="<?= url('contact') ?>" class="text-ignite font-semibold">contact us</a> for a bulk quote.</p>
  </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
