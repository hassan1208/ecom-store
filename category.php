<?php
// category.php — public category landing page (products catalog comes in a later step)
require_once __DIR__ . '/includes/config.php';

$slug = sanitize($_GET['slug'] ?? '');
$category = $slug ? fetch_one("SELECT * FROM categories WHERE slug=? AND status='active'", 's', $slug) : null;

if (!$category) {
    http_response_code(404);
    $meta_title = 'Category Not Found | ' . setting('site_name');
    include __DIR__ . '/includes/site-header.php';
    echo '<main class="max-w-3xl mx-auto px-4 py-24 text-center">
            <h1 class="font-display font-bold text-3xl mb-3">Category Not Found</h1>
            <p class="text-slate-500 mb-6">This category may have been moved or is no longer available.</p>
            <a href="' . url('') . '" class="text-ignite font-semibold">&larr; Back to Home</a>
          </main>';
    include __DIR__ . '/includes/site-footer.php';
    exit;
}

$parent   = $category['parent_id'] ? fetch_one("SELECT name, slug FROM categories WHERE id=?", 'i', $category['parent_id']) : null;
$children = fetch_all("SELECT * FROM categories WHERE parent_id=? AND status='active' ORDER BY nav_order ASC, name ASC", 'i', $category['id']);

$category_ids = array_merge([$category['id']], array_column($children, 'id'));
$placeholders = implode(',', array_fill(0, count($category_ids), '?'));
$products = fetch_all(
    "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS thumb,
            (SELECT alt_text FROM product_images WHERE product_id=p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS thumb_alt
     FROM products p WHERE p.category_id IN ($placeholders) AND p.status='active' ORDER BY p.created_at DESC",
    str_repeat('i', count($category_ids)), ...$category_ids
);

$meta_title       = $category['meta_title'] ?: ($category['name'] . ' | ' . setting('site_name'));
$meta_description = $category['meta_description']
    ?: $category['short_description']
    ?: mb_substr(trim(strip_tags((string)$category['description'])), 0, 160)
    ?: ('Shop ' . $category['name'] . ' at ' . setting('site_name') . '.');
$canonical_url     = $category['canonical_url'] ?: url('category/' . $category['slug']);
$og_image           = !empty($category['image']) ? UPLOAD_URL . $category['image'] : null;

include __DIR__ . '/includes/site-header.php';
?>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => array_values(array_filter([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('')],
        $parent ? ['@type' => 'ListItem', 'position' => 2, 'name' => $parent['name'], 'item' => url('category/' . $parent['slug'])] : null,
        ['@type' => 'ListItem', 'position' => $parent ? 3 : 2, 'name' => $category['name']],
    ])),
], JSON_UNESCAPED_SLASHES) ?>
</script>

<main>
  <section class="relative <?= $category['image'] ? 'h-72' : 'h-40' ?> bg-ink flex items-end overflow-hidden">
    <?php if ($category['image']): ?>
    <img src="<?= UPLOAD_URL . h($category['image']) ?>" alt="<?= h($category['image_alt'] ?: $category['name']) ?>" class="absolute inset-0 w-full h-full object-cover opacity-50">
    <?php endif; ?>
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 pb-8 w-full">
      <nav class="text-xs text-white/50 mb-3" aria-label="Breadcrumb">
        <a href="<?= url('') ?>" class="hover:text-white">Home</a>
        <?php if ($parent): ?> / <a href="<?= url('category/' . $parent['slug']) ?>" class="hover:text-white"><?= h($parent['name']) ?></a><?php endif; ?>
        / <span class="text-white/80"><?= h($category['name']) ?></span>
      </nav>
      <h1 class="font-display font-bold text-white text-3xl sm:text-4xl"><?= h($category['name']) ?></h1>
      <?php if ($category['short_description']): ?><p class="text-white/60 mt-2 max-w-xl"><?= h($category['short_description']) ?></p><?php endif; ?>
    </div>
  </section>

  <?php if ($children): ?>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-14">
    <h2 class="font-display font-bold text-xl mb-6">Browse <?= h($category['name']) ?></h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
      <?php foreach ($children as $c): ?>
      <a href="<?= url('category/' . $c['slug']) ?>" class="group relative rounded-xl overflow-hidden aspect-[4/5] bg-slate-100 block">
        <?php if ($c['image']): ?>
        <img src="<?= UPLOAD_URL . h($c['image']) ?>" alt="<?= h($c['image_alt'] ?: $c['name']) ?>" loading="lazy" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition duration-500">
        <?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-t from-ink/70 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 p-3">
          <h3 class="font-display font-semibold text-white text-sm"><?= h($c['name']) ?></h3>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-6 pb-20">
    <?php if ($products): ?>
    <h2 class="font-display font-bold text-xl mb-6">Products</h2>
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
      <p class="font-medium">No products in this category yet.</p>
    </div>
    <?php endif; ?>
    <?php if ($category['description']): ?>
    <div class="prose prose-slate max-w-none mt-10 text-slate-600 leading-relaxed">
      <?= nl2br(h($category['description'])) ?>
    </div>
    <?php endif; ?>
  </section>
</main>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
