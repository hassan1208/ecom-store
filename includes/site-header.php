<?php
// includes/site-header.php — shared public storefront header (topbar + navbar)
// Expects $page_title, $meta_description, optionally $canonical_url, $og_image already set by the caller.

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

$meta_title       = $meta_title ?? ($site_name . ' | ' . setting('site_tagline', ''));
$meta_description = $meta_description ?? setting('homepage_meta_description', '');
$canonical_url    = $canonical_url ?? current_full_url();
// Fallback chain: page-specific image (e.g. a category's own photo) -> dedicated
// site-wide social share image (Settings) -> logo, so social links always have a picture.
$site_og_image    = setting('og_image', '');
$og_image         = $og_image ?? ($site_og_image ? UPLOAD_URL . $site_og_image : ($logo ? UPLOAD_URL . $logo : ''));
$menu             = get_menu_tree();

track_visit($_SERVER['REQUEST_URI'] ?? '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($meta_title) ?></title>
<meta name="description" content="<?= h($meta_description) ?>">
<link rel="canonical" href="<?= h($canonical_url) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= h($site_name) ?>">
<meta property="og:title" content="<?= h($meta_title) ?>">
<meta property="og:description" content="<?= h($meta_description) ?>">
<meta property="og:url" content="<?= h($canonical_url) ?>">
<?php if ($og_image): ?>
<meta property="og:image" content="<?= h($og_image) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($meta_title) ?>">
<meta name="twitter:description" content="<?= h($meta_description) ?>">
<?php if ($og_image): ?><meta name="twitter:image" content="<?= h($og_image) ?>"><?php endif; ?>
<?php if (!empty($meta_robots)): ?><meta name="robots" content="<?= h($meta_robots) ?>"><?php endif; ?>
<?php if ($logo): ?><link rel="icon" href="<?= UPLOAD_URL . h(setting('site_favicon') ?: $logo) ?>"><?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Anton&family=Bebas+Neue&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { theme: { extend: {
    fontFamily: { display: ['Oswald', 'sans-serif'], sans: ['Inter', 'sans-serif'] },
    colors: {
      ink: <?= json_encode(setting('theme_ink_color', '#0b0f14')) ?>,
      ignite: { DEFAULT: <?= json_encode(setting('theme_primary_color', '#ff4d2e')) ?>, dark: <?= json_encode(setting('theme_primary_dark', '#e0391d')) ?> },
    },
  } } };
</script>

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $site_name,
    'url' => SITE_URL,
    'logo' => $logo ? (UPLOAD_URL . $logo) : null,
    'telephone' => $phone ?: null,
    'email' => $email ?: null,
    'sameAs' => array_values(array_filter($socials)),
], JSON_UNESCAPED_SLASHES) ?>
</script>

<?php $gsc = setting('gsc_verification', ''); if ($gsc): $gsc = preg_replace('/^google-site-verification=/i', '', trim($gsc)); ?><meta name="google-site-verification" content="<?= h($gsc) ?>"><?php endif; ?>

<?php $ga_id = setting('ga_measurement_id', ''); if ($ga_id): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= h($ga_id) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', <?= json_encode($ga_id) ?>);
</script>
<?php endif; ?>

<?php $fb_pixel = setting('fb_pixel_id', ''); if ($fb_pixel): ?>
<script>
  !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', <?= json_encode($fb_pixel) ?>);
  fbq('track', 'PageView');
</script>
<?php endif; ?>

<?php $header_scripts = setting('custom_header_scripts', ''); if ($header_scripts) echo $header_scripts; ?>
</head>
<body class="font-sans text-ink antialiased">

