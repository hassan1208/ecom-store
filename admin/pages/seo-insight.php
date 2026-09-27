<?php
// admin/pages/seo-insight.php — sitemap status, URL inspector, robots.txt editor,
// SEO overview, and Google Search Console verification
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'SEO Insight';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $form = $_POST['form'] ?? '';

    if ($form === 'robots') {
        $val = sanitize($_POST['robots_txt'] ?? '');
        $stmt = db()->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('robots_txt', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param('ss', $val, $val);
        $stmt->execute();
        set_flash('success', 'robots.txt saved.');
    } elseif ($form === 'gsc') {
        foreach (['gsc_verification', 'gsc_property_url'] as $key) {
            $val = sanitize($_POST[$key] ?? '');
            $stmt = db()->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param('sss', $key, $val, $val);
            $stmt->execute();
        }
        set_flash('success', 'Google verification settings saved.');
    }
    header('Location: ' . ADMIN_URL . '/pages/seo-insight.php');
    exit;
}

$s = get_settings();
$gsc_connected = !empty($s['gsc_verification']);

// Sitemap URL count — read our own live sitemap.xml so this always matches reality.
$sitemap_url_count = 0;
$ch = curl_init(url('sitemap.xml'));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8]);
$sitemap_xml = curl_exec($ch);
$sitemap_ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
curl_close($ch);
if ($sitemap_xml) $sitemap_url_count = substr_count($sitemap_xml, '<loc>');

$total_products     = (int)(fetch_one("SELECT COUNT(*) c FROM products")['c'] ?? 0);
$products_with_meta = (int)(fetch_one("SELECT COUNT(*) c FROM products WHERE meta_title<>'' AND meta_description<>''")['c'] ?? 0);
$products_with_kw   = (int)(fetch_one("SELECT COUNT(*) c FROM products WHERE focus_keyword<>''")['c'] ?? 0);

