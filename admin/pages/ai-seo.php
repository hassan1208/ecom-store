<?php
// admin/pages/ai-seo.php — AI-assisted SEO scoring & generation for products
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'AI SEO Manager';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'ai_settings') {
    require_csrf();
    foreach (['ai_provider', 'ai_api_key', 'ai_model'] as $key) {
        $val = sanitize($_POST[$key] ?? '');
        $stmt = db()->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param('sss', $key, $val, $val);
        $stmt->execute();
    }
    set_flash('success', 'AI settings saved.');
    header('Location: ' . ADMIN_URL . '/pages/ai-seo.php?tab=ai-settings');
    exit;
}

$s = get_settings();
$ai_ready = !empty($s['ai_api_key']);
$active_tab = $_GET['tab'] ?? 'products';

$products = fetch_all("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.name ASC");
$alt_map = array_fill_keys(array_column(fetch_all("SELECT DISTINCT product_id FROM product_images WHERE alt_text IS NOT NULL AND alt_text<>''"), 'product_id'), true);

$good = $ok = $poor = 0;
foreach ($products as &$p) {
    $p['seo_score'] = calculate_seo_score($p, $alt_map);
    $band = seo_score_band($p['seo_score'])['label'];
    if ($band === 'good') $good++; elseif ($band === 'ok') $ok++; else $poor++;
}
unset($p);

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex items-start justify-between mb-6 flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-robot text-brand"></i> AI SEO Manager</h1>
    <p class="text-slate-500 text-sm mt-1">Generate, optimize and score SEO for every product using AI.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="badge <?= $ai_ready ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
      <i class="fa-solid fa-circle text-[6px] mr-1.5"></i> <?= $ai_ready ? 'AI Ready' : 'AI Not Configured' ?>
    </span>
    <button type="button" onclick="generateAll()" id="generateAllBtn" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition" <?= $ai_ready ? '' : 'disabled title="Set up your AI API key first"' ?>>
      <i class="fa-solid fa-wand-magic-sparkles"></i> Generate All SEO
    </button>
  </div>
</div>

<div class="flex items-center gap-1 mb-6 border-b border-slate-200" id="aiSeoTabs">
  <button type="button" data-tab="products" onclick="showAiTab('products')" class="tab-btn flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition <?= $active_tab === 'products' ? 'border-brand text-brand' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
    <i class="fa-solid fa-box"></i> Products <span class="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-0.5 rounded-full"><?= count($products) ?></span>
  </button>
  <button type="button" data-tab="global" onclick="showAiTab('global')" class="tab-btn flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition <?= $active_tab === 'global' ? 'border-brand text-brand' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
    <i class="fa-solid fa-globe"></i> Global
  </button>
  <button type="button" data-tab="ai-settings" onclick="showAiTab('ai-settings')" class="tab-btn flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition <?= $active_tab === 'ai-settings' ? 'border-brand text-brand' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
    <i class="fa-solid fa-sliders"></i> AI Settings
  </button>
</div>

