<?php
// admin/pages/settings.php — tabbed site settings: General, Branding, Payment,
// Integrations, Homepage SEO, Social, Advanced
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $fields = [
        'site_name', 'site_tagline', 'contact_phone', 'contact_email', 'contact_address', 'whatsapp_number',
        'social_facebook', 'social_instagram', 'social_twitter', 'social_youtube', 'social_tiktok',
        'homepage_meta_title', 'homepage_meta_description', 'homepage_intro_title', 'homepage_intro_content',
        'footer_about_text', 'footer_copyright_text', 'shipping_cost', 'currency_symbol', 'currency_code',
        'ga_measurement_id', 'fb_pixel_id', 'gsc_verification',
        'custom_header_scripts', 'custom_footer_scripts', 'robots_txt',
        'theme_primary_color', 'theme_primary_dark', 'theme_ink_color',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption',
        'smtp_from_email', 'smtp_from_name', 'abandoned_cart_reminder_hours',
        'seo_default_brand', 'seo_twitter_handle', 'seo_business_type', 'seo_founding_year',
        'seo_price_valid_days', 'seo_return_days', 'seo_return_fees', 'seo_shipping_country',
        'seo_handling_days_max', 'seo_transit_days_max', 'seo_bing_verification',
        'seo_pinterest_verification', 'seo_yandex_verification', 'announcement_text', 'hero_3d_model',
    ];
    foreach ($fields as $f) {
        $val = sanitize($_POST[$f] ?? '');
        $stmt = db()->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param('sss', $f, $val, $val);
        $stmt->execute();
    }

    foreach (['email_order_confirmation_enabled', 'email_order_status_update_enabled', 'email_admin_new_order_enabled', 'email_admin_bulk_inquiry_enabled', 'abandoned_cart_reminder_enabled', 'seo_noindex_site', 'studio_enabled_3d', 'hero_3d_enabled'] as $f) {
        $val = isset($_POST[$f]) ? '1' : '0';
        $stmt = db()->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param('sss', $f, $val, $val);
        $stmt->execute();
    }

    foreach (['site_logo' => [300, 100, 'logo'], 'site_favicon' => [64, 64, 'favicon'], 'og_image' => [1200, 630, 'og']] as $key => [$w, $h, $prefix]) {
        if (!empty($_FILES[$key]['name'])) {
            $res = upload_image($_FILES[$key], 'branding', $w, $h, $prefix);
            if (!isset($res['error'])) {
                $stmt = db()->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param('sss', $key, $res['filename'], $res['filename']);
                $stmt->execute();
            }
        }
    }

    set_flash('success', 'Settings saved.');
    header('Location: ' . ADMIN_URL . '/pages/settings.php' . (!empty($_POST['active_tab']) ? '?tab=' . urlencode($_POST['active_tab']) : ''));
    exit;
}

$s = get_settings();
function sv($s, $k) { return h($s[$k] ?? ''); }
$payment_methods = fetch_all("SELECT * FROM payment_methods ORDER BY sort_order ASC");
$active_tab = $_GET['tab'] ?? 'general';

include __DIR__ . '/../includes/admin-header.php';
?>
<p class="text-slate-500 text-sm -mt-3 mb-6">Configure your store's appearance, branding, and integrations.</p>

<div class="flex items-center gap-1 mb-6 border-b border-slate-200 overflow-x-auto" id="settingsTabs">
  <?php
  $tabs = [
      'general'      => ['icon' => 'fa-shop', 'label' => 'General'],
      'branding'     => ['icon' => 'fa-palette', 'label' => 'Branding'],
      'payment'      => ['icon' => 'fa-credit-card', 'label' => 'Payment'],
      'email'        => ['icon' => 'fa-envelope', 'label' => 'Email'],
      'integrations' => ['icon' => 'fa-plug', 'label' => 'Integrations'],
      'seo'          => ['icon' => 'fa-magnifying-glass', 'label' => 'SEO & Schema'],
      'studio'       => ['icon' => 'fa-cube', 'label' => '3D & Design'],
      'social'       => ['icon' => 'fa-share-nodes', 'label' => 'Social'],
      'advanced'     => ['icon' => 'fa-code', 'label' => 'Advanced'],
  ];
  foreach ($tabs as $key => $t): ?>
  <button type="button" data-tab="<?= $key ?>" onclick="showTab('<?= $key ?>')" class="tab-btn shrink-0 flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition <?= $active_tab === $key ? 'border-brand text-brand' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
    <i class="fa-solid <?= $t['icon'] ?>"></i> <?= $t['label'] ?>
  </button>
  <?php endforeach; ?>