$robots_value = $s['robots_txt'] ?: default_robots_txt();

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex items-start justify-between mb-6 flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-magnifying-glass text-brand"></i> SEO Insight</h1>
    <p class="text-slate-500 text-sm mt-1">Crawling, indexing, Search Console &amp; sitemap management</p>
  </div>
  <span class="badge <?= $gsc_connected ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
    <i class="fa-solid <?= $gsc_connected ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> mr-1.5"></i> <?= $gsc_connected ? 'Connected' : 'Setup Required' ?>
  </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  <div class="space-y-6">

    <!-- Sitemap Status -->
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-4"><i class="fa-solid fa-sitemap text-brand mr-1.5"></i>Sitemap Status</h2>
      <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="rounded-lg bg-slate-50 p-4 text-center">
          <div class="text-2xl font-extrabold text-brand"><?= $sitemap_url_count ?></div>
          <div class="text-[11px] text-slate-500 mt-1">URLs in Sitemap</div>
        </div>
        <div class="rounded-lg bg-slate-50 p-4 text-center">
          <div class="text-2xl font-extrabold text-slate-300">—</div>
          <div class="text-[11px] text-slate-500 mt-1">Indexed (est.)</div>
        </div>
        <div class="rounded-lg bg-slate-50 p-4 text-center">
          <div class="text-2xl text-emerald-500 flex items-center justify-center"><i class="fa-solid fa-circle-check"></i></div>
          <div class="text-[11px] text-slate-500 mt-1"><?= $sitemap_ok ? 'Sitemap Live' : 'Sitemap Error' ?></div>
        </div>
      </div>
      <div class="flex flex-col sm:flex-row gap-2">
        <a href="<?= url('sitemap.xml') ?>" target="_blank" class="btn-outline"><i class="fa-solid fa-arrow-up-right-from-square"></i> View sitemap.xml</a>
        <input type="text" readonly value="<?= h(url('sitemap.xml')) ?>" class="f-input flex-1 bg-slate-50 text-slate-500" onclick="this.select()">
        <a href="https://search.google.com/search-console/sitemaps<?= $s['gsc_property_url'] ? '?resource_id=' . urlencode($s['gsc_property_url']) : '' ?>" target="_blank" class="btn-outline shrink-0">Submit to GSC</a>
      </div>
    </div>

    <!-- URL Inspector -->
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-magnifying-glass-location text-brand mr-1.5"></i>URL Inspector</h2>
      <p class="f-hint mb-3">Checks reachability, robots.txt rules, and sitemap presence for a URL on your site.</p>
      <div class="flex gap-2 mb-3">
        <input type="text" id="inspectUrl" value="<?= h(url('')) ?>" class="f-input flex-1">
        <button type="button" onclick="inspectUrl()" class="btn-outline shrink-0"><i class="fa-solid fa-magnifying-glass"></i> Check</button>
      </div>
      <div id="inspectResult"></div>
    </div>

    <!-- robots.txt editor -->
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-3"><i class="fa-solid fa-robot text-brand mr-1.5"></i>robots.txt Editor</h2>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="robots">
        <textarea name="robots_txt" rows="7" class="f-textarea font-mono text-xs mb-3"><?= h($robots_value) ?></textarea>
        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
      </form>
    </div>
  </div>

  <div class="space-y-6">

    <!-- Site SEO Overview -->
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-4"><i class="fa-solid fa-list-check text-brand mr-1.5"></i>Site SEO Overview</h2>
      <div class="mb-4">
        <div class="flex items-center justify-between text-sm font-semibold text-slate-700 mb-1.5">
          <span>Products with Meta Tags</span><span><?= $products_with_meta ?>/<?= $total_products ?></span>
        </div>
        <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
          <div class="h-full bg-emerald-500" style="width:<?= $total_products ? round($products_with_meta / $total_products * 100) : 0 ?>%"></div>
        </div>
      </div>
      <div class="mb-5">
        <div class="flex items-center justify-between text-sm font-semibold text-slate-700 mb-1.5">
          <span>Products with Keywords</span><span><?= $products_with_kw ?>/<?= $total_products ?></span>
        </div>
        <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
          <div class="h-full bg-emerald-500" style="width:<?= $total_products ? round($products_with_kw / $total_products * 100) : 0 ?>%"></div>
        </div>
      </div>
      <a href="ai-seo.php" class="btn-outline w-full justify-center"><i class="fa-solid fa-robot"></i> Open AI SEO Manager</a>
    </div>

    <!-- Google Verification -->
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-4"><i class="fa-brands fa-google text-brand mr-1.5"></i>Google Verification</h2>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="gsc">
        <div class="mb-4">
          <label class="f-label">Search Console Verification Code</label>
          <input type="text" name="gsc_verification" class="f-input" value="<?= h($s['gsc_verification'] ?? '') ?>" placeholder="google-site-verification=xxxxxxxxx">
          <p class="f-hint">The content value from the Google meta tag.</p>
        </div>
        <div class="mb-4">
          <label class="f-label">GSC Property URL</label>
          <input type="text" name="gsc_property_url" class="f-input" value="<?= h($s['gsc_property_url'] ?? '') ?>" placeholder="<?= h(url('')) ?>">
        </div>
        <button type="submit" class="btn-primary w-full mb-3"><i class="fa-solid fa-floppy-disk"></i> Save</button>
      </form>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <a href="https://search.google.com/search-console" target="_blank" class="btn-outline justify-center"><i class="fa-brands fa-google"></i> Open Search Console</a>
        <a href="https://pagespeed.web.dev/report?url=<?= urlencode(url('')) ?>" target="_blank" class="btn-outline justify-center"><i class="fa-solid fa-gauge-high"></i> PageSpeed Insights <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></a>
      </div>
    </div>
  </div>
</div>

<script>
const csrfToken = <?= json_encode(csrf_token()) ?>;

async function inspectUrl() {
  const input = document.getElementById('inspectUrl');
  const result = document.getElementById('inspectResult');
  result.innerHTML = '<p class="text-sm text-slate-400"><i class="fa-solid fa-spinner fa-spin"></i> Checking…</p>';
  const fd = new FormData();
  fd.append('csrf_token', csrfToken);
  fd.append('url', input.value);
  try {
    const res = await fetch('seo-insight-check.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.error) { result.innerHTML = `<p class="text-sm text-red-600"><i class="fa-solid fa-circle-exclamation"></i> ${data.error}</p>`; return; }
    const rows = [
      [data.reachable, data.reachable ? `Reachable (HTTP ${data.http_code})` : `Not reachable (HTTP ${data.http_code})`],
      [!data.disallowed, data.disallowed ? 'Blocked by robots.txt' : 'Allowed by robots.txt'],
      [data.in_sitemap, data.in_sitemap ? 'Present in sitemap.xml' : 'Not found in sitemap.xml'],
    ];
    result.innerHTML = '<div class="space-y-1.5 mt-2">' + rows.map(([ok, label]) =>
      `<div class="flex items-center gap-2 text-sm ${ok ? 'text-emerald-700' : 'text-red-600'}"><i class="fa-solid ${ok ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> ${label}</div>`
    ).join('') + '</div>';
  } catch (e) { result.innerHTML = '<p class="text-sm text-red-600">Request failed.</p>'; }
}
</script>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