<!-- Products tab -->
<div class="ai-tab-panel" data-panel="products">
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-center">
      <div class="text-3xl font-extrabold text-emerald-700"><?= $good ?></div>
      <div class="text-xs font-semibold text-emerald-700 mt-1">Good (80-100)</div>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-center">
      <div class="text-3xl font-extrabold text-amber-700"><?= $ok ?></div>
      <div class="text-xs font-semibold text-amber-700 mt-1">OK (50-79)</div>
    </div>
    <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-center">
      <div class="text-3xl font-extrabold text-red-700"><?= $poor ?></div>
      <div class="text-xs font-semibold text-red-700 mt-1">Poor (&lt;50)</div>
    </div>
  </div>

  <div class="flex items-center gap-2 mb-4">
    <input type="text" id="seoSearch" placeholder="Search…" class="f-input" oninput="filterList()">
    <select id="scoreFilter" class="f-select w-44" onchange="filterList()">
      <option value="">All scores</option>
      <option value="good">Good (80-100)</option>
      <option value="ok">OK (50-79)</option>
      <option value="poor">Poor (&lt;50)</option>
    </select>
  </div>

  <div class="card divide-y divide-slate-100" id="productListWrap">
    <?php foreach ($products as $p): $band = seo_score_band($p['seo_score']); ?>
    <div class="seo-row flex items-center gap-4 p-4" data-title="<?= h(mb_strtolower($p['name'])) ?>" data-band="<?= $band['label'] ?>" data-id="<?= (int)$p['id'] ?>">
      <?php $thumb = fetch_one("SELECT image_path FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order ASC LIMIT 1", 'i', $p['id']); ?>
      <?php if ($thumb): ?>
      <img src="<?= UPLOAD_URL . h($thumb['image_path']) ?>" class="w-12 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
      <?php else: ?>
      <div class="w-12 h-12 rounded-lg bg-slate-100 text-slate-300 flex items-center justify-center shrink-0"><i class="fa-solid fa-image"></i></div>
      <?php endif; ?>
      <div class="flex-1 min-w-0">
        <div class="font-semibold text-slate-800 text-sm truncate seo-title-display"><?= h($p['meta_title'] ?: $p['name']) ?></div>
        <div class="text-xs text-slate-400"><?= h($p['category_name'] ?? 'Uncategorized') ?> · <a href="<?= SITE_URL ?>/product/<?= h($p['slug']) ?>" target="_blank" class="text-brand hover:underline">View <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i></a></div>
      </div>
      <div class="score-badge w-11 h-11 rounded-full flex items-center justify-center font-bold text-sm shrink-0 bg-<?= $band['color'] ?>-100 text-<?= $band['color'] ?>-700"><?= $p['seo_score'] ?></div>
      <button type="button" onclick="generateOne(<?= (int)$p['id'] ?>, this)" class="ai-btn inline-flex items-center gap-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold px-3.5 py-2 rounded-lg transition shrink-0" <?= $ai_ready ? '' : 'disabled' ?>>
        <i class="fa-solid fa-wand-magic-sparkles"></i> AI
      </button>
      <a href="product-edit.php?id=<?= (int)$p['id'] ?>" class="btn-outline btn-sm shrink-0">Edit</a>
    </div>
    <?php endforeach; ?>
    <?php if (empty($products)): ?><div class="p-10 text-center text-slate-400">No products yet.</div><?php endif; ?>
  </div>
</div>

<!-- Global tab -->
<div class="ai-tab-panel" data-panel="global" style="display:none">
  <div class="card p-6 max-w-2xl">
    <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-house text-brand mr-1.5"></i>Homepage SEO</h2>
    <p class="f-hint mb-4">Site-wide meta tags are managed in Settings.</p>
    <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 mb-4">
      <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Meta Title</div>
      <div class="text-sm text-slate-700 mb-3"><?= h($s['homepage_meta_title'] ?? '') ?: '<span class="text-slate-400">Not set</span>' ?></div>
      <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Meta Description</div>
      <div class="text-sm text-slate-700"><?= h($s['homepage_meta_description'] ?? '') ?: '<span class="text-slate-400">Not set</span>' ?></div>
    </div>
    <a href="settings.php?tab=seo" class="btn-outline"><i class="fa-solid fa-pen"></i> Edit in Settings</a>
  </div>
</div>

<!-- AI Settings tab -->
<div class="ai-tab-panel" data-panel="ai-settings" style="display:none">
  <div class="card p-6 max-w-xl">
    <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-key text-brand mr-1.5"></i>AI Provider</h2>
    <p class="f-hint mb-4">Your key is used server-side only to call the AI provider on your behalf — it's never exposed to visitors.</p>
    <form method="POST" id="aiSettingsForm">
      <?= csrf_field() ?>
      <input type="hidden" name="form" value="ai_settings">
      <div class="mb-4">
        <label class="f-label">Provider</label>
        <select name="ai_provider" class="f-select">
          <option value="openai" selected>OpenAI</option>
        </select>
      </div>
      <div class="mb-4">
        <label class="f-label">Model</label>
        <select name="ai_model" class="f-select">
          <?php foreach (['gpt-4o-mini' => 'GPT-4o mini (fastest, cheapest)', 'gpt-4o' => 'GPT-4o (higher quality)', 'gpt-4-turbo' => 'GPT-4 Turbo'] as $val => $label): ?>
          <option value="<?= $val ?>" <?= (($s['ai_model'] ?? 'gpt-4o-mini') === $val) ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-2">
        <label class="f-label">API Key</label>
        <div class="flex gap-2">
          <input type="password" name="ai_api_key" id="apiKeyInput" class="f-input" value="<?= h($s['ai_api_key'] ?? '') ?>" placeholder="sk-...">
          <button type="button" onclick="toggleKeyVisibility()" class="btn-outline shrink-0"><i class="fa-solid fa-eye" id="toggleKeyIcon"></i></button>
        </div>
        <p class="f-hint">From <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener" class="text-brand">platform.openai.com/api-keys</a>.</p>
      </div>
      <div class="flex items-center gap-2 mt-4">
        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
        <button type="button" onclick="testConnection()" class="btn-outline" id="testBtn"><i class="fa-solid fa-plug"></i> Test Connection</button>
        <span id="testResult" class="text-sm font-medium"></span>
      </div>
    </form>
  </div>