</div>

<form method="POST" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="active_tab" id="activeTabInput" value="<?= h($active_tab) ?>">

  <!-- General -->
  <div class="tab-panel grid grid-cols-1 lg:grid-cols-2 gap-6" data-panel="general">
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-5"><i class="fa-solid fa-circle-info text-brand mr-1.5"></i>Site Information</h2>
      <div class="mb-4"><label class="f-label">Site Name *</label><input type="text" name="site_name" required class="f-input" value="<?= sv($s, 'site_name') ?>"></div>
      <div class="mb-4"><label class="f-label">Tagline</label><input type="text" name="site_tagline" class="f-input" value="<?= sv($s, 'site_tagline') ?>"></div>
      <div class="mb-4"><label class="f-label">Contact Email *</label><input type="email" name="contact_email" required class="f-input" value="<?= sv($s, 'contact_email') ?>"></div>
      <div class="mb-4"><label class="f-label">Phone</label><input type="text" name="contact_phone" class="f-input" value="<?= sv($s, 'contact_phone') ?>"></div>
      <div class="mb-4">
        <label class="f-label">WhatsApp Number</label>
        <input type="text" name="whatsapp_number" class="f-input" value="<?= sv($s, 'whatsapp_number') ?>" placeholder="e.g. 923001234567">
        <p class="f-hint mt-1">Country code + number, digits only, no + or spaces. Shows a floating WhatsApp chat button site-wide when set.</p>
      </div>
      <div><label class="f-label">Address</label><textarea name="contact_address" rows="2" class="f-textarea"><?= sv($s, 'contact_address') ?></textarea></div>
    </div>
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-5"><i class="fa-solid fa-shoe-prints text-brand mr-1.5"></i>Footer</h2>
      <div class="mb-4"><label class="f-label">About Text</label><textarea name="footer_about_text" rows="3" class="f-textarea"><?= sv($s, 'footer_about_text') ?></textarea></div>
      <div><label class="f-label">Copyright Text</label><input type="text" name="footer_copyright_text" class="f-input" value="<?= sv($s, 'footer_copyright_text') ?>"></div>
    </div>
  </div>

  <!-- Branding -->
  <div class="tab-panel grid grid-cols-1 lg:grid-cols-2 gap-6" data-panel="branding" style="display:none">
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-5"><i class="fa-solid fa-image text-brand mr-1.5"></i>Logo &amp; Favicon</h2>
      <div class="mb-5">
        <label class="f-label">Site Logo</label>
        <p class="f-hint mb-2">Recommended: 300×80px (PNG/SVG with transparent background)</p>
        <?php if (!empty($s['site_logo'])): ?><img src="<?= UPLOAD_URL . h($s['site_logo']) ?>" class="h-10 mb-2 bg-slate-800 p-1.5 rounded"><?php endif; ?>
        <input type="file" name="site_logo" accept="image/*" class="f-input">
      </div>
      <div>
        <label class="f-label">Favicon</label>
        <p class="f-hint mb-2">Recommended: 32×32px (ICO/PNG)</p>
        <?php if (!empty($s['site_favicon'])): ?><img src="<?= UPLOAD_URL . h($s['site_favicon']) ?>" class="h-8 w-8 mb-2 rounded"><?php endif; ?>
        <input type="file" name="site_favicon" accept="image/*" class="f-input">
      </div>
    </div>
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-palette text-brand mr-1.5"></i>Theme Colors</h2>
      <p class="f-hint mb-4">Controls the accent color across your storefront — buttons, links, badges, the hero overlay, everything. Changes apply site-wide instantly.</p>
      <div class="grid grid-cols-3 gap-3">
        <div>
          <label class="f-label text-xs">Accent Color</label>
          <div class="flex items-center gap-2 rounded-lg border border-slate-300 p-1.5">
            <input type="color" name="theme_primary_color" oninput="this.nextElementSibling.textContent=this.value" value="<?= sv($s, 'theme_primary_color') ?: '#ff4d2e' ?>" class="w-9 h-9 rounded cursor-pointer border-0 p-0 bg-transparent">
            <span class="text-xs font-mono text-slate-500"><?= sv($s, 'theme_primary_color') ?: '#ff4d2e' ?></span>
          </div>
        </div>
        <div>
          <label class="f-label text-xs">Accent Hover</label>
          <div class="flex items-center gap-2 rounded-lg border border-slate-300 p-1.5">
            <input type="color" name="theme_primary_dark" oninput="this.nextElementSibling.textContent=this.value" value="<?= sv($s, 'theme_primary_dark') ?: '#e0391d' ?>" class="w-9 h-9 rounded cursor-pointer border-0 p-0 bg-transparent">
            <span class="text-xs font-mono text-slate-500"><?= sv($s, 'theme_primary_dark') ?: '#e0391d' ?></span>
          </div>
        </div>
        <div>
          <label class="f-label text-xs">Dark Base</label>
          <div class="flex items-center gap-2 rounded-lg border border-slate-300 p-1.5">
            <input type="color" name="theme_ink_color" oninput="this.nextElementSibling.textContent=this.value" value="<?= sv($s, 'theme_ink_color') ?: '#0b0f14' ?>" class="w-9 h-9 rounded cursor-pointer border-0 p-0 bg-transparent">
            <span class="text-xs font-mono text-slate-500"><?= sv($s, 'theme_ink_color') ?: '#0b0f14' ?></span>
          </div>
        </div>
      </div>
      <a href="<?= SITE_URL ?>/" target="_blank" class="btn-outline btn-sm mt-4"><i class="fa-solid fa-arrow-up-right-from-square"></i> Preview Live Site</a>
    </div>
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-5"><i class="fa-solid fa-share-from-square text-brand mr-1.5"></i>Social Share Image</h2>
      <p class="f-hint mb-3">Shown when your homepage or any page without its own image is shared on Facebook, WhatsApp, Twitter/X, etc. Recommended: 1200×630px.</p>
      <?php if (!empty($s['og_image'])): ?><img src="<?= UPLOAD_URL . h($s['og_image']) ?>" class="w-full max-w-xs rounded-lg border border-slate-200 mb-2"><?php endif; ?>
      <input type="file" name="og_image" accept="image/*" class="f-input">
    </div>
  </div>

  <!-- Payment -->
  <div class="tab-panel space-y-6" data-panel="payment" style="display:none">
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-coins text-brand mr-1.5"></i>Currency</h2>
      <p class="f-hint mb-4">Used for every price shown across the site, invoices, and product structured data (Google).</p>
      <div class="grid grid-cols-2 gap-4 max-w-sm">
        <div><label class="f-label">Symbol</label><input type="text" name="currency_symbol" class="f-input" value="<?= sv($s, 'currency_symbol') ?: '$' ?>" placeholder="$"></div>
        <div><label class="f-label">Code (ISO 4217)</label><input type="text" name="currency_code" class="f-input" style="text-transform:uppercase" maxlength="3" value="<?= sv($s, 'currency_code') ?: 'USD' ?>" placeholder="USD"></div>
      </div>
    </div>
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-truck-fast text-brand mr-1.5"></i>Shipping</h2>
      <p class="f-hint mb-4">A flat rate applied to every order at checkout. Leave as 0 for free shipping.</p>
      <div class="max-w-xs"><label class="f-label">Flat Shipping Cost</label><input type="number" step="0.01" min="0" name="shipping_cost" class="f-input" value="<?= sv($s, 'shipping_cost') ?: '0' ?>"></div>
    </div>
    <div class="card p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-bold text-slate-800"><i class="fa-solid fa-credit-card text-brand mr-1.5"></i>Payment Methods</h2>
        <a href="payment-methods.php" class="btn-outline btn-sm"><i class="fa-solid fa-gear"></i> Manage</a>
      </div>
      <div class="divide-y divide-slate-100">
        <?php foreach ($payment_methods as $pm): ?>
        <div class="flex items-center justify-between py-3">
          <span class="text-sm font-medium text-slate-700"><?= h($pm['name']) ?></span>
          <span class="<?= $pm['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($pm['status']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Email -->
  <div class="tab-panel grid grid-cols-1 lg:grid-cols-2 gap-6" data-panel="email" style="display:none">
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-server text-brand mr-1.5"></i>SMTP Server</h2>
      <p class="f-hint mb-4">Used to send order confirmations and admin alerts. Works with Gmail, SendGrid, Mailgun, cPanel mail, etc.</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div><label class="f-label">SMTP Host</label><input type="text" name="smtp_host" class="f-input" value="<?= sv($s, 'smtp_host') ?>" placeholder="smtp.gmail.com"></div>
        <div><label class="f-label">Port</label><input type="number" name="smtp_port" class="f-input" value="<?= sv($s, 'smtp_port') ?: '587' ?>"></div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div><label class="f-label">Username</label><input type="text" name="smtp_username" class="f-input" value="<?= sv($s, 'smtp_username') ?>"></div>
        <div><label class="f-label">Password</label><input type="password" name="smtp_password" class="f-input" value="<?= sv($s, 'smtp_password') ?>"></div>
      </div>
      <div class="mb-4">
        <label class="f-label">Encryption</label>
        <select name="smtp_encryption" class="f-select">
          <?php foreach (['tls' => 'TLS (STARTTLS)', 'ssl' => 'SSL', 'none' => 'None'] as $val => $label): ?>
          <option value="<?= $val ?>" <?= (($s['smtp_encryption'] ?? 'tls') === $val) ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div><label class="f-label">From Email</label><input type="email" name="smtp_from_email" class="f-input" value="<?= sv($s, 'smtp_from_email') ?>" placeholder="<?= sv($s, 'contact_email') ?: 'noreply@yoursite.com' ?>"></div>
        <div><label class="f-label">From Name</label><input type="text" name="smtp_from_name" class="f-input" value="<?= sv($s, 'smtp_from_name') ?>" placeholder="<?= sv($s, 'site_name') ?>"></div>
      </div>
      <div class="flex items-center gap-2">
        <input type="email" id="testEmailTo" class="f-input" placeholder="Send a test to…" value="<?= sv($s, 'contact_email') ?>">
        <button type="button" onclick="sendTestEmail()" id="testEmailBtn" class="btn-outline shrink-0"><i class="fa-solid fa-paper-plane"></i> Test</button>
      </div>
      <p id="testEmailResult" class="text-sm font-medium mt-2"></p>
    </div>

    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-4"><i class="fa-solid fa-bell text-brand mr-1.5"></i>Notifications</h2>
      <div class="space-y-4">
        <label class="flex items-start gap-3">
          <input type="checkbox" name="email_order_confirmation_enabled" value="1" class="w-4 h-4 rounded accent-brand mt-0.5" <?= (($s['email_order_confirmation_enabled'] ?? '1') === '1') ? 'checked' : '' ?>>
          <span><span class="text-sm font-semibold text-slate-700 block">Order confirmation to customer</span><span class="text-xs text-slate-400">Sent right after checkout, with a link to their invoice.</span></span>
        </label>
        <label class="flex items-start gap-3">
          <input type="checkbox" name="email_order_status_update_enabled" value="1" class="w-4 h-4 rounded accent-brand mt-0.5" <?= (($s['email_order_status_update_enabled'] ?? '1') === '1') ? 'checked' : '' ?>>
          <span><span class="text-sm font-semibold text-slate-700 block">Order status update to customer</span><span class="text-xs text-slate-400">Sent when you change an order's status (paid, shipped, delivered, etc.) in Orders.</span></span>
        </label>
        <label class="flex items-start gap-3">
          <input type="checkbox" name="email_admin_new_order_enabled" value="1" class="w-4 h-4 rounded accent-brand mt-0.5" <?= (($s['email_admin_new_order_enabled'] ?? '1') === '1') ? 'checked' : '' ?>>
          <span><span class="text-sm font-semibold text-slate-700 block">New order alert to you</span><span class="text-xs text-slate-400">Sent to your Contact Email whenever an order comes in.</span></span>
        </label>
        <label class="flex items-start gap-3">
          <input type="checkbox" name="email_admin_bulk_inquiry_enabled" value="1" class="w-4 h-4 rounded accent-brand mt-0.5" <?= (($s['email_admin_bulk_inquiry_enabled'] ?? '1') === '1') ? 'checked' : '' ?>>
          <span><span class="text-sm font-semibold text-slate-700 block">Bulk inquiry alert to you</span><span class="text-xs text-slate-400">Sent when someone submits the Contact Us / bulk quote form.</span></span>
        </label>
        <label class="flex items-start gap-3">
          <input type="checkbox" name="abandoned_cart_reminder_enabled" value="1" class="w-4 h-4 rounded accent-brand mt-0.5" <?= (($s['abandoned_cart_reminder_enabled'] ?? '1') === '1') ? 'checked' : '' ?>>
          <span><span class="text-sm font-semibold text-slate-700 block">Abandoned cart recovery to customer</span><span class="text-xs text-slate-400">A one-time "you left something in your cart" email with a link that restores it. Requires a cron job — see <code>cron/send_abandoned_cart_reminders.php</code>.</span></span>
        </label>
      </div>
      <div class="mt-4 max-w-xs">
        <label class="f-label">Send abandoned cart reminder after</label>
        <div class="flex items-center gap-2">
          <input type="number" name="abandoned_cart_reminder_hours" min="1" class="f-input" value="<?= sv($s, 'abandoned_cart_reminder_hours') ?: '3' ?>">
          <span class="text-sm text-slate-500 whitespace-nowrap">hours of inactivity</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Integrations -->
  <div class="tab-panel" data-panel="integrations" style="display:none">
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-plug text-brand mr-1.5"></i>Tracking &amp; Verification</h2>
      <p class="f-hint mb-5">These load automatically on every public page once filled in.</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label class="f-label">Google Analytics (GA4) Measurement ID</label><input type="text" name="ga_measurement_id" class="f-input" value="<?= sv($s, 'ga_measurement_id') ?>" placeholder="G-XXXXXXXXXX"></div>
        <div><label class="f-label">Facebook Pixel ID</label><input type="text" name="fb_pixel_id" class="f-input" value="<?= sv($s, 'fb_pixel_id') ?>" placeholder="XXXXXXXXXXXXXXX"></div>
      </div>
      <div class="mt-4"><label class="f-label">Google Search Console Verification Code</label><input type="text" name="gsc_verification" class="f-input" value="<?= sv($s, 'gsc_verification') ?>" placeholder="Content value from the meta tag Google gives you"></div>
    </div>
  </div>

  <!-- Homepage SEO -->
  <div class="tab-panel" data-panel="seo" style="display:none">
    <div class="card p-6 border-emerald-100 bg-emerald-50/30">
      <h2 class="font-bold text-slate-800 mb-5"><i class="fa-solid fa-magnifying-glass-chart text-brand mr-1.5"></i>Homepage SEO &amp; Content</h2>
      <div class="mb-4"><label class="f-label">Homepage Meta Title</label><input type="text" name="homepage_meta_title" maxlength="70" class="f-input" value="<?= sv($s, 'homepage_meta_title') ?>"></div>
      <div class="mb-4"><label class="f-label">Homepage Meta Description</label><textarea name="homepage_meta_description" rows="2" maxlength="160" class="f-textarea"><?= sv($s, 'homepage_meta_description') ?></textarea></div>
      <div class="mb-4"><label class="f-label">Intro Section Title</label><input type="text" name="homepage_intro_title" class="f-input" value="<?= sv($s, 'homepage_intro_title') ?>"></div>
      <div><label class="f-label">Intro Section Content</label><textarea name="homepage_intro_content" rows="3" class="f-textarea"><?= sv($s, 'homepage_intro_content') ?></textarea></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
      <div class="card p-6">
        <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-sitemap text-brand mr-1.5"></i>Organization &amp; Products Schema</h2>
        <p class="f-hint mb-4">Feeds Google's structured data (Knowledge Panel, Product rich results, Merchant listings).</p>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="f-label">Business type</label>
            <select name="seo_business_type" class="f-select">
              <?php foreach (['Organization', 'OnlineStore', 'SportingGoodsStore', 'Store', 'LocalBusiness', 'Corporation'] as $bt): ?>
              <option value="<?= $bt ?>" <?= ($s['seo_business_type'] ?? 'Organization') === $bt ? 'selected' : '' ?>><?= $bt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div><label class="f-label">Founding year</label><input type="text" name="seo_founding_year" class="f-input" maxlength="4" value="<?= sv($s, 'seo_founding_year') ?>" placeholder="e.g. 1998"></div>
          <div><label class="f-label">Default brand</label><input type="text" name="seo_default_brand" class="f-input" value="<?= sv($s, 'seo_default_brand') ?>" placeholder="<?= sv($s, 'site_name') ?>"></div>
          <div><label class="f-label">X / Twitter handle</label><input type="text" name="seo_twitter_handle" class="f-input" value="<?= sv($s, 'seo_twitter_handle') ?>" placeholder="@builtcosports"></div>
          <div><label class="f-label">Ships from (country code)</label><input type="text" name="seo_shipping_country" maxlength="2" style="text-transform:uppercase" class="f-input" value="<?= sv($s, 'seo_shipping_country') ?: 'PK' ?>"></div>
          <div><label class="f-label">Price valid for (days)</label><input type="number" min="30" name="seo_price_valid_days" class="f-input" value="<?= sv($s, 'seo_price_valid_days') ?: '365' ?>"></div>
          <div><label class="f-label">Handling time (max days)</label><input type="number" min="0" name="seo_handling_days_max" class="f-input" value="<?= sv($s, 'seo_handling_days_max') ?: '3' ?>"></div>
          <div><label class="f-label">Transit time (max days)</label><input type="number" min="1" name="seo_transit_days_max" class="f-input" value="<?= sv($s, 'seo_transit_days_max') ?: '7' ?>"></div>
          <div><label class="f-label">Return window (days, 0 = none)</label><input type="number" min="0" name="seo_return_days" class="f-input" value="<?= sv($s, 'seo_return_days') ?: '14' ?>"></div>
          <div>
            <label class="f-label">Return shipping</label>
            <select name="seo_return_fees" class="f-select">
              <?php foreach (['FreeReturn' => 'Free returns', 'ReturnFeesCustomerResponsibility' => 'Customer pays', 'ReturnShippingFees' => 'Fixed return fee'] as $k => $lbl): ?>
              <option value="<?= $k ?>" <?= ($s['seo_return_fees'] ?? 'FreeReturn') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="card p-6">
        <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-shield-halved text-brand mr-1.5"></i>Search engine verification &amp; indexing</h2>
        <p class="f-hint mb-4">Google Search Console is under Integrations. Paste only the <code>content</code> value of each meta tag.</p>
        <div class="mb-4"><label class="f-label">Bing Webmaster</label><input type="text" name="seo_bing_verification" class="f-input" value="<?= sv($s, 'seo_bing_verification') ?>"></div>
        <div class="mb-4"><label class="f-label">Pinterest</label><input type="text" name="seo_pinterest_verification" class="f-input" value="<?= sv($s, 'seo_pinterest_verification') ?>"></div>
        <div class="mb-4"><label class="f-label">Yandex</label><input type="text" name="seo_yandex_verification" class="f-input" value="<?= sv($s, 'seo_yandex_verification') ?>"></div>
        <label class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50/50 p-3 cursor-pointer">
          <input type="checkbox" name="seo_noindex_site" value="1" class="w-4 h-4 rounded accent-brand mt-0.5" <?= ($s['seo_noindex_site'] ?? '0') === '1' ? 'checked' : '' ?>>
          <span class="text-sm"><strong>Hide whole site from search engines</strong><br><span class="text-xs text-slate-500">Adds <code>noindex</code> to every page — only for staging/test copies. Leave OFF on the live store.</span></span>
        </label>
        <div class="mt-4 text-xs text-slate-500 space-y-1">
          <p><i class="fa-solid fa-link mr-1"></i>Sitemap index: <a href="<?= url('sitemap.xml') ?>" target="_blank" class="text-brand font-semibold"><?= url('sitemap.xml') ?></a></p>
          <p><i class="fa-solid fa-robot mr-1"></i>robots.txt: <a href="<?= url('robots.txt') ?>" target="_blank" class="text-brand font-semibold"><?= url('robots.txt') ?></a> · llms.txt: <a href="<?= url('llms.txt') ?>" target="_blank" class="text-brand font-semibold">view</a></p>
          <p><i class="fa-solid fa-rss mr-1"></i>Blog RSS feed: <a href="<?= url('blog/feed') ?>" target="_blank" class="text-brand font-semibold"><?= url('blog/feed') ?></a></p>
        </div>
      </div>
    </div>
  </div>

  <!-- 3D & Design -->
  <div class="tab-panel grid grid-cols-1 lg:grid-cols-2 gap-6" data-panel="studio" style="display:none">
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-5"><i class="fa-solid fa-cube text-brand mr-1.5"></i>3D Design Studio</h2>
      <label class="flex items-start gap-3 mb-4 cursor-pointer">
        <input type="checkbox" name="studio_enabled_3d" value="1" class="w-4 h-4 rounded accent-brand mt-0.5" <?= ($s['studio_enabled_3d'] ?? '1') === '1' ? 'checked' : '' ?>>
        <span class="text-sm"><strong>Enable 3D preview in the product customizer</strong><br><span class="text-xs text-slate-500">When off, shoppers design on the product photo only. Per-product model is set on each product.</span></span>
      </label>
      <label class="flex items-start gap-3 mb-4 cursor-pointer">
        <input type="checkbox" name="hero_3d_enabled" value="1" class="w-4 h-4 rounded accent-brand mt-0.5" <?= ($s['hero_3d_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
        <span class="text-sm"><strong>Interactive 3D model in the homepage hero</strong><br><span class="text-xs text-slate-500">Loaded after the page is interactive, so it doesn't slow down first paint.</span></span>
      </label>
      <div class="max-w-xs">
        <label class="f-label">Hero 3D model</label>
        <select name="hero_3d_model" class="f-select">
          <option value="jersey" <?= ($s['hero_3d_model'] ?? 'jersey') === 'jersey' ? 'selected' : '' ?>>Team jersey</option>
          <option value="ball" <?= ($s['hero_3d_model'] ?? '') === 'ball' ? 'selected' : '' ?>>Football</option>
        </select>
      </div>
    </div>
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-5"><i class="fa-solid fa-bullhorn text-brand mr-1.5"></i>Announcement bar</h2>
      <label class="f-label">Scrolling announcement text</label>
      <input type="text" name="announcement_text" maxlength="160" class="f-input" value="<?= sv($s, 'announcement_text') ?>" placeholder="Looking to buy in bulk? Contact us for wholesale & custom pricing">
      <p class="f-hint">Shown in the ticker above the header on every page.</p>
    </div>
  </div>

  <!-- Social -->
  <div class="tab-panel" data-panel="social" style="display:none">
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-5"><i class="fa-solid fa-share-nodes text-brand mr-1.5"></i>Social Media</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php foreach (['social_facebook' => 'Facebook', 'social_instagram' => 'Instagram', 'social_twitter' => 'X / Twitter', 'social_youtube' => 'YouTube', 'social_tiktok' => 'TikTok'] as $k => $label): ?>
        <div><label class="f-label"><?= $label ?></label><input type="text" name="<?= $k ?>" class="f-input" value="<?= sv($s, $k) ?>" placeholder="https://..."></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Advanced -->
  <div class="tab-panel space-y-6" data-panel="advanced" style="display:none">
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-robot text-brand mr-1.5"></i>robots.txt Override</h2>
      <p class="f-hint mb-3">Leave blank to use the default (allow all, block /admin/). Advanced use only.</p>
      <textarea name="robots_txt" rows="5" class="f-textarea font-mono text-xs"><?= sv($s, 'robots_txt') ?></textarea>
    </div>
    <div class="card p-6">
      <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-code text-brand mr-1.5"></i>Custom Scripts</h2>
      <p class="f-hint mb-4">Raw HTML/JS injected as-is — only paste code from sources you trust.</p>
      <div class="mb-4"><label class="f-label">Header Scripts <span class="text-slate-400 font-normal">(before &lt;/head&gt;)</span></label><textarea name="custom_header_scripts" rows="4" class="f-textarea font-mono text-xs"><?= sv($s, 'custom_header_scripts') ?></textarea></div>
      <div><label class="f-label">Footer Scripts <span class="text-slate-400 font-normal">(before &lt;/body&gt;)</span></label><textarea name="custom_footer_scripts" rows="4" class="f-textarea font-mono text-xs"><?= sv($s, 'custom_footer_scripts') ?></textarea></div>
    </div>
  </div>

  <button type="submit" class="btn-primary mt-6"><i class="fa-solid fa-floppy-disk"></i> Save All Changes</button>
</form>

<script>
function showTab(key) {
  document.querySelectorAll('.tab-panel').forEach(p => p.style.display = p.dataset.panel === key ? '' : 'none');
  document.querySelectorAll('.tab-btn').forEach(b => {
    const active = b.dataset.tab === key;
    b.classList.toggle('border-brand', active); b.classList.toggle('text-brand', active);
    b.classList.toggle('border-transparent', !active); b.classList.toggle('text-slate-500', !active);
  });
  document.getElementById('activeTabInput').value = key;
  history.replaceState(null, '', '?tab=' + key);
}
showTab(<?= json_encode($active_tab) ?>);

const csrfToken = <?= json_encode(csrf_token()) ?>;
async function sendTestEmail() {
  const btn = document.getElementById('testEmailBtn');
  const result = document.getElementById('testEmailResult');
  const to = document.getElementById('testEmailTo').value;
  if (!to) { result.textContent = 'Enter an email to send the test to.'; result.className = 'text-sm font-medium mt-2 text-red-600'; return; }
  btn.disabled = true; result.textContent = 'Sending…'; result.className = 'text-sm font-medium mt-2 text-slate-400';
  const fd = new FormData();
  fd.append('csrf_token', csrfToken);
  fd.append('to', to);
  ['smtp_host','smtp_port','smtp_username','smtp_password','smtp_encryption','smtp_from_email','smtp_from_name'].forEach(name => {
    const el = document.querySelector(`[name="${name}"]`);
    if (el) fd.append(name, el.value);
  });
  try {
    const res = await fetch('email-test.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { result.textContent = '✓ Sent! Check the inbox.'; result.className = 'text-sm font-medium mt-2 text-emerald-600'; }
    else { result.textContent = data.error || 'Failed.'; result.className = 'text-sm font-medium mt-2 text-red-600'; }
  } catch (e) { result.textContent = 'Request failed.'; result.className = 'text-sm font-medium mt-2 text-red-600'; }
  btn.disabled = false;
}
</script>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
