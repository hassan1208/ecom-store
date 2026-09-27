<?php
// blog.php — public blog listing
require_once __DIR__ . '/includes/config.php';

$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 9;
$offset = ($page_num - 1) * $per_page;

$total = (int)(fetch_one("SELECT COUNT(*) c FROM blog_posts WHERE status='published'")['c'] ?? 0);
$posts = fetch_all("SELECT * FROM blog_posts WHERE status='published' ORDER BY published_at DESC LIMIT $per_page OFFSET $offset");
$total_pages = max(1, (int)ceil($total / $per_page));

$meta_title       = 'Blog | ' . setting('site_name');
$meta_description = 'Latest news, tips and updates from ' . setting('site_name', '');

include __DIR__ . '/includes/site-header.php';
?>
<main>
  <section class="bg-ink py-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <p class="text-ignite font-display font-semibold uppercase tracking-[0.2em] text-xs mb-2">Blog</p>
      <h1 class="font-display font-bold text-white text-3xl sm:text-4xl">Latest Articles</h1>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-14">
    <?php if ($posts): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-7">
      <?php foreach ($posts as $p): ?>
      <a href="<?= url('blog/' . $p['slug']) ?>" class="group block">
        <div class="aspect-[1200/630] rounded-xl overflow-hidden bg-slate-100 mb-4">
          <?php if ($p['featured_image']): ?>
          <img src="<?= UPLOAD_URL . h($p['featured_image']) ?>" alt="<?= h($p['featured_image_alt'] ?: $p['title']) ?>" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
          <?php else: ?>
          <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-image text-3xl"></i></div>
          <?php endif; ?>
        </div>
        <p class="text-xs text-slate-400 mb-1.5"><?= $p['published_at'] ? format_date($p['published_at']) : format_date($p['created_at']) ?><?= $p['author'] ? ' · ' . h($p['author']) : '' ?></p>
        <h2 class="font-display font-bold text-lg text-ink group-hover:text-ignite transition mb-2"><?= h($p['title']) ?></h2>
        <?php if ($p['excerpt']): ?><p class="text-sm text-slate-500 leading-relaxed line-clamp-2"><?= h($p['excerpt']) ?></p><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="flex items-center justify-center gap-2 mt-14">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <a href="?page=<?= $i ?>" class="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-semibold transition <?= $i === $page_num ? 'bg-ignite text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="text-center py-20 text-slate-400">
      <i class="fa-solid fa-newspaper text-4xl mb-4"></i>
      <p>No articles published yet.</p>
    </div>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
