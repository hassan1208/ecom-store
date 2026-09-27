<?php
// search.php — site-wide product search (name, short description, tags, SKU,
// category). ?suggest=1 returns JSON for the header's live suggestions.
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/product-listing.php';

$q = mb_substr(trim(sanitize($_GET['q'] ?? '')), 0, 80);
$like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
$search_where = "(p.name LIKE ? OR p.short_description LIKE ? OR p.tags LIKE ? OR p.sku LIKE ? OR p.category_id IN (SELECT id FROM categories WHERE name LIKE ?))";

if (!empty($_GET['suggest'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: private, max-age=60');
    if (mb_strlen($q) < 2) { echo json_encode(['products' => [], 'categories' => []]); exit; }
    $rows = fetch_all(
        "SELECT p.name, p.slug, p.base_price, p.sale_price, c.name AS cat,
                (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS thumb
         FROM products p LEFT JOIN categories c ON c.id=p.category_id
         WHERE p.status='active' AND $search_where
         ORDER BY (p.name LIKE ?) DESC, p.is_featured DESC, p.views DESC LIMIT 6",
        'ssssss', $like, $like, $like, $like, $like, $q . '%'
    );
    $cats = fetch_all("SELECT name, slug FROM categories WHERE status='active' AND name LIKE ? ORDER BY name LIMIT 4", 's', $like);
    echo json_encode([
        'products' => array_map(fn($r) => [
            'name' => $r['name'], 'url' => url('product/' . $r['slug']), 'category' => $r['cat'],
            'price' => format_price($r['sale_price'] ?: $r['base_price']),
            'image' => $r['thumb'] ? UPLOAD_URL . $r['thumb'] : null,
        ], $rows),
        'categories' => array_map(fn($c) => ['name' => $c['name'], 'url' => url('category/' . $c['slug'])], $cats),
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

$f = listing_params();
$result = $q !== '' ? listing_query($search_where, 'sssss', [$like, $like, $like, $like, $like], $f) : ['items' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
$base_url = url('search');

$meta_title    = ($q !== '' ? 'Search results for "' . $q . '"' : 'Search') . ' | ' . setting('site_name');
$canonical_url = url('search');
$meta_robots   = 'noindex, follow'; // internal search result pages should never be indexed

include __DIR__ . '/includes/site-header.php';
?>
<main id="main">
  <section class="bg-ink text-white">
    <div class="container-x py-12 sm:py-16">
      <p class="eyebrow mb-3">Search</p>
      <h1 class="font-display font-bold uppercase text-3xl sm:text-5xl leading-none mb-8"><?= $q !== '' ? 'Results for &ldquo;' . h($q) . '&rdquo;' : 'Find your gear' ?></h1>
      <form method="GET" action="<?= url('search') ?>" class="max-w-2xl relative" role="search">
        <label for="searchPageQ" class="sr-only">Search products</label>
        <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input id="searchPageQ" type="search" name="q" value="<?= h($q) ?>" placeholder="Search products, categories, SKUs…" <?= $q === '' ? 'autofocus' : '' ?> class="w-full rounded-full bg-white text-ink pl-12 pr-32 py-4 font-medium focus:outline-none focus:ring-4 focus:ring-ignite/40">
        <button type="submit" class="btn-primary btn-sm absolute right-2 top-1/2 -translate-y-1/2">Search</button>
      </form>
    </div>
  </section>
  <div class="container-x py-12">
    <?php if ($q === ''): ?>
    <div class="text-center py-10">
      <p class="text-slate-500 mb-6">Popular searches</p>
      <div class="flex flex-wrap justify-center gap-2">
        <?php foreach (['Football', 'Boxing gloves', 'Jersey', 'Cricket', 'Gym', 'Cycling'] as $s): ?><a href="<?= url('search') ?>?q=<?= urlencode($s) ?>" class="chip"><?= h($s) ?></a><?php endforeach; ?>
      </div>
    </div>
    <?php else: ?>
    <?= render_listing($base_url, $f, $result, ['empty' => 'No products found for "' . $q . '"']) ?>
    <?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
