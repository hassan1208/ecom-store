<?php
// category.php — public category landing page (products catalog comes in a later step)
require_once __DIR__ . '/includes/config.php';

$slug = sanitize($_GET['slug'] ?? '');
$category = $slug ? fetch_one("SELECT * FROM categories WHERE slug=? AND status='active'", 's', $slug) : null;

if (!$category) {
    $nf_title = 'Category Not Found';
    $nf_message = 'This category may have been moved or is no longer available.';
    include __DIR__ . '/includes/not-found.php';
}

$parent   = $category['parent_id'] ? fetch_one("SELECT name, slug FROM categories WHERE id=?", 'i', $category['parent_id']) : null;
$children = fetch_all("SELECT * FROM categories WHERE parent_id=? AND status='active' ORDER BY nav_order ASC, name ASC", 'i', $category['id']);

$category_ids = array_merge([$category['id']], array_column($children, 'id'));
require_once __DIR__ . '/includes/product-listing.php';
$f = listing_params();
$f['cat'] = 0; // the category itself is the base filter here
$placeholders = implode(',', array_fill(0, count($category_ids), '?'));
$result = listing_query("p.category_id IN ($placeholders)", str_repeat('i', count($category_ids)), $category_ids, $f);
$base_url = url('category/' . $category['slug']);

$meta_title       = ($category['meta_title'] ?: ($category['name'] . ' — Wholesale & Custom | ' . setting('site_name'))) . ($result['page'] > 1 ? ' — Page ' . $result['page'] : '');
$meta_description = $category['meta_description']
    ?: $category['short_description']
    ?: meta_text($category['description'], 160)
    ?: ('Shop ' . $category['name'] . ' at ' . setting('site_name') . '. Bulk pricing and custom orders available.');
$og_image         = !empty($category['image']) ? UPLOAD_URL . $category['image'] : null;
listing_seo($base_url, $f, $result, $category['name']);
if ($category['canonical_url'] && !listing_is_filtered($f) && $result['page'] === 1) $canonical_url = $category['canonical_url'];

// Optional FAQ block (Admin → Categories → FAQ, one "Question? | Answer" per line)
$faqs = [];
foreach (preg_split('/\r?\n/', (string)($category['faq'] ?? '')) as $line) {
    if (strpos($line, '|') === false) continue;
    [$qq, $aa] = array_map('trim', explode('|', $line, 2));
    if ($qq !== '' && $aa !== '') $faqs[] = [$qq, $aa];
}

$page_schema[] = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => array_values(array_filter([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('')],
        $parent ? ['@type' => 'ListItem', 'position' => 2, 'name' => $parent['name'], 'item' => url('category/' . $parent['slug'])] : null,
        ['@type' => 'ListItem', 'position' => $parent ? 3 : 2, 'name' => $category['name'], 'item' => $base_url],
    ])),
];
$page_schema[] = ['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => $category['name'], 'url' => $base_url, 'description' => $meta_description, 'isPartOf' => ['@id' => SITE_URL . '/#website']];
if ($faqs) {
    $page_schema[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($x) => ['@type' => 'Question', 'name' => $x[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $x[1]]], $faqs)];
}

include __DIR__ . '/includes/site-header.php';
?>
<main id="main">
  <section class="relative bg-ink text-white overflow-hidden">
    <?php if ($category['image']): ?>
    <img src="<?= UPLOAD_URL . h($category['image']) ?>" alt="<?= h($category['image_alt'] ?: $category['name']) ?>" fetchpriority="high" width="1600" height="600" class="absolute inset-0 w-full h-full object-cover opacity-40">
    <?php endif; ?>
    <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/80 to-ink/30" aria-hidden="true"></div>
    <div class="absolute inset-0 bg-grid opacity-40" aria-hidden="true"></div>
    <div class="container-x relative py-16 sm:py-24">
      <nav class="text-xs text-white/50 mb-4" aria-label="Breadcrumb">
        <a href="<?= url('') ?>" class="hover:text-white">Home</a>
        <?php if ($parent): ?><i class="fa-solid fa-chevron-right text-[8px] mx-1.5 opacity-60"></i><a href="<?= url('category/' . $parent['slug']) ?>" class="hover:text-white"><?= h($parent['name']) ?></a><?php endif; ?>
        <i class="fa-solid fa-chevron-right text-[8px] mx-1.5 opacity-60"></i><span class="text-white/80" aria-current="page"><?= h($category['name']) ?></span>
      </nav>
      <h1 class="font-display font-bold uppercase text-4xl sm:text-6xl leading-none max-w-3xl"><?= h($category['name']) ?></h1>
      <?php if ($category['short_description']): ?><p class="text-white/70 mt-4 max-w-xl text-lg"><?= h($category['short_description']) ?></p><?php endif; ?>
    </div>
  </section>

  <?php if ($children): ?>
  <section class="container-x pt-12" aria-label="Sub-categories">
    <div class="flex gap-4 overflow-x-auto scrollbar-none pb-2">
      <?php foreach ($children as $c): ?>
      <a href="<?= url('category/' . $c['slug']) ?>" class="group shrink-0 w-40 sm:w-48">
        <div class="aspect-square rounded-2xl overflow-hidden bg-slate-200 mb-2 tilt-3d">
          <?php if ($c['image']): ?><img src="<?= UPLOAD_URL . h($c['image']) ?>" alt="<?= h($c['image_alt'] ?: $c['name']) ?>" loading="lazy" width="300" height="300" class="w-full h-full object-cover group-hover:scale-110 transition duration-700"><?php endif; ?>
        </div>
        <h2 class="font-display font-semibold uppercase text-sm group-hover:text-ignite transition"><?= h($c['name']) ?></h2>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <div class="container-x py-12"><?= render_listing($base_url, $f, $result, ['empty' => 'No products in this category yet']) ?></div>

  <?php if ($category['description'] || $faqs): ?>
  <section class="container-x pb-20 grid grid-cols-1 lg:grid-cols-2 gap-10">
    <?php if ($category['description']): ?>
    <div class="prose-site text-slate-600"><h2>About <?= h($category['name']) ?></h2><?= nl2br(h($category['description'])) ?></div>
    <?php endif; ?>
    <?php if ($faqs): ?>
    <div>
      <h2 class="font-display font-bold uppercase text-2xl mb-5">FAQs</h2>
      <div class="space-y-3">
        <?php foreach ($faqs as [$qq, $aa]): ?>
        <details class="card p-5 group"><summary class="font-semibold cursor-pointer list-none flex justify-between gap-4"><?= h($qq) ?><i class="fa-solid fa-plus text-ignite group-open:rotate-45 transition"></i></summary><p class="text-sm text-slate-600 mt-3"><?= h($aa) ?></p></details>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
