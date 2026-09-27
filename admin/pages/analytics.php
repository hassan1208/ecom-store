<?php
// admin/pages/analytics.php — simple self-hosted traffic + product view stats
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Analytics';

$today = date('Y-m-d');

$stat = function ($sql) { return (int)(fetch_one($sql)['c'] ?? 0); };

$today_visits  = $stat("SELECT COUNT(*) c FROM page_visits WHERE DATE(visited_at)=CURDATE()");
$today_unique  = $stat("SELECT COUNT(*) c FROM page_visits WHERE DATE(visited_at)=CURDATE() AND is_unique=1");
$week_visits   = $stat("SELECT COUNT(*) c FROM page_visits WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$week_unique   = $stat("SELECT COUNT(*) c FROM page_visits WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_unique=1");
$month_visits  = $stat("SELECT COUNT(*) c FROM page_visits WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$month_unique  = $stat("SELECT COUNT(*) c FROM page_visits WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_unique=1");
$alltime_visits = $stat("SELECT COUNT(*) c FROM page_visits");

// Last 7 days daily breakdown (for a simple CSS bar chart)
$daily_raw = fetch_all("SELECT DATE(visited_at) d, COUNT(*) c FROM page_visits WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(visited_at)");
$daily_map = array_column($daily_raw, 'c', 'd');
$daily = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $daily[] = ['date' => $d, 'label' => date('D', strtotime($d)), 'count' => (int)($daily_map[$d] ?? 0)];
}
$max_daily = max(1, max(array_column($daily, 'count')));

$top_pages = fetch_all("SELECT url, COUNT(*) c FROM page_visits WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY url ORDER BY c DESC LIMIT 8");
$top_products = fetch_all("SELECT name, views FROM products WHERE views > 0 ORDER BY views DESC LIMIT 8");

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Today</div>
    <div class="text-2xl font-extrabold text-slate-800"><?= $today_visits ?></div>
    <div class="text-xs text-slate-500"><?= $today_unique ?> unique visitors</div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Last 7 Days</div>
    <div class="text-2xl font-extrabold text-slate-800"><?= $week_visits ?></div>
    <div class="text-xs text-slate-500"><?= $week_unique ?> unique visitors</div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Last 30 Days</div>
    <div class="text-2xl font-extrabold text-slate-800"><?= $month_visits ?></div>
    <div class="text-xs text-slate-500"><?= $month_unique ?> unique visitors</div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">All Time</div>
    <div class="text-2xl font-extrabold text-slate-800"><?= $alltime_visits ?></div>
    <div class="text-xs text-slate-500">total pageviews</div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
  <div class="lg:col-span-3 card p-6">
    <h2 class="font-bold text-slate-800 mb-5">Visits — Last 7 Days</h2>
    <div class="flex items-end gap-3" style="height:160px">
      <?php foreach ($daily as $d): ?>
      <div class="flex-1 flex flex-col items-center justify-end h-full gap-2">
        <span class="text-xs font-semibold text-slate-600"><?= $d['count'] ?></span>
        <div class="w-full bg-brand rounded-t-md transition-all" style="height:<?= max(4, round($d['count'] / $max_daily * 120)) ?>px"></div>
        <span class="text-[11px] text-slate-400"><?= $d['label'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="lg:col-span-2 card p-6">
    <h2 class="font-bold text-slate-800 mb-4">Top Pages <span class="text-slate-400 font-normal text-xs">(30 days)</span></h2>
    <?php if ($top_pages): ?>
    <div class="space-y-2.5">
      <?php foreach ($top_pages as $p): ?>
      <div class="flex items-center justify-between text-sm">
        <span class="text-slate-600 truncate pr-3"><?= h($p['url']) ?></span>
        <span class="font-semibold text-slate-800 shrink-0"><?= (int)$p['c'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-sm text-slate-400">No visits recorded yet.</p>
    <?php endif; ?>
  </div>
</div>

<div class="card p-6 mt-6">
  <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-eye text-brand mr-1.5"></i>Most Viewed Products</h2>
  <p class="f-hint mb-4">Counts start once a product's public page goes live.</p>
  <?php if ($top_products): ?>
  <div class="space-y-2">
    <?php foreach ($top_products as $i => $tp): ?>
    <div class="flex items-center gap-3">
      <span class="w-6 h-6 rounded-full bg-brand text-white text-xs font-bold flex items-center justify-center shrink-0"><?= $i + 1 ?></span>
      <span class="flex-1 text-sm text-slate-700 truncate"><?= h($tp['name']) ?></span>
      <span class="text-sm font-semibold text-slate-500"><?= (int)$tp['views'] ?> views</span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <p class="text-sm text-slate-400">No product views yet.</p>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
