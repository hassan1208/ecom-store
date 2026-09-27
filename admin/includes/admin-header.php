<?php
// admin/includes/admin-header.php
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
if ($current_page === 'product-edit') $current_page = 'products';
if ($current_page === 'order-detail' || $current_page === 'orders-export' || $current_page === 'order-invoice') $current_page = 'orders';
if ($current_page === 'customer-detail') $current_page = 'customers';
if ($current_page === 'page-edit') $current_page = 'pages';
if ($current_page === 'blog-edit' || $current_page === 'blog-image-upload') $current_page = 'blog';
if ($current_page === 'ai-seo-generate' || $current_page === 'ai-seo-test') $current_page = 'ai-seo';
if ($current_page === 'seo-insight-check') $current_page = 'seo-insight';

$pending_orders_count  = (int)(fetch_one("SELECT COUNT(*) c FROM orders WHERE status='pending'")['c'] ?? 0);
$pending_reviews_count = (int)(fetch_one("SELECT COUNT(*) c FROM reviews WHERE status='pending'")['c'] ?? 0);
$pending_design_requests_count = (int)(fetch_one("SELECT COUNT(*) c FROM design_requests WHERE status='new'")['c'] ?? 0);

$nav_groups = [
    'Main' => [
        ['key' => 'index',      'label' => 'Dashboard',        'icon' => 'fa-table-cells-large',       'href' => ADMIN_URL . '/index.php'],
        ['key' => 'analytics',  'label' => 'Analytics',        'icon' => 'fa-chart-line',   'href' => ADMIN_URL . '/pages/analytics.php'],
        ['key' => 'ai-seo',     'label' => 'AI SEO',           'icon' => 'fa-robot',        'href' => ADMIN_URL . '/pages/ai-seo.php'],
        ['key' => 'seo-insight', 'label' => 'SEO Insight',     'icon' => 'fa-magnifying-glass', 'href' => ADMIN_URL . '/pages/seo-insight.php'],
    ],
    'Catalog' => [
        ['key' => 'products',   'label' => 'Products',         'icon' => 'fa-box',          'href' => ADMIN_URL . '/pages/products.php'],
        ['key' => 'categories', 'label' => 'Categories',       'icon' => 'fa-sitemap',      'href' => ADMIN_URL . '/pages/categories.php'],
    ],
    'Sales' => [
        ['key' => 'orders',     'label' => 'Orders',           'icon' => 'fa-cart-shopping', 'href' => ADMIN_URL . '/pages/orders.php', 'badge' => $pending_orders_count],
        ['key' => 'abandoned-carts', 'label' => 'Abandoned Carts', 'icon' => 'fa-cart-arrow-down', 'href' => ADMIN_URL . '/pages/abandoned-carts.php'],
        ['key' => 'coupons',    'label' => 'Coupons',          'icon' => 'fa-tags',         'href' => ADMIN_URL . '/pages/coupons.php'],
        ['key' => 'payment-methods', 'label' => 'Payment Methods', 'icon' => 'fa-credit-card', 'href' => ADMIN_URL . '/pages/payment-methods.php'],
        ['key' => 'reviews',    'label' => 'Reviews',          'icon' => 'fa-star',         'href' => ADMIN_URL . '/pages/reviews.php', 'badge' => $pending_reviews_count],
        ['key' => 'customers',  'label' => 'Customers',        'icon' => 'fa-users',        'href' => ADMIN_URL . '/pages/customers.php'],
        ['key' => 'bulk-inquiries', 'label' => 'Bulk Inquiries', 'icon' => 'fa-boxes-stacked', 'href' => ADMIN_URL . '/pages/bulk-inquiries.php'],
        ['key' => 'design-requests', 'label' => 'Design Requests', 'icon' => 'fa-image', 'href' => ADMIN_URL . '/pages/design-requests.php', 'badge' => $pending_design_requests_count],
    ],
    'Content' => [
        ['key' => 'homepage-manager', 'label' => 'Homepage Sections', 'icon' => 'fa-house', 'href' => ADMIN_URL . '/pages/homepage-manager.php'],
        ['key' => 'banners',    'label' => 'Hero Banners',      'icon' => 'fa-images',       'href' => ADMIN_URL . '/pages/banners.php?type=hero'],
        ['key' => 'features',   'label' => 'Features',         'icon' => 'fa-award',        'href' => ADMIN_URL . '/pages/features.php'],
        ['key' => 'testimonials', 'label' => 'Testimonials',   'icon' => 'fa-quote-left',   'href' => ADMIN_URL . '/pages/testimonials.php'],
        ['key' => 'certifications', 'label' => 'Certifications', 'icon' => 'fa-certificate', 'href' => ADMIN_URL . '/pages/certifications.php'],
        ['key' => 'blog',       'label' => 'Blog',             'icon' => 'fa-newspaper',    'href' => ADMIN_URL . '/pages/blog.php'],
        ['key' => 'pages',      'label' => 'Pages',            'icon' => 'fa-file-lines',   'href' => ADMIN_URL . '/pages/pages.php'],
        ['key' => 'menu',       'label' => 'Navbar Menu',      'icon' => 'fa-bars',         'href' => ADMIN_URL . '/pages/menu.php'],
        ['key' => 'footer',     'label' => 'Footer',           'icon' => 'fa-shoe-prints',  'href' => ADMIN_URL . '/pages/footer.php'],
        ['key' => 'newsletter', 'label' => 'Newsletter',       'icon' => 'fa-envelope',     'href' => ADMIN_URL . '/pages/newsletter.php'],
    ],
    'System' => [
        ['key' => 'settings',   'label' => 'Settings',         'icon' => 'fa-gear',         'href' => ADMIN_URL . '/pages/settings.php'],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= h($page_title ?? 'Admin') ?> · BuiltCo Sports Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/css/admin.css?v=<?= @filemtime(__DIR__ . '/../assets/css/admin.css') ?>">
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased">
<div class="flex min-h-screen">

  <!-- Sidebar -->
  <div class="admin-backdrop hidden fixed inset-0 bg-black/40 z-20 lg:hidden" onclick="document.body.classList.remove('admin-nav-open')"></div>
  <aside class="admin-sidebar w-64 shrink-0 bg-ink text-slate-300 flex flex-col fixed inset-y-0 left-0 z-30">
    <div class="h-16 flex items-center gap-2 px-5 border-b border-white/10">
      <div class="w-9 h-9 rounded-lg bg-brand flex items-center justify-center text-white font-extrabold">B</div>
      <div>
        <div class="text-white font-bold leading-tight text-sm">BuiltCo Sports</div>
        <div class="text-[11px] text-slate-400 leading-tight">Admin Panel</div>
      </div>
    </div>
    <nav class="flex-1 px-3 py-4 overflow-y-auto">
      <?php foreach ($nav_groups as $group_label => $items): ?>
      <p class="px-3 mt-4 mb-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-500 first:mt-0"><?= h($group_label) ?></p>
      <div class="space-y-1 mb-2">
        <?php foreach ($items as $item): $active = $current_page === $item['key']; ?>
        <a href="<?= $item['href'] ?>"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                  <?= $active ? 'bg-brand text-white shadow-lg shadow-brand/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' ?>">
          <i class="fa-solid <?= $item['icon'] ?> w-4 text-center"></i>
          <span class="flex-1"><?= h($item['label']) ?></span>
          <?php if (!empty($item['badge'])): ?>
          <span class="min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold flex items-center justify-center <?= $active ? 'bg-white text-brand' : 'bg-red-500 text-white' ?>"><?= $item['badge'] ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </nav>
    <div class="p-3 border-t border-white/10">
      <a href="<?= ADMIN_URL ?>/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white transition">
        <i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Logout
      </a>
    </div>
  </aside>

  <!-- Main -->
  <div class="flex-1 lg:ml-64 flex flex-col min-h-screen min-w-0">
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-10 gap-3">
      <button type="button" class="lg:hidden w-9 h-9 rounded-lg border border-slate-200 flex items-center justify-center text-slate-600" onclick="document.body.classList.toggle('admin-nav-open')" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
      <div class="flex-1 min-w-0">
        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Dashboard <?= $current_page !== 'index' ? '/ ' . h($page_title ?? '') : '' ?></p>
        <h1 class="text-lg font-bold text-slate-800 truncate"><?= h($page_title ?? '') ?></h1>
      </div>
      <div class="flex items-center gap-4">
        <a href="<?= SITE_URL ?>/" target="_blank" class="btn-outline btn-sm"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
        <div class="flex items-center gap-3">
          <div class="text-right hidden sm:block">
            <div class="text-sm font-semibold text-slate-700"><?= h($_SESSION['user_name'] ?? 'Admin') ?></div>
            <div class="text-xs text-slate-400">Administrator</div>
          </div>
          <div class="w-9 h-9 rounded-full bg-brand-light text-brand-dark flex items-center justify-center font-bold text-sm">
            <?= h(strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1))) ?>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-1 p-4 sm:p-6">
      <?php $success = get_flash('success'); $error = get_flash('error'); ?>
      <?php if ($success): ?>
      <div data-flash class="mb-5 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm font-medium">
        <i class="fa-solid fa-circle-check"></i> <?= h($success) ?>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div data-flash class="mb-5 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm font-medium">
        <i class="fa-solid fa-circle-exclamation"></i> <?= h($error) ?>
      </div>
      <?php endif; ?>