</div>

<!-- Progress overlay for "Generate All" -->
<div id="progressOverlay" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
  <div class="bg-white rounded-2xl p-6 w-full max-w-sm text-center">
    <i class="fa-solid fa-wand-magic-sparkles text-3xl text-violet-600 mb-3"></i>
    <h3 class="font-bold text-slate-800 mb-1">Generating SEO…</h3>
    <p class="text-sm text-slate-500 mb-4"><span id="progressCount">0</span> / <span id="progressTotal">0</span> products</p>
    <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
      <div id="progressBar" class="h-full bg-violet-600 transition-all" style="width:0%"></div>
    </div>
  </div>
</div>

<script>
const csrfToken = <?= json_encode(csrf_token()) ?>;

function showAiTab(key) {
  document.querySelectorAll('.ai-tab-panel').forEach(p => p.style.display = p.dataset.panel === key ? '' : 'none');
  document.querySelectorAll('#aiSeoTabs .tab-btn').forEach(b => {
    const active = b.dataset.tab === key;
    b.classList.toggle('border-brand', active); b.classList.toggle('text-brand', active);
    b.classList.toggle('border-transparent', !active); b.classList.toggle('text-slate-500', !active);
  });
  history.replaceState(null, '', '?tab=' + key);
}
showAiTab(<?= json_encode($active_tab) ?>);

function toggleKeyVisibility() {
  const input = document.getElementById('apiKeyInput');
  const icon = document.getElementById('toggleKeyIcon');
  input.type = input.type === 'password' ? 'text' : 'password';
  icon.className = input.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
}

async function testConnection() {
  const btn = document.getElementById('testBtn');
  const result = document.getElementById('testResult');
  btn.disabled = true; result.textContent = 'Testing…'; result.className = 'text-sm font-medium text-slate-400';
  const fd = new FormData();
  fd.append('csrf_token', csrfToken);
  fd.append('api_key', document.getElementById('apiKeyInput').value);
  try {
    const res = await fetch('ai-seo-test.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { result.textContent = '✓ Connected'; result.className = 'text-sm font-medium text-emerald-600'; }
    else { result.textContent = data.error || 'Failed'; result.className = 'text-sm font-medium text-red-600'; }
  } catch (e) { result.textContent = 'Connection error'; result.className = 'text-sm font-medium text-red-600'; }
  btn.disabled = false;
}

function scoreColor(score) {
  if (score >= 80) return 'emerald'; if (score >= 50) return 'amber'; return 'red';
}

async function generateOne(id, btn) {
  const row = btn.closest('.seo-row');
  const originalHtml = btn.innerHTML;
  btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
  const fd = new FormData();
  fd.append('csrf_token', csrfToken);
  fd.append('id', id);
  try {
    const res = await fetch('ai-seo-generate.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      row.querySelector('.seo-title-display').textContent = data.meta_title;
      const badge = row.querySelector('.score-badge');
      const color = scoreColor(data.score);
      badge.className = `score-badge w-11 h-11 rounded-full flex items-center justify-center font-bold text-sm shrink-0 bg-${color}-100 text-${color}-700`;
      badge.textContent = data.score;
      row.dataset.band = data.score >= 80 ? 'good' : (data.score >= 50 ? 'ok' : 'poor');
    } else {
      alert(data.error || 'Generation failed.');
    }
  } catch (e) { alert('Request failed.'); }
  btn.disabled = false; btn.innerHTML = originalHtml;
}

async function generateAll() {
  const rows = Array.from(document.querySelectorAll('.seo-row'));
  if (!rows.length) return;
  if (!confirm(`This will generate AI SEO for ${rows.length} products using your API key. This may take a while and use API credits. Continue?`)) return;

  const overlay = document.getElementById('progressOverlay');
  const btn = document.getElementById('generateAllBtn');
  overlay.classList.remove('hidden'); btn.disabled = true;
  document.getElementById('progressTotal').textContent = rows.length;

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const aiBtn = row.querySelector('.ai-btn');
    await generateOne(row.dataset.id, aiBtn);
    document.getElementById('progressCount').textContent = i + 1;
    document.getElementById('progressBar').style.width = Math.round(((i + 1) / rows.length) * 100) + '%';
  }

  overlay.classList.add('hidden'); btn.disabled = false;
}

function filterList() {
  const q = document.getElementById('seoSearch').value.toLowerCase();
  const band = document.getElementById('scoreFilter').value;
  document.querySelectorAll('.seo-row').forEach(row => {
    const matchesText = row.dataset.title.includes(q);
    const matchesBand = !band || row.dataset.band === band;
    row.style.display = (matchesText && matchesBand) ? '' : 'none';
  });
}
</script>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
