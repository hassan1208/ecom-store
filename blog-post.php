<?php
// blog-post.php — public single blog post
require_once __DIR__ . '/includes/config.php';

$slug = sanitize($_GET['slug'] ?? '');
$post = $slug ? fetch_one("SELECT * FROM blog_posts WHERE slug=? AND status='published'", 's', $slug) : null;

if (!$post) {
    http_response_code(404);
    $meta_title = 'Post Not Found | ' . setting('site_name');
    include __DIR__ . '/includes/site-header.php';
    echo '<main class="max-w-3xl mx-auto px-4 py-24 text-center">
            <h1 class="font-display font-bold text-3xl mb-3">Post Not Found</h1>
            <p class="text-slate-500 mb-6">This article may have been moved or is no longer available.</p>
            <a href="' . url('blog') . '" class="text-ignite font-semibold">&larr; Back to Blog</a>
          </main>';
    include __DIR__ . '/includes/site-footer.php';
    exit;
}

track_blog_view($post['id']);

$related = fetch_all("SELECT * FROM blog_posts WHERE status='published' AND id<>? ORDER BY published_at DESC LIMIT 3", 'i', $post['id']);

$meta_title       = $post['meta_title'] ?: ($post['title'] . ' | ' . setting('site_name'));
$meta_description = $post['meta_description']
    ?: $post['excerpt']
    ?: mb_substr(trim(strip_tags((string)$post['content'])), 0, 160);
$canonical_url    = url('blog/' . $post['slug']);
$og_image         = !empty($post['featured_image']) ? UPLOAD_URL . $post['featured_image'] : null;

include __DIR__ . '/includes/site-header.php';
?>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post['title'],
    'image' => $og_image ? [$og_image] : [],
    'author' => ['@type' => 'Organization', 'name' => $post['author'] ?: setting('site_name')],
    'datePublished' => $post['published_at'] ?: $post['created_at'],
    'dateModified' => $post['updated_at'],
], JSON_UNESCAPED_SLASHES) ?>
</script>

<main>
  <?php if ($post['featured_image']): ?>
  <section class="relative h-72 sm:h-96 overflow-hidden bg-ink flex items-end">
    <img src="<?= UPLOAD_URL . h($post['featured_image']) ?>" alt="<?= h($post['featured_image_alt'] ?: $post['title']) ?>" class="absolute inset-0 w-full h-full object-cover opacity-60">
    <div class="absolute inset-0 bg-gradient-to-t from-ink/85 to-transparent"></div>
    <div class="relative z-10 max-w-3xl mx-auto px-4 sm:px-6 pb-10 w-full">
      <nav class="text-xs text-white/50 mb-3" aria-label="Breadcrumb"><a href="<?= url('blog') ?>" class="hover:text-white">Blog</a> / <span class="text-white/80">Article</span></nav>
      <h1 class="font-display font-bold text-white text-3xl sm:text-4xl mb-3"><?= h($post['title']) ?></h1>
      <p class="text-white/60 text-sm"><?= $post['published_at'] ? format_date($post['published_at']) : format_date($post['created_at']) ?><?= $post['author'] ? ' · ' . h($post['author']) : '' ?></p>
    </div>
  </section>
  <?php else: ?>
  <section class="bg-ink py-14">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
      <nav class="text-xs text-white/50 mb-3" aria-label="Breadcrumb"><a href="<?= url('blog') ?>" class="hover:text-white">Blog</a> / <span class="text-white/80">Article</span></nav>
      <h1 class="font-display font-bold text-white text-3xl sm:text-4xl mb-3"><?= h($post['title']) ?></h1>
      <p class="text-white/60 text-sm"><?= $post['published_at'] ? format_date($post['published_at']) : format_date($post['created_at']) ?><?= $post['author'] ? ' · ' . h($post['author']) : '' ?></p>
    </div>
  </section>
  <?php endif; ?>

  <section class="max-w-3xl mx-auto px-4 sm:px-6 py-14">
    <div class="prose prose-slate max-w-none text-slate-600 leading-relaxed"><?= $post['content'] ?: '<p class="text-slate-400">This post has no content yet.</p>' ?></div>
  </section>

  <?php if ($related): ?>
  <section class="bg-slate-50 border-t border-black/5 py-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <h2 class="font-display font-bold text-xl mb-8">More Articles</h2>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <?php foreach ($related as $r): ?>
        <a href="<?= url('blog/' . $r['slug']) ?>" class="group block">
          <div class="aspect-[1200/630] rounded-xl overflow-hidden bg-slate-100 mb-3">
            <?php if ($r['featured_image']): ?>
            <img src="<?= UPLOAD_URL . h($r['featured_image']) ?>" alt="<?= h($r['featured_image_alt'] ?: $r['title']) ?>" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-image text-2xl"></i></div>
            <?php endif; ?>
          </div>
          <h3 class="font-medium text-sm text-slate-800 group-hover:text-ignite transition"><?= h($r['title']) ?></h3>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
