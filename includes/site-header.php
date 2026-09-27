<?php
// includes/site-header.php — shared public storefront <head> + header.
// Pages may set before including: $meta_title, $meta_description, $canonical_url,
// $og_image, $og_image_alt, $og_type ('website'|'product'|'article'), $og_extra
// (array of [property => content]), $meta_robots, $page_schema (array of JSON-LD
// objects), $pagination_prev / $pagination_next (URLs), $body_class, $extra_head.

$site_name   = setting('site_name', 'BuiltCo Sports');
$logo        = setting('site_logo', '');
$phone       = setting('contact_phone', '');
$email       = setting('contact_email', '');
$socials     = [
    'facebook'  => setting('social_facebook', ''),
    'instagram' => setting('social_instagram', ''),
    'twitter'   => setting('social_twitter', ''),
    'youtube'   => setting('social_youtube', ''),
    'tiktok'    => setting('social_tiktok', ''),
];
$social_icons = ['facebook' => 'fa-brands fa-facebook-f', 'instagram' => 'fa-brands fa-instagram', 'twitter' => 'fa-brands fa-x-twitter', 'youtube' => 'fa-brands fa-youtube', 'tiktok' => 'fa-brands fa-tiktok'];

$meta_title       = meta_text($meta_title ?? ($site_name . ' | ' . setting('site_tagline', '')), 90);
$meta_description = meta_text($meta_description ?? setting('homepage_meta_description', ''), 170);
$canonical_url    = $canonical_url ?? clean_canonical();
$site_og_image    = setting('og_image', '');
$og_image         = $og_image ?? ($site_og_image ? UPLOAD_URL . $site_og_image : ($logo ? UPLOAD_URL . $logo : ''));
$og_type          = $og_type ?? 'website';
$is_home          = rtrim(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), '/') === rtrim(parse_url(SITE_URL, PHP_URL_PATH) ?? '', '/');
// Site-wide "noindex" switch (Settings → SEO) for staging copies; otherwise let
// Google show large image previews and full snippets.
$meta_robots      = setting('seo_noindex_site', '0') === '1'
    ? 'noindex, nofollow'
    : ($meta_robots ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
$twitter_handle   = ltrim(setting('seo_twitter_handle', ''), '@');
$theme_ink        = setting('theme_ink_color', '#0b0f14');
$menu             = get_menu_tree();
$cart_count       = cart_count();
$announcement     = setting('announcement_text', 'Looking to buy in bulk? Contact us for wholesale & custom pricing');

track_visit($_SERVER['REQUEST_URI'] ?? '/');

$org_schema = [
    '@context' => 'https://schema.org',
    '@type' => setting('seo_business_type', 'Organization') ?: 'Organization',
    '@id' => SITE_URL . '/#organization',
    'name' => $site_name,
    'url' => url(''),
    'logo' => $logo ? UPLOAD_URL . $logo : null,
    'image' => $og_image ?: null,
    'description' => meta_text(setting('footer_about_text', ''), 300) ?: null,
    'telephone' => $phone ?: null,
    'email' => $email ?: null,
    'foundingDate' => setting('seo_founding_year', '') ?: null,
    'address' => setting('contact_address', '') ? ['@type' => 'PostalAddress', 'streetAddress' => setting('contact_address', ''), 'addressCountry' => setting('seo_shipping_country', 'PK')] : null,
    'contactPoint' => ($phone || $email) ? [[
        '@type' => 'ContactPoint', 'contactType' => 'customer service',
        'telephone' => $phone ?: null, 'email' => $email ?: null, 'availableLanguage' => ['English', 'Urdu'],
    ]] : null,
    'sameAs' => array_values(array_filter($socials)),
];
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= h($meta_title) ?></title>
<meta name="description" content="<?= h($meta_description) ?>">
<meta name="robots" content="<?= h($meta_robots) ?>">
<link rel="canonical" href="<?= h($canonical_url) ?>">
<link rel="alternate" hreflang="en" href="<?= h($canonical_url) ?>">
<link rel="alternate" hreflang="x-default" href="<?= h($canonical_url) ?>">
<?php if (!empty($pagination_prev)): ?><link rel="prev" href="<?= h($pagination_prev) ?>"><?php endif; ?>
<?php if (!empty($pagination_next)): ?><link rel="next" href="<?= h($pagination_next) ?>"><?php endif; ?>
<meta name="theme-color" content="<?= h($theme_ink) ?>">
<meta name="format-detection" content="telephone=no">
<meta name="application-name" content="<?= h($site_name) ?>">
<meta name="apple-mobile-web-app-title" content="<?= h($site_name) ?>">
<link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
<link rel="alternate" type="application/rss+xml" title="<?= h($site_name) ?> Blog" href="<?= url('blog/feed') ?>">

<meta property="og:type" content="<?= h($og_type) ?>">
<meta property="og:site_name" content="<?= h($site_name) ?>">
<meta property="og:locale" content="en_US">
<meta property="og:title" content="<?= h($meta_title) ?>">
<meta property="og:description" content="<?= h($meta_description) ?>">
<meta property="og:url" content="<?= h($canonical_url) ?>">
<?php if ($og_image): ?>
<meta property="og:image" content="<?= h($og_image) ?>">
<meta property="og:image:alt" content="<?= h($og_image_alt ?? $meta_title) ?>">
<?php endif; ?>
<?php foreach (($og_extra ?? []) as $prop => $content): if ($content === null || $content === '') continue; ?>
<meta property="<?= h($prop) ?>" content="<?= h($content) ?>">
<?php endforeach; ?>
<meta name="twitter:card" content="summary_large_image">
<?php if ($twitter_handle): ?><meta name="twitter:site" content="@<?= h($twitter_handle) ?>"><?php endif; ?>
<meta name="twitter:title" content="<?= h($meta_title) ?>">
<meta name="twitter:description" content="<?= h($meta_description) ?>">
<?php if ($og_image): ?><meta name="twitter:image" content="<?= h($og_image) ?>"><?php endif; ?>

<?php $favicon = setting('site_favicon') ?: $logo; if ($favicon): ?>
<link rel="icon" href="<?= UPLOAD_URL . h($favicon) ?>">
<link rel="apple-touch-icon" href="<?= UPLOAD_URL . h($favicon) ?>">
<?php else: ?>
<link rel="icon" href="<?= asset_url('assets/images/favicon.svg') ?>" type="image/svg+xml">
<?php endif; ?>

<?php $gsc = setting('gsc_verification', ''); if ($gsc): $gsc = preg_replace('/^google-site-verification=/i', '', trim($gsc)); ?><meta name="google-site-verification" content="<?= h($gsc) ?>"><?php endif; ?>
<?php if ($v = setting('seo_bing_verification', '')): ?><meta name="msvalidate.01" content="<?= h($v) ?>"><?php endif; ?>
<?php if ($v = setting('seo_pinterest_verification', '')): ?><meta name="p:domain_verify" content="<?= h($v) ?>"><?php endif; ?>
<?php if ($v = setting('seo_yandex_verification', '')): ?><meta name="yandex-verification" content="<?= h($v) ?>"><?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700;800&family=Anton&family=Bebas+Neue&display=swap">
<link rel="preload" href="<?= SITE_URL ?>/assets/vendor/fontawesome/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset_url('assets/vendor/fontawesome/css/all.min.css') ?>">
<link rel="stylesheet" href="<?= asset_url('assets/css/site.css') ?>">
<style>
  :root {
    --c-ink: <?= hex_to_rgb_triplet($theme_ink, '11 15 20') ?>;
    --c-ignite: <?= hex_to_rgb_triplet(setting('theme_primary_color', '#ff4d2e')) ?>;
    --c-ignite-dark: <?= hex_to_rgb_triplet(setting('theme_primary_dark', '#e0391d'), '224 57 29') ?>;
  }
</style>
<script>
  window.SITE_CONFIG = <?= json_encode([
      'base' => SITE_URL,
      'threeUrl' => asset_url('assets/vendor/three/three.min.js'),
      'garmentUrl' => asset_url('assets/js/garment3d.js'),
      'suggestUrl' => url('search'),
  ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
</script>
<?= $extra_head ?? '' ?>

<?= json_ld($org_schema) ?>
<?php if ($is_home): ?>
<?= json_ld([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    '@id' => SITE_URL . '/#website',
    'name' => $site_name,
    'url' => url(''),
    'publisher' => ['@id' => SITE_URL . '/#organization'],
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('search') . '?q={search_term_string}'],
        'query-input' => 'required name=search_term_string',
    ],
]) ?>
<?php endif; ?>
<?php foreach (($page_schema ?? []) as $schema) echo json_ld($schema); ?>

<?php $ga_id = setting('ga_measurement_id', ''); if ($ga_id): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= h($ga_id) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', <?= json_encode($ga_id, JSON_HEX_TAG) ?>);
</script>
<?php endif; ?>

<?php $fb_pixel = setting('fb_pixel_id', ''); if ($fb_pixel): ?>
<script>
  !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', <?= json_encode($fb_pixel, JSON_HEX_TAG) ?>);
  fbq('track', 'PageView');
</script>
<?php endif; ?>

<?php $header_scripts = setting('custom_header_scripts', ''); if ($header_scripts) echo $header_scripts; ?>
</head>
<body class="font-sans antialiased <?= h($body_class ?? '') ?>">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:bg-ink focus:text-white focus:px-4 focus:py-2 focus:rounded-lg">Skip to content</a>

<!-- Announcement ticker -->
<div class="marquee-wrap relative bg-ink text-white overflow-hidden whitespace-nowrap text-[11px] sm:text-xs" style="height:36px" role="region" aria-label="Announcements">
  <div class="marquee-track flex items-center h-full">
    <?php for ($i = 0; $i < 4; $i++): ?>
    <a href="<?= url('contact') ?>" class="inline-flex items-center gap-3 px-8 font-semibold tracking-[0.14em] uppercase hover:text-ignite transition"<?= $i ? ' aria-hidden="true" tabindex="-1"' : '' ?>>
      <i class="fa-solid fa-bolt text-ignite"></i> <?= h($announcement) ?>
      <span class="text-white/30">/</span>
      <i class="fa-solid fa-cube text-ignite"></i> Design your kit in 3D
      <span class="text-white/30">/</span>
      <i class="fa-solid fa-earth-asia text-ignite"></i> Worldwide shipping from Sialkot
    </a>
    <?php endfor; ?>
  </div>
</div>

<header class="site-header">
  <div class="site-header-bar">
    <div class="container-x h-full flex items-center justify-between gap-4">
      <a href="<?= url('') ?>" class="flex items-center gap-2 shrink-0" aria-label="<?= h($site_name) ?> — home">
        <?php if ($logo): ?>
          <img src="<?= UPLOAD_URL . h($logo) ?>" alt="<?= h($site_name) ?>" class="h-9 w-auto" width="140" height="36">
        <?php else: $name_words = explode(' ', strtoupper($site_name)); $last_word = array_pop($name_words); ?>
          <span class="w-9 h-9 rounded-xl bg-ink text-white flex items-center justify-center font-poster text-lg -rotate-6 shadow-glow"><?= h(substr($site_name, 0, 1)) ?></span>
          <span class="font-display font-bold text-xl tracking-wide leading-none">
            <?= h(implode(' ', $name_words)) ?> <span class="text-ignite"><?= h($last_word) ?></span>
          </span>
        <?php endif; ?>
      </a>

      <button type="button" data-search-open class="hidden md:flex flex-1 max-w-md mx-6 items-center gap-3 rounded-full bg-black/[0.04] hover:bg-black/[0.07] px-5 h-11 text-sm text-slate-500 transition" aria-label="Search products">
        <i class="fa-solid fa-magnifying-glass"></i> <span class="flex-1 text-left">Search footballs, gloves, kits…</span>
        <kbd class="text-[10px] font-bold border border-slate-300 rounded px-1.5 py-0.5">/</kbd>
      </button>

      <div class="flex items-center gap-1">
        <button type="button" class="icon-btn md:hidden" data-search-open aria-label="Search (press /)"><i class="fa-solid fa-magnifying-glass"></i></button>
        <a href="<?= is_customer() ? url('account') : url('login') ?>" class="icon-btn hidden sm:flex" aria-label="<?= is_customer() ? 'My account' : 'Sign in' ?>"><i class="fa-regular fa-user"></i></a>
        <?php if (is_customer()): $wishlist_count = get_wishlist_count(); ?>
        <a href="<?= url('wishlist') ?>" class="icon-btn hidden sm:flex" aria-label="Wishlist (<?= $wishlist_count ?>)">
          <i class="fa-regular fa-heart"></i>
          <?php if ($wishlist_count > 0): ?><span class="count-badge"><?= $wishlist_count > 9 ? '9+' : $wishlist_count ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        <a href="<?= url('cart') ?>" class="icon-btn" aria-label="Cart (<?= $cart_count ?> items)">
          <i class="fa-solid fa-bag-shopping"></i>
          <?php if ($cart_count > 0): ?><span class="count-badge"><?= $cart_count > 9 ? '9+' : $cart_count ?></span><?php endif; ?>
        </a>
        <button type="button" id="mobileMenuBtn" class="icon-btn lg:hidden" data-drawer-open aria-controls="mobileDrawer" aria-expanded="false" aria-label="Open menu"><i class="fa-solid fa-bars-staggered"></i></button>
      </div>
    </div>
  </div>
  <div class="site-header-nav hidden lg:block bg-white/85 backdrop-blur-xl border-b border-black/5">
  <nav class="container-x flex items-center justify-center flex-wrap gap-x-7" aria-label="Main">
        <?php foreach ($menu as $item): ?>
        <div class="nav-item relative">
          <a href="<?= h($item['url']) ?>" <?= $item['open_new_tab'] ? 'target="_blank" rel="noopener"' : '' ?> class="nav-link"<?= !empty($item['children']) ? ' aria-haspopup="true"' : '' ?>>
            <?= h($item['label']) ?>
            <?php if (!empty($item['children'])): ?><i class="fa-solid fa-chevron-down text-[9px] opacity-60"></i><?php endif; ?>
          </a>
          <?php if (!empty($item['children'])): ?>
          <div class="nav-dropdown">
            <?php foreach ($item['children'] as $child): ?>
            <a href="<?= h($child['url']) ?>" class="flex items-center justify-between rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-ink group/dd">
              <?= h($child['label']) ?> <i class="fa-solid fa-arrow-right text-[10px] opacity-0 -translate-x-1 group-hover/dd:opacity-100 group-hover/dd:translate-x-0 transition text-ignite"></i>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <a href="<?= url('kit-builder') ?>" class="nav-link !text-ignite"><i class="fa-solid fa-cube text-[11px]"></i> 3D Kit Builder</a>
      </nav>
  </div>
</header>

<!-- Search overlay -->
<div id="searchOverlay" class="search-overlay" role="dialog" aria-modal="true" aria-label="Search products" aria-hidden="true">
  <div class="search-overlay-panel">
    <form method="GET" action="<?= url('search') ?>" role="search" class="relative">
      <label for="siteSearch" class="sr-only">Search products</label>
      <i class="fa-solid fa-magnifying-glass absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
      <input id="siteSearch" type="search" name="q" placeholder="Search footballs, gloves, kits…" autocomplete="off"
             class="w-full rounded-2xl bg-white pl-16 pr-28 py-5 text-lg font-medium text-ink shadow-2xl focus:outline-none focus:ring-4 focus:ring-ignite/30">
      <button type="button" data-search-close class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-ink px-3 py-2 rounded-lg bg-slate-100">Esc</button>
    </form>
    <div id="searchResults" class="mt-3 rounded-2xl bg-white shadow-2xl overflow-hidden empty:hidden max-h-[60vh] overflow-y-auto" aria-live="polite"></div>
  </div>
</div>

<!-- Mobile drawer -->
<div id="mobileDrawer" class="drawer lg:hidden" aria-hidden="true">
  <div class="drawer-backdrop" data-drawer-close></div>
  <nav class="drawer-panel flex flex-col" aria-label="Mobile">
    <div class="flex items-center justify-between px-6 h-16 border-b border-slate-100">
      <span class="font-display font-bold uppercase tracking-wider">Menu</span>
      <button type="button" class="icon-btn" data-drawer-close aria-label="Close menu"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="flex-1 px-4 py-4 space-y-1">
      <?php foreach ($menu as $i => $item): ?>
        <?php if (!empty($item['children'])): ?>
        <button type="button" class="w-full flex items-center justify-between px-3 py-3 font-display font-semibold uppercase tracking-wider text-sm rounded-xl hover:bg-slate-50" data-accordion aria-expanded="false" aria-controls="mnav-<?= $i ?>">
          <?= h($item['label']) ?> <i class="fa-solid fa-chevron-down text-xs transition"></i>
        </button>
        <div id="mnav-<?= $i ?>" hidden class="pl-4 pb-2">
          <a href="<?= h($item['url']) ?>" class="block px-3 py-2 text-sm font-semibold text-ink">All <?= h($item['label']) ?></a>
          <?php foreach ($item['children'] as $child): ?>
          <a href="<?= h($child['url']) ?>" class="block px-3 py-2 text-sm text-slate-500 hover:text-ink"><?= h($child['label']) ?></a>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <a href="<?= h($item['url']) ?>" class="block px-3 py-3 font-display font-semibold uppercase tracking-wider text-sm rounded-xl hover:bg-slate-50"><?= h($item['label']) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
      <a href="<?= url('kit-builder') ?>" class="flex items-center gap-2 px-3 py-3 font-display font-semibold uppercase tracking-wider text-sm rounded-xl text-ignite hover:bg-slate-50"><i class="fa-solid fa-cube"></i> 3D Kit Builder</a>
    </div>
    <div class="border-t border-slate-100 p-4 grid grid-cols-2 gap-2">
      <a href="<?= is_customer() ? url('account') : url('login') ?>" class="btn-outline btn-sm"><i class="fa-regular fa-user"></i> <?= is_customer() ? 'Account' : 'Sign in' ?></a>
      <a href="<?= url('track-order') ?>" class="btn-outline btn-sm"><i class="fa-solid fa-truck-fast"></i> Track</a>
      <?php if ($phone): ?><a href="tel:<?= h(preg_replace('/\s+/', '', $phone)) ?>" class="col-span-2 text-center text-xs text-slate-500 mt-2"><i class="fa-solid fa-phone mr-1"></i><?= h($phone) ?></a><?php endif; ?>
    </div>
  </nav>
</div>
