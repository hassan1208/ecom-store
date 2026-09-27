<?php
// page.php — public renderer for static content pages (About, Privacy Policy, etc.)
require_once __DIR__ . '/includes/config.php';

$slug = sanitize($_GET['slug'] ?? '');
$page = $slug ? fetch_one("SELECT * FROM pages WHERE slug=? AND status='published'", 's', $slug) : null;

if (!$page) {
    http_response_code(404);
    $meta_title = 'Page Not Found | ' . setting('site_name');
    include __DIR__ . '/includes/site-header.php';
    echo '<main class="max-w-3xl mx-auto px-4 py-24 text-center">
            <h1 class="font-display font-bold text-3xl mb-3">Page Not Found</h1>
            <p class="text-slate-500 mb-6">This page may have been moved or is no longer available.</p>
            <a href="' . url('') . '" class="text-ignite font-semibold">&larr; Back to Home</a>
          </main>';
    include __DIR__ . '/includes/site-footer.php';
    exit;
}

$meta_title       = $page['meta_title'] ?: ($page['title'] . ' | ' . setting('site_name'));
$meta_description = $page['meta_description'] ?: mb_substr(trim(strip_tags((string)$page['content'])), 0, 160);
$canonical_url    = url($page['slug']);
$og_image         = !empty($page['banner_image']) ? UPLOAD_URL . $page['banner_image'] : null;

include __DIR__ . '/includes/site-header.php';
?>
<main>
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
    <div class="md:col-span-3 prose prose-slate max-w-none text-slate-600 leading-relaxed">
      <?= $page['content'] ?: '<p class="text-slate-400">This page has no content yet.</p>' ?>
    </div>
  </section>
  <?php else: ?>
  <section class="max-w-3xl mx-auto px-4 sm:px-6 py-14">
    <div class="prose prose-slate max-w-none text-slate-600 leading-relaxed"><?= $page['content'] ?: '<p class="text-slate-400">This page has no content yet.</p>' ?></div>
  </section>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
