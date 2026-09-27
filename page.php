<?php
// page.php — public renderer for static content pages (About, Privacy Policy, etc.)
require_once __DIR__ . '/includes/config.php';

$slug = sanitize($_GET['slug'] ?? '');
$page = $slug ? fetch_one("SELECT * FROM pages WHERE slug=? AND status='published'", 's', $slug) : null;

if (!$page) {
    $nf_title = 'Page Not Found';
    $nf_message = 'This page may have been moved or is no longer available.';
    include __DIR__ . '/includes/not-found.php';
}

$meta_title       = $page['meta_title'] ?: ($page['title'] . ' | ' . setting('site_name'));
$meta_description = $page['meta_description'] ?: meta_text($page['content'], 160);
$canonical_url    = url($page['slug']);
$og_image         = !empty($page['banner_image']) ? UPLOAD_URL . $page['banner_image'] : null;
$meta_description = meta_text($meta_description, 160);

// Page type for structured data, plus automatic FAQPage markup when the page
// content is written as question headings (<h2>/<h3> ending in "?") followed
// by answer paragraphs — e.g. the FAQs page.
$page_type = preg_match('/about/i', $page['slug']) ? 'AboutPage' : (preg_match('/contact/i', $page['slug']) ? 'ContactPage' : 'WebPage');
$page_schema = [
    ['@context' => 'https://schema.org', '@type' => $page_type, 'name' => $page['title'], 'url' => $canonical_url, 'description' => $meta_description, 'isPartOf' => ['@id' => SITE_URL . '/#website'], 'dateModified' => date('c', strtotime($page['updated_at'] ?? 'now'))],
    ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $page['title'], 'item' => $canonical_url],
    ]],
];
if (preg_match_all('#<h[23][^>]*>(.*?\?)\s*</h[23]>(.*?)(?=<h[23]|$)#is', (string)$page['content'], $m, PREG_SET_ORDER) && count($m) >= 2) {
    $page_schema[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($x) => [
        '@type' => 'Question', 'name' => meta_text($x[1], 300),
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => meta_text($x[2], 1000)],
    ], $m)];
}

include __DIR__ . '/includes/site-header.php';
?>
<main id="main">
  <?php if (!empty($page['banner_image'])): ?>
  <section class="relative h-64 sm:h-80 overflow-hidden bg-ink flex items-end">
    <img src="<?= UPLOAD_URL . h($page['banner_image']) ?>" alt="<?= h($page['banner_image_alt'] ?: $page['title']) ?>" class="absolute inset-0 w-full h-full object-cover opacity-60">
    <div class="absolute inset-0 bg-gradient-to-t from-ink/80 to-transparent"></div>
    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 pb-8 w-full">
      <nav class="text-xs text-white/50 mb-3" aria-label="Breadcrumb"><a href="<?= url('') ?>" class="hover:text-white">Home</a> / <span class="text-white/80"><?= h($page['title']) ?></span></nav>
      <h1 class="font-display font-bold text-white text-3xl sm:text-4xl"><?= h($page['title']) ?></h1>
    </div>
  </section>
  <?php else: ?>
  <section class="bg-ink py-14">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
      <nav class="text-xs text-white/50 mb-3" aria-label="Breadcrumb"><a href="<?= url('') ?>" class="hover:text-white">Home</a> / <span class="text-white/80"><?= h($page['title']) ?></span></nav>
      <h1 class="font-display font-bold text-white text-3xl sm:text-4xl"><?= h($page['title']) ?></h1>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($page['side_image'])): ?>
  <section class="max-w-5xl mx-auto px-4 sm:px-6 py-14 grid grid-cols-1 md:grid-cols-5 gap-10 items-start">
    <div class="md:col-span-2">
      <img src="<?= UPLOAD_URL . h($page['side_image']) ?>" alt="<?= h($page['side_image_alt'] ?: $page['title']) ?>" class="w-full rounded-2xl object-cover aspect-[4/5]">
    </div>
    <div class="md:col-span-3 prose-site text-slate-600 leading-relaxed">
      <?= $page['content'] ?: '<p class="text-slate-400">This page has no content yet.</p>' ?>
    </div>
  </section>
  <?php else: ?>
  <section class="max-w-3xl mx-auto px-4 sm:px-6 py-14">
    <div class="prose-site text-slate-600 leading-relaxed"><?= $page['content'] ?: '<p class="text-slate-400">This page has no content yet.</p>' ?></div>
  </section>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