<?php if ($phone || $email || array_filter($socials)): ?>
<div class="bg-ink text-white/70 text-xs">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 h-9 flex items-center justify-between">
    <div class="flex items-center gap-5">
      <?php if ($phone): ?><a href="tel:<?= h(preg_replace('/\s+/', '', $phone)) ?>" class="hover:text-white flex items-center gap-1.5"><i class="fa-solid fa-phone"></i> <?= h($phone) ?></a><?php endif; ?>
      <?php if ($email): ?><a href="mailto:<?= h($email) ?>" class="hover:text-white hidden sm:flex items-center gap-1.5"><i class="fa-solid fa-envelope"></i> <?= h($email) ?></a><?php endif; ?>
    </div>
    <div class="flex items-center gap-3">
      <?php foreach ($socials as $key => $link): if (!$link) continue; ?>
      <a href="<?= h($link) ?>" target="_blank" rel="noopener" class="hover:text-ignite transition"><i class="<?= $social_icons[$key] ?>"></i></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Bulk inquiry scrolling ticker -->
<div class="marquee-wrap relative bg-ignite text-white overflow-hidden whitespace-nowrap" style="height:34px">
  <div class="marquee-track flex items-center h-full">
    <?php for ($i = 0; $i < 2; $i++): ?>
    <a href="<?= url('contact') ?>" class="inline-flex items-center gap-2 px-8 text-xs font-semibold tracking-wide hover:underline">
      <i class="fa-solid fa-star text-[10px] marquee-star"></i> <i class="fa-solid fa-boxes-stacked"></i> Looking to buy in bulk? Contact us for wholesale &amp; custom pricing — visit our Contact Us page &rarr; <i class="fa-solid fa-star text-[10px] marquee-star"></i>
    </a>
    <a href="<?= url('contact') ?>" class="inline-flex items-center gap-2 px-8 text-xs font-semibold tracking-wide hover:underline">
      <i class="fa-solid fa-star text-[10px] marquee-star"></i> <i class="fa-solid fa-boxes-stacked"></i> Looking to buy in bulk? Contact us for wholesale &amp; custom pricing — visit our Contact Us page &rarr; <i class="fa-solid fa-star text-[10px] marquee-star"></i>
    </a>
    <?php endfor; ?>
  </div>
  <div class="marquee-shine"></div>
</div>
<style>
  @keyframes marqueeScroll { 0% { transform: translateX(0%); } 100% { transform: translateX(-50%); } }
  .marquee-track { width: max-content; animation: marqueeScroll 34s linear infinite; }

  @keyframes marqueeShine { 0% { left: -30%; } 100% { left: 130%; } }
  .marquee-shine {
    position: absolute; top: 0; bottom: 0; left: -30%; width: 22%;
    background: linear-gradient(100deg, transparent, rgba(255,255,255,0.35) 45%, rgba(255,255,255,0.55) 50%, rgba(255,255,255,0.35) 55%, transparent);
    animation: marqueeShine 3.2s ease-in-out infinite;
    pointer-events: none;
  }

  @keyframes marqueeTwinkle { 0%, 100% { opacity: 0.5; transform: scale(0.85); } 50% { opacity: 1; transform: scale(1.15); } }
  .marquee-star { animation: marqueeTwinkle 1.6s ease-in-out infinite; }
  .marquee-star:nth-child(1) { animation-delay: 0s; }
</style>

