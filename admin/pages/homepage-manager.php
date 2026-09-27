<?php
// admin/pages/homepage-manager.php — central on/off + title control for every homepage section
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Homepage Manager';

$toggle_keys = ['hero', 'trustbar', 'category', 'new_arrivals', 'featured', 'promo', 'bestsellers', 'comingsoon', 'why', 'testimonials', 'certifications', 'newsletter'];
$text_keys = [
    'section_category_title', 'section_new_arrivals_title', 'section_featured_title',
    'section_bestsellers_title', 'section_comingsoon_title', 'homepage_intro_title',
    'section_testimonials_title', 'section_certifications_title', 'section_newsletter_title', 'section_newsletter_subtitle',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    foreach ($toggle_keys as $key) {
        $val = isset($_POST["section_{$key}_enabled"]) ? '1' : '0';
        $stmt = db()->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $k = "section_{$key}_enabled";
        $stmt->bind_param('sss', $k, $val, $val);
        $stmt->execute();
    }
    foreach ($text_keys as $key) {
        $val = sanitize($_POST[$key] ?? '');
        $stmt = db()->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param('sss', $key, $val, $val);
        $stmt->execute();
    }

    $cat_ids = array_filter(array_map('intval', $_POST['category_ids'] ?? []));
    $valid_ids = [];
    if ($cat_ids) {
        $placeholders = implode(',', array_fill(0, count($cat_ids), '?'));
        $valid = fetch_all("SELECT id FROM categories WHERE id IN ($placeholders) AND status='active'", str_repeat('i', count($cat_ids)), ...$cat_ids);
        $valid_map = array_column($valid, 'id');
        foreach ($cat_ids as $id) if (in_array($id, $valid_map) && !in_array($id, $valid_ids)) $valid_ids[] = $id;
    }
    $ids_csv = implode(',', $valid_ids);
    $stmt = db()->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('homepage_category_ids', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param('ss', $ids_csv, $ids_csv);
    $stmt->execute();

    set_flash('success', 'Homepage settings saved.');
    header('Location: ' . ADMIN_URL . '/pages/homepage-manager.php');
    exit;
}

$s = get_settings();
function sv2($s, $k) { return h($s[$k] ?? ''); }
$on = fn($key) => (($s["section_{$key}_enabled"] ?? '1') === '1');

$all_categories = fetch_all("SELECT id, name FROM categories WHERE status='active' ORDER BY name ASC");
$selected_category_ids = array_filter(array_map('intval', explode(',', $s['homepage_category_ids'] ?? '')));

include __DIR__ . '/../includes/admin-header.php';
?>
<p class="text-slate-500 text-sm mb-6">Toggle sections on/off and customize their titles and content.</p>

<form method="POST" id="hpForm">
  <?= csrf_field() ?>
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-6">

    <!-- Hero Slider -->
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-images text-brand"></i> Hero Slider</h3>
        <label class="switch"><input type="checkbox" name="section_hero_enabled" <?= $on('hero') ? 'checked' : '' ?>><span class="slider"></span></label>
      </div>
      <a href="banners.php?type=hero" class="btn-outline w-full justify-center"><i class="fa-solid fa-images"></i> Manage Banners</a>
    </div>

    <!-- Trust Bar -->
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-shield-halved text-brand"></i> Trust Bar</h3>
        <label class="switch"><input type="checkbox" name="section_trustbar_enabled" <?= $on('trustbar') ? 'checked' : '' ?>><span class="slider"></span></label>
      </div>
      <p class="text-sm text-slate-500 mb-3">Shipping · Secure · Quality · Support strip shown below the hero.</p>
      <a href="features.php" class="btn-outline w-full justify-center"><i class="fa-solid fa-award"></i> Manage Features</a>
    </div>

    <!-- Shop by Category -->
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-table-cells text-brand"></i> Shop by Category</h3>
        <label class="switch"><input type="checkbox" name="section_category_enabled" <?= $on('category') ? 'checked' : '' ?>><span class="slider"></span></label>
      </div>
      <input type="text" name="section_category_title" value="<?= sv2($s, 'section_category_title') ?>" class="f-input mb-3" placeholder="Section title">
      <div id="categoryRows" class="space-y-2 mb-3">
        <?php foreach ($selected_category_ids as $cid): ?>
        <div class="flex items-center gap-2 cat-row">
          <select name="category_ids[]" class="f-select flex-1">
            <?php foreach ($all_categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $c['id'] == $cid ? 'selected' : '' ?>><?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" onclick="this.closest('.cat-row').remove()" class="w-9 h-9 shrink-0 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" onclick="addCategoryRow()" class="btn-outline w-full justify-center"><i class="fa-solid fa-plus"></i> Add Category</button>
    </div>

    <!-- New Arrivals -->
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-star text-brand"></i> New Arrivals</h3>
        <label class="switch"><input type="checkbox" name="section_new_arrivals_enabled" <?= $on('new_arrivals') ? 'checked' : '' ?>><span class="slider"></span></label>
      </div>
      <input type="text" name="section_new_arrivals_title" value="<?= sv2($s, 'section_new_arrivals_title') ?>" class="f-input mb-3" placeholder="Section title">
      <a href="products.php" class="btn-outline w-full justify-center"><i class="fa-solid fa-tag"></i> Mark products as New Arrival</a>
    </div>

    <!-- Featured Products -->
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-star text-brand"></i> Featured Products</h3>
        <label class="switch"><input type="checkbox" name="section_featured_enabled" <?= $on('featured') ? 'checked' : '' ?>><span class="slider"></span></label>
      </div>
      <input type="text" name="section_featured_title" value="<?= sv2($s, 'section_featured_title') ?>" class="f-input mb-3" placeholder="Section title">
      <a href="products.php" class="btn-outline w-full justify-center"><i class="fa-solid fa-star"></i> Mark products as Featured</a>
    </div>

    <!-- Promo Banners -->
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-rectangle-ad text-brand"></i> Promo Banners</h3>
        <label class="switch"><input type="checkbox" name="section_promo_enabled" <?= $on('promo') ? 'checked' : '' ?>><span class="slider"></span></label>
      </div>
      <a href="banners.php?type=promo" class="btn-outline w-full justify-center"><i class="fa-solid fa-images"></i> Manage Promo Banners</a>
    </div>

    <!-- Best Sellers -->
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-fire text-brand"></i> Best Sellers</h3>
        <label class="switch"><input type="checkbox" name="section_bestsellers_enabled" <?= $on('bestsellers') ? 'checked' : '' ?>><span class="slider"></span></label>
      </div>
      <input type="text" name="section_bestsellers_title" value="<?= sv2($s, 'section_bestsellers_title') ?>" class="f-input" placeholder="Section title">
      <p class="f-hint mt-2">Ranked automatically from completed order sales.</p>
    </div>

    <!-- Coming Soon -->
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-clock text-brand"></i> Coming Soon</h3>
        <label class="switch"><input type="checkbox" name="section_comingsoon_enabled" <?= $on('comingsoon') ? 'checked' : '' ?>><span class="slider"></span></label>
      </div>
      <input type="text" name="section_comingsoon_title" value="<?= sv2($s, 'section_comingsoon_title') ?>" class="f-input mb-3" placeholder="Section title">
      <a href="products.php" class="btn-outline w-full justify-center"><i class="fa-solid fa-hourglass-half"></i> Mark products as Coming Soon</a>
    </div>

    <!-- Why Choose Us -->
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-medal text-brand"></i> Why Choose Us</h3>
        <label class="switch"><input type="checkbox" name="section_why_enabled" <?= $on('why') ? 'checked' : '' ?>><span class="slider"></span></label>
      </div>
      <input type="text" name="homepage_intro_title" value="<?= sv2($s, 'homepage_intro_title') ?>" class="f-input mb-2" placeholder="Section title">
      <p class="f-hint">Body text is edited in <a href="settings.php" class="text-brand font-semibold">Settings</a>.</p>
    </div>

    <!-- Testimonials -->
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-quote-left text-brand"></i> Testimonials</h3>
        <label class="switch"><input type="checkbox" name="section_testimonials_enabled" <?= $on('testimonials') ? 'checked' : '' ?>><span class="slider"></span></label>
      </div>
      <input type="text" name="section_testimonials_title" value="<?= sv2($s, 'section_testimonials_title') ?>" class="f-input mb-3" placeholder="Section title">
      <a href="testimonials.php" class="btn-outline w-full justify-center"><i class="fa-solid fa-users"></i> Manage Testimonials</a>
    </div>

    <!-- Certifications -->
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-certificate text-brand"></i> Certifications</h3>
        <label class="switch"><input type="checkbox" name="section_certifications_enabled" <?= $on('certifications') ? 'checked' : '' ?>><span class="slider"></span></label>
      </div>
      <input type="text" name="section_certifications_title" value="<?= sv2($s, 'section_certifications_title') ?>" class="f-input mb-3" placeholder="Section title">
      <a href="certifications.php" class="btn-outline w-full justify-center"><i class="fa-solid fa-award"></i> Manage Certifications</a>
    </div>

    <!-- Newsletter -->
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-envelope text-brand"></i> Newsletter</h3>
        <label class="switch"><input type="checkbox" name="section_newsletter_enabled" <?= $on('newsletter') ? 'checked' : '' ?>><span class="slider"></span></label>
      </div>
      <input type="text" name="section_newsletter_title" value="<?= sv2($s, 'section_newsletter_title') ?>" class="f-input mb-2" placeholder="Heading">
      <input type="text" name="section_newsletter_subtitle" value="<?= sv2($s, 'section_newsletter_subtitle') ?>" class="f-input mb-3" placeholder="Subtext">
      <a href="newsletter.php" class="btn-outline w-full justify-center"><i class="fa-solid fa-list"></i> View Subscribers</a>
    </div>

  </div>
</form>

<div class="fixed bottom-0 left-0 lg:left-64 right-0 bg-white border-t border-slate-200 px-6 py-3 z-30 flex justify-start">
  <button type="submit" form="hpForm" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save All Changes</button>
</div>
<div class="h-16"></div>

<template id="categoryRowTpl">
  <div class="flex items-center gap-2 cat-row">
    <select name="category_ids[]" class="f-select flex-1">
      <?php foreach ($all_categories as $c): ?>
      <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" onclick="this.closest('.cat-row').remove()" class="w-9 h-9 shrink-0 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
  </div>
</template>

<style>
  .switch { position: relative; display: inline-block; width: 40px; height: 22px; }
  .switch input { opacity: 0; width: 0; height: 0; }
  .switch .slider { position: absolute; cursor: pointer; inset: 0; background-color: #cbd5e1; transition: .2s; border-radius: 999px; }
  .switch .slider::before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .2s; border-radius: 50%; }
  .switch input:checked + .slider { background-color: #059669; }
  .switch input:checked + .slider::before { transform: translateX(18px); }
</style>

<script>
function addCategoryRow() {
  const tpl = document.getElementById('categoryRowTpl').content.cloneNode(true);
  document.getElementById('categoryRows').appendChild(tpl);
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
