<?php
// admin/index.php - Dashboard
require_once __DIR__ . '/../includes/config.php';
require_admin();

$page_title = 'Dashboard';

$total_categories  = (int)(fetch_one("SELECT COUNT(*) c FROM categories")['c'] ?? 0);
$active_categories = (int)(fetch_one("SELECT COUNT(*) c FROM categories WHERE status='active'")['c'] ?? 0);
$seo_ok_categories  = (int)(fetch_one("SELECT COUNT(*) c FROM categories WHERE meta_title <> '' AND meta_description <> ''")['c'] ?? 0);
$banners_active     = (int)(fetch_one("SELECT COUNT(*) c FROM banners WHERE status='active'")['c'] ?? 0);
$menu_items_active  = (int)(fetch_one("SELECT COUNT(*) c FROM menu_items WHERE status='active'")['c'] ?? 0);
$footer_links_count = (int)(fetch_one("SELECT COUNT(*) c FROM footer_links")['c'] ?? 0);

$seo_score = $total_categories > 0 ? round(($seo_ok_categories / $total_categories) * 100) : 0;
$incomplete_categories = fetch_all("SELECT id, name FROM categories WHERE meta_title = '' OR meta_description = '' LIMIT 5");

$s = get_settings();
$checklist = [
    ['label' => 'Add a category',        'done' => $total_categories > 0,        'href' => ADMIN_URL . '/pages/categories.php'],
    ['label' => 'Upload a hero banner',   'done' => $banners_active > 0,          'href' => ADMIN_URL . '/pages/banners.php'],
    ['label' => 'Build the navbar menu',  'done' => $menu_items_active > 0,       'href' => ADMIN_URL . '/pages/menu.php'],
    ['label' => 'Set up footer links',    'done' => $footer_links_count > 0,      'href' => ADMIN_URL . '/pages/footer.php'],
    ['label' => 'Upload logo & contact info', 'done' => !empty($s['site_logo']) && !empty($s['contact_phone']), 'href' => ADMIN_URL . '/pages/settings.php'],
];
$checklist_done = count(array_filter($checklist, fn($i) => $i['done']));

include __DIR__ . '/includes/admin-header.php';
?>

<!-- Welcome banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-ink via-ink to-brand-dark p-7 mb-6">
  <div class="absolute -right-10 -top-10 w-56 h-56 rounded-full bg-brand/20 blur-2xl"></div>
  <div class="absolute right-24 bottom-0 w-32 h-32 rounded-full bg-emerald-400/10 blur-2xl"></div>
  <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <p class="text-emerald-300 text-xs font-semibold uppercase tracking-wider mb-1"><?= date('l, F j, Y') ?></p>
      <h1 class="text-white text-2xl font-extrabold">Welcome back, <?= h($_SESSION['user_name'] ?? 'Admin') ?> 👋</h1>
      <p class="text-slate-300 text-sm mt-1">Here's what's happening with your store today.</p>
    </div>
    <a href="<?= SITE_URL ?>/" target="_blank" class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white text-sm font-semibold px-4 py-2.5 rounded-lg backdrop-blur transition shrink-0">
      <i class="fa-solid fa-arrow-up-right-from-square"></i> View Live Site
    </a>
  </div>
</div>