<header class="bg-white/95 backdrop-blur border-b border-black/5 sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
    <a href="<?= url('') ?>" class="flex items-center gap-2 shrink-0">
      <?php if ($logo): ?>
        <img src="<?= UPLOAD_URL . h($logo) ?>" alt="<?= h($site_name) ?>" class="h-9 w-auto">
      <?php else: $name_words = explode(' ', strtoupper($site_name)); $last_word = array_pop($name_words); ?>
        <span class="font-display font-bold text-xl tracking-wide">
          <?= h(implode(' ', $name_words)) ?> <span class="text-ignite"><?= h($last_word) ?></span>
        </span>
      <?php endif; ?>
    </a>

    <nav class="hidden lg:flex items-center gap-8 font-display text-sm font-medium tracking-wide uppercase">
      <?php foreach ($menu as $item): ?>
      <div class="relative group">
        <a href="<?= h($item['url']) ?>" <?= $item['open_new_tab'] ? 'target="_blank" rel="noopener"' : '' ?> class="flex items-center gap-1 py-6 hover:text-ignite transition">
          <?= h($item['label']) ?>
          <?php if (!empty($item['children'])): ?><i class="fa-solid fa-chevron-down text-[10px] mt-0.5"></i><?php endif; ?>
        </a>
        <?php if (!empty($item['children'])): ?>
        <div class="absolute left-0 top-full w-56 bg-white shadow-xl rounded-lg border border-black/5 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition normal-case font-sans">
          <?php foreach ($item['children'] as $child): ?>
          <a href="<?= h($child['url']) ?>" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-ignite"><?= h($child['label']) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </nav>

    <div class="flex items-center gap-4">
      <button id="searchToggleBtn" class="w-9 h-9 flex items-center justify-center text-lg hover:text-ignite transition">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
      <a href="<?= is_customer() ? url('account') : url('login') ?>" class="w-9 h-9 flex items-center justify-center text-lg hover:text-ignite transition" title="<?= is_customer() ? 'My Account' : 'Sign In' ?>">
        <i class="fa-solid fa-user"></i>
      </a>
      <?php if (is_customer()): ?>
      <a href="<?= url('wishlist') ?>" class="relative w-9 h-9 flex items-center justify-center text-lg hover:text-ignite transition" title="My Wishlist">
        <i class="fa-solid fa-heart"></i>
        <?php $wishlist_count = get_wishlist_count(); if ($wishlist_count > 0): ?>
        <span class="absolute -top-0.5 -right-0.5 bg-ignite text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center"><?= $wishlist_count > 9 ? '9+' : $wishlist_count ?></span>
        <?php endif; ?>
      </a>
      <?php endif; ?>
      <a href="<?= url('cart') ?>" class="relative w-9 h-9 flex items-center justify-center text-lg hover:text-ignite transition">
        <i class="fa-solid fa-cart-shopping"></i>
        <?php $cart_count = cart_count(); if ($cart_count > 0): ?>
        <span class="absolute -top-0.5 -right-0.5 bg-ignite text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center"><?= $cart_count > 9 ? '9+' : $cart_count ?></span>
        <?php endif; ?>
      </a>
      <button id="mobileMenuBtn" class="lg:hidden w-9 h-9 flex items-center justify-center text-lg"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>

  <!-- Search bar (toggled) -->
  <div id="searchBar" class="hidden border-t border-black/5 bg-white">
    <form method="GET" action="<?= url('search') ?>" class="max-w-7xl mx-auto px-4 sm:px-6 py-3">
      <div class="relative">
        <input type="text" name="q" placeholder="Search products..." autocomplete="off"
               class="w-full rounded-full border border-slate-300 pl-5 pr-12 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite">
        <button type="submit" class="absolute right-1.5 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-ignite hover:bg-ignite-dark text-white flex items-center justify-center transition">
          <i class="fa-solid fa-magnifying-glass text-xs"></i>
        </button>
      </div>
    </form>
  </div>

  <!-- Mobile menu -->
  <div id="mobileMenu" class="hidden lg:hidden border-t border-black/5 bg-white">
    <div class="px-4 py-3 space-y-1">
      <?php foreach ($menu as $item): ?>
      <a href="<?= h($item['url']) ?>" class="block py-2 font-display font-medium uppercase text-sm"><?= h($item['label']) ?></a>
        <?php foreach ($item['children'] as $child): ?>
        <a href="<?= h($child['url']) ?>" class="block py-1.5 pl-4 text-sm text-slate-500"><?= h($child['label']) ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  </div>
</header>
<script>
document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
  document.getElementById('mobileMenu').classList.toggle('hidden');
});
document.getElementById('searchToggleBtn')?.addEventListener('click', () => {
  const bar = document.getElementById('searchBar');
  bar.classList.toggle('hidden');
  if (!bar.classList.contains('hidden')) bar.querySelector('input').focus();
});
</script>