<!-- Stat cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="card p-5 relative overflow-hidden">
    <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-emerald-50"></div>
    <div class="relative flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white flex items-center justify-center text-lg shadow-lg shadow-emerald-200"><i class="fa-solid fa-sitemap"></i></div>
      <div>
        <div class="text-2xl font-extrabold text-slate-800"><?= $total_categories ?></div>
        <div class="text-xs text-slate-500 font-medium">Total Categories</div>
      </div>
    </div>
  </div>
  <div class="card p-5 relative overflow-hidden">
    <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-sky-50"></div>
    <div class="relative flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-400 to-sky-600 text-white flex items-center justify-center text-lg shadow-lg shadow-sky-200"><i class="fa-solid fa-circle-check"></i></div>
      <div>
        <div class="text-2xl font-extrabold text-slate-800"><?= $active_categories ?></div>
        <div class="text-xs text-slate-500 font-medium">Active Categories</div>
      </div>
    </div>
  </div>
  <div class="card p-5 relative overflow-hidden">
    <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-amber-50"></div>
    <div class="relative flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-white flex items-center justify-center text-lg shadow-lg shadow-amber-200"><i class="fa-solid fa-images"></i></div>
      <div>
        <div class="text-2xl font-extrabold text-slate-800"><?= $banners_active ?></div>
        <div class="text-xs text-slate-500 font-medium">Active Banners</div>
      </div>
    </div>
  </div>
  <div class="card p-5 relative overflow-hidden">
    <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-violet-50"></div>
    <div class="relative flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-400 to-violet-600 text-white flex items-center justify-center text-lg shadow-lg shadow-violet-200"><i class="fa-solid fa-bars"></i></div>
      <div>
        <div class="text-2xl font-extrabold text-slate-800"><?= $menu_items_active ?></div>
        <div class="text-xs text-slate-500 font-medium">Menu Items</div>
      </div>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-6">
  <!-- Homepage setup checklist -->
  <div class="lg:col-span-3 card p-6">
    <div class="flex items-center justify-between mb-1">
      <h2 class="font-bold text-slate-800">Homepage Setup</h2>
      <span class="text-xs font-semibold text-slate-400"><?= $checklist_done ?>/<?= count($checklist) ?> complete</span>
    </div>
    <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden mb-5">
      <div class="h-full bg-brand rounded-full transition-all" style="width:<?= count($checklist) ? round($checklist_done / count($checklist) * 100) : 0 ?>%"></div>
    </div>
    <div class="space-y-1">
      <?php foreach ($checklist as $item): ?>
      <a href="<?= $item['href'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-50 transition group">
        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs shrink-0 <?= $item['done'] ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-300' ?>">
          <i class="fa-solid <?= $item['done'] ? 'fa-check' : 'fa-minus' ?>"></i>
        </span>
        <span class="flex-1 text-sm font-medium <?= $item['done'] ? 'text-slate-400 line-through' : 'text-slate-700' ?>"><?= h($item['label']) ?></span>
        <i class="fa-solid fa-chevron-right text-xs text-slate-300 group-hover:text-slate-500 transition"></i>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- SEO health -->
  <div class="lg:col-span-2 card p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-bold text-slate-800"><i class="fa-solid fa-magnifying-glass-chart text-brand mr-1.5"></i>SEO Health</h2>
    </div>
    <?php if ($total_categories === 0): ?>
      <p class="text-sm text-slate-400 py-4">Add categories to start tracking SEO completeness.</p>
    <?php else: ?>
      <div class="flex items-center gap-4 mb-5">
        <div class="relative w-16 h-16 shrink-0">
          <svg viewBox="0 0 36 36" class="w-16 h-16 -rotate-90">
            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#f1f5f9" stroke-width="4"></circle>
            <circle cx="18" cy="18" r="15.5" fill="none" stroke="<?= $seo_score >= 70 ? '#059669' : ($seo_score >= 40 ? '#d97706' : '#dc2626') ?>" stroke-width="4" stroke-linecap="round" stroke-dasharray="<?= round($seo_score * 0.974, 1) ?> 999"></circle>
          </svg>
          <div class="absolute inset-0 flex items-center justify-center text-sm font-extrabold text-slate-700"><?= $seo_score ?>%</div>
        </div>
        <div class="text-sm text-slate-500"><span class="font-semibold text-slate-700"><?= $seo_ok_categories ?> of <?= $total_categories ?></span> categories have complete meta title &amp; description.</div>
      </div>
      <?php if ($incomplete_categories): ?>
      <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Needs attention</p>
      <div class="space-y-1">
        <?php foreach ($incomplete_categories as $c): ?>
        <a href="<?= ADMIN_URL ?>/pages/categories.php?edit=<?= (int)$c['id'] ?>" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-amber-50 text-sm transition">
          <span class="text-slate-600"><?= h($c['name']) ?></span>
          <span class="text-amber-600 text-xs font-semibold">Fix SEO <i class="fa-solid fa-arrow-right ml-1"></i></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="text-sm text-emerald-600 font-medium"><i class="fa-solid fa-circle-check mr-1"></i> All categories are SEO-optimized.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Quick actions -->
<div class="card p-6">
  <h2 class="font-bold text-slate-800 mb-4">Quick Actions</h2>
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <?php foreach ([
        ['icon' => 'fa-sitemap', 'label' => 'Add Category', 'href' => ADMIN_URL . '/pages/categories.php'],
        ['icon' => 'fa-images',  'label' => 'Add Banner',   'href' => ADMIN_URL . '/pages/banners.php'],
        ['icon' => 'fa-bars',    'label' => 'Edit Menu',    'href' => ADMIN_URL . '/pages/menu.php'],
        ['icon' => 'fa-gear',    'label' => 'Settings',     'href' => ADMIN_URL . '/pages/settings.php'],
    ] as $qa): ?>
    <a href="<?= $qa['href'] ?>" class="flex flex-col items-center justify-center gap-2 text-center px-4 py-5 rounded-xl border border-slate-100 hover:border-brand/30 hover:bg-brand-light/20 transition group">
      <div class="w-10 h-10 rounded-lg bg-brand-light text-brand-dark flex items-center justify-center group-hover:bg-brand group-hover:text-white transition"><i class="fa-solid <?= $qa['icon'] ?>"></i></div>
      <span class="text-xs font-semibold text-slate-600"><?= $qa['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
