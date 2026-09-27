<?php
// index.php — Homepage. Every section below is toggled on/off and titled from
// Admin → Homepage Manager.
require_once __DIR__ . '/includes/config.php';

// Newsletter subscribe (this section — and the footer form — post back here)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'subscribe') {
    require_csrf();
    if (subscribe_newsletter(sanitize($_POST['email'] ?? ''))) {
        set_flash('success', "You're subscribed!");
    } else {
        set_flash('error', 'Please enter a valid email address.');
    }
    header('Location: ' . url('') . '#newsletter');
    exit;
}

$meta_title       = setting('homepage_meta_title', setting('site_name', 'BuiltCo Sports'));
$meta_description = setting('homepage_meta_description', '');
$canonical_url    = url('');

$banners  = section_enabled('hero') ? get_active_banners('hero') : [];
$features = section_enabled('trustbar') ? get_active_features() : [];
$cats     = section_enabled('category') ? get_homepage_categories() : [];
$new_arrivals   = section_enabled('new_arrivals') ? fetch_all("SELECT * FROM products WHERE status='active' AND is_new_arrival=1 ORDER BY created_at DESC LIMIT 8") : [];
$featured_prods = section_enabled('featured') ? fetch_all("SELECT * FROM products WHERE status='active' AND is_featured=1 ORDER BY created_at DESC LIMIT 8") : [];
$promo_banners  = section_enabled('promo') ? get_active_banners('promo') : [];
$best_sellers   = section_enabled('bestsellers') ? fetch_all(
    "SELECT p.*, SUM(oi.quantity) AS sold FROM products p
     JOIN order_items oi ON oi.product_id=p.id JOIN orders o ON o.id=oi.order_id
     WHERE p.status='active' AND o.status NOT IN ('cancelled','failed','refunded')
     GROUP BY p.id ORDER BY sold DESC LIMIT 8"
) : [];
$coming_soon    = section_enabled('comingsoon') ? fetch_all("SELECT * FROM products WHERE status='active' AND is_coming_soon=1 ORDER BY created_at DESC LIMIT 8") : [];
$testimonials   = section_enabled('testimonials') ? get_testimonials() : [];
$certifications = section_enabled('certifications') ? get_certifications() : [];
$studio_product = fetch_one("SELECT slug FROM products WHERE status='active' AND is_customizable=1 AND (name LIKE '%jersey%' OR name LIKE '%kit%' OR customizer_model='jersey') ORDER BY is_featured DESC, id DESC LIMIT 1");
$hero_3d        = setting('hero_3d_enabled', '1') === '1';

// Homepage structured data: the featured products as an ItemList (eligible
// for rich carousels) — only products actually shown on the page.
$list_products = $featured_prods ?: $new_arrivals;
if ($list_products) {
    $page_schema[] = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => setting('section_featured_title', 'Featured Products'),
        'itemListElement' => array_map(fn($p, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => url('product/' . $p['slug']), 'name' => $p['name']], $list_products, array_keys($list_products)),
    ];
}

$hero_first = $banners[0] ?? null;
if ($hero_first && !empty($hero_first['image'])) {
    $extra_head = '<link rel="preload" as="image" href="' . h(UPLOAD_URL . $hero_first['image']) . '" fetchpriority="high">';
}

function hero_words($text) {
    $out = '';
    foreach (preg_split('/\s+/', trim($text)) as $i => $w) $out .= '<span class="hero-word" style="--i:' . $i . '">' . h($w) . '</span> ';
    return $out;
}

include __DIR__ . '/includes/site-header.php';
?>
<main id="main">

  <!-- ============ HERO ============ -->
  <?php if (section_enabled('hero')): ?>
  <section class="relative bg-ink text-white overflow-hidden isolate" aria-label="Featured">
    <!-- Background slides -->
    <div class="absolute inset-0 -z-10" aria-hidden="true">
      <?php foreach ($banners as $i => $b): ?>
      <div class="hero-bg absolute inset-0 <?= $i === 0 ? 'is-active' : '' ?>">
        <img src="<?= UPLOAD_URL . h($b['image']) ?>" alt="" class="absolute inset-0 w-full h-full object-cover opacity-35" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async" width="1920" height="1080">
      </div>
      <?php endforeach; ?>
      <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/85 to-ink/40"></div>
      <div class="absolute inset-0 bg-grid [mask-image:radial-gradient(ellipse_at_70%_40%,#000_20%,transparent_70%)]"></div>
      <div class="absolute top-1/3 right-[12%] w-[520px] h-[520px] rounded-full bg-ignite/30 blur-[120px]"></div>
    </div>

    <div class="container-x relative grid lg:grid-cols-12 gap-8 items-center min-h-[640px] lg:min-h-[720px] py-16 lg:py-10">
      <div class="lg:col-span-6 relative z-10">
        <?php foreach ($banners ?: [null] as $i => $b): ?>
        <div class="hero-slide <?= $i === 0 ? 'is-active' : 'absolute inset-x-0 top-1/2 -translate-y-1/2' ?>" <?= $i ? 'aria-hidden="true"' : '' ?>>
          <p class="eyebrow mb-5"><?= h($b['subtitle'] ?? '' ?: setting('site_tagline', 'Premium Sports Equipment')) ?></p>
          <?php $title = $b['title'] ?? '' ?: setting('homepage_intro_title', setting('site_name')); ?>
          <<?= $i === 0 ? 'h1' : 'p' ?> class="font-display font-bold uppercase text-[44px] sm:text-6xl xl:text-7xl leading-[0.95] tracking-tight mb-6 [perspective:600px]"><?= hero_words($title) ?></<?= $i === 0 ? 'h1' : 'p' ?>>
          <p class="text-white/65 text-base sm:text-lg max-w-xl mb-8"><?= h(meta_text(setting('homepage_meta_description', ''), 170)) ?></p>
          <div class="flex flex-wrap gap-3">
            <?php if (!empty($b['button_text']) && !empty($b['button_url'])): ?>
            <a href="<?= h($b['button_url']) ?>" class="btn-primary btn-shine"><?= h($b['button_text']) ?> <i class="fa-solid fa-arrow-right"></i></a>
            <?php endif; ?>
            <a href="<?= $studio_product ? url('product/' . $studio_product['slug']) . '?customize' : url('kit-builder') ?>" class="btn-ghost-light"><i class="fa-solid fa-cube"></i> Design in 3D</a>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if ($features): ?>
        <ul class="mt-12 flex flex-wrap gap-x-6 gap-y-3 text-xs font-semibold uppercase tracking-wider text-white/60">
          <?php foreach (array_slice($features, 0, 3) as $f): ?>
          <li class="flex items-center gap-2"><i class="fa-solid <?= h($f['icon']) ?> text-ignite"></i><?= h($f['title']) ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>

      <div class="lg:col-span-6 relative h-[380px] sm:h-[480px] lg:h-[640px]">
        <?php if ($hero_3d): ?>
        <div id="hero3d" data-model="<?= h(setting('hero_3d_model', 'jersey')) ?>" data-label="<?= h(explode(' ', setting('site_name', 'BuiltCo'))[0]) ?>" data-mobile="1"
             class="absolute inset-0 cursor-grab [&.is-ready_.hero-fallback]:opacity-0" aria-label="Interactive 3D kit — drag to rotate">
          <div class="hero-fallback absolute inset-0 flex items-center justify-center transition-opacity duration-700" aria-hidden="true">
            <?php if ($hero_first): ?>
            <img src="<?= UPLOAD_URL . h($hero_first['image']) ?>" alt="" class="w-4/5 aspect-square object-cover rounded-[40px] rotate-3 shadow-2xl opacity-80" width="640" height="640">
            <?php endif; ?>
          </div>
        </div>
        <?php elseif ($hero_first): ?>
        <img src="<?= UPLOAD_URL . h($hero_first['image']) ?>" alt="<?= h($hero_first['image_alt'] ?: $hero_first['title']) ?>" class="absolute inset-0 w-full h-full object-cover rounded-[40px]" width="800" height="800">
        <?php endif; ?>
        <!-- Floating glass badges -->
        <div class="absolute left-0 sm:left-4 top-8 glass-dark rounded-2xl px-4 py-3 animate-floaty pointer-events-none hidden sm:block">
          <p class="text-[10px] uppercase tracking-[0.2em] text-white/50">Live</p>
          <p class="font-display font-semibold uppercase text-sm"><i class="fa-solid fa-cube text-ignite mr-1"></i> 3D Customizer</p>
        </div>
        <div class="absolute right-0 sm:right-6 bottom-16 glass-dark rounded-2xl px-4 py-3 animate-floaty pointer-events-none [animation-delay:-3s]">
          <p class="text-[10px] uppercase tracking-[0.2em] text-white/50">Your name · Your number</p>
          <p class="font-display font-semibold uppercase text-sm"><i class="fa-solid fa-hand-pointer text-ignite mr-1"></i> Drag to rotate</p>
        </div>
      </div>
    </div>

    <?php if (count($banners) > 1): ?>
    <div class="container-x relative pb-8 flex items-center gap-4">
      <button type="button" data-hero-prev class="w-10 h-10 rounded-full border border-white/20 hover:bg-white hover:text-ink transition" aria-label="Previous slide"><i class="fa-solid fa-arrow-left"></i></button>
      <div class="flex gap-2">
        <?php foreach ($banners as $i => $b): ?>
        <button type="button" class="hero-dot h-1.5 rounded-full bg-white/25 transition-all duration-500 w-6 [&.is-active]:w-12 [&.is-active]:bg-ignite <?= $i === 0 ? 'is-active' : '' ?>" data-dot="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
      <button type="button" data-hero-next class="w-10 h-10 rounded-full border border-white/20 hover:bg-white hover:text-ink transition" aria-label="Next slide"><i class="fa-solid fa-arrow-right"></i></button>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <!-- ============ TRUST BAR ============ -->
  <?php if ($features): ?>
  <section class="bg-white border-b border-black/5" aria-label="Why shop with us">
    <div class="container-x py-8 grid grid-cols-2 lg:grid-cols-4 gap-6">
      <?php foreach ($features as $f): ?>
      <div class="flex items-center gap-4" data-reveal>
        <div class="w-12 h-12 rounded-2xl bg-ignite/10 text-ignite flex items-center justify-center shrink-0 text-lg"><i class="fa-solid <?= h($f['icon']) ?>"></i></div>
        <div>
          <p class="font-display font-semibold text-sm uppercase tracking-wide"><?= h($f['title']) ?></p>
          <?php if ($f['description']): ?><p class="text-xs text-slate-500"><?= h($f['description']) ?></p><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ SHOP BY CATEGORY ============ -->
  <?php if ($cats): ?>
  <section class="section container-x" aria-labelledby="cat-title">
    <div class="section-head">
      <div>
        <p class="eyebrow mb-3">Explore</p>
        <h2 id="cat-title" class="section-title"><?= h(setting('section_category_title', 'Shop by Category')) ?></h2>
      </div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 auto-rows-[200px] sm:auto-rows-[240px] gap-4">
      <?php foreach ($cats as $i => $c): $big = $i === 0 || $i === 5; ?>
      <a href="<?= url('category/' . $c['slug']) ?>" class="flip-3d group relative block <?= $big ? 'md:col-span-2 md:row-span-2' : '' ?>" data-reveal>
        <div class="flip-3d-inner tilt-3d relative h-full rounded-3xl overflow-hidden bg-slate-200">
          <?php if ($c['image']): ?>
          <img src="<?= UPLOAD_URL . h($c['image']) ?>" alt="<?= h($c['image_alt'] ?: $c['name']) ?>" loading="lazy" decoding="async" width="800" height="800" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition duration-[1.2s]">
          <?php else: ?>
          <div class="absolute inset-0 flex items-center justify-center text-slate-400"><i class="fa-solid fa-image text-3xl"></i></div>
          <?php endif; ?>
          <div class="absolute inset-0 bg-gradient-to-t from-ink/85 via-ink/20 to-transparent"></div>
          <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6 flex items-end justify-between gap-3">
            <div>
              <h3 class="font-display font-bold uppercase text-white <?= $big ? 'text-2xl sm:text-4xl' : 'text-lg sm:text-xl' ?> leading-none"><?= h($c['name']) ?></h3>
              <?php if (!empty($c['short_description']) && $big): ?><p class="text-white/70 text-sm mt-2 max-w-sm hidden sm:block"><?= h(meta_text($c['short_description'], 90)) ?></p><?php endif; ?>
            </div>
            <span class="w-10 h-10 rounded-full bg-white text-ink flex items-center justify-center shrink-0 group-hover:bg-ignite group-hover:text-white transition"><i class="fa-solid fa-arrow-right -rotate-45 group-hover:rotate-0 transition"></i></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ 3D STUDIO PROMO ============ -->
  <section class="relative bg-ink text-white overflow-hidden" aria-labelledby="studio-title">
    <div class="absolute inset-0 bg-grid opacity-60" aria-hidden="true"></div>
    <div class="absolute -left-40 bottom-0 w-[480px] h-[480px] rounded-full bg-ignite/25 blur-[120px]" aria-hidden="true"></div>
    <div class="container-x relative section grid lg:grid-cols-2 gap-14 items-center">
      <div>
        <p class="eyebrow mb-4">3D Design Studio</p>
        <h2 id="studio-title" class="section-title mb-6">Build your team kit <span class="text-stroke">in real&nbsp;time</span></h2>
        <p class="text-white/65 max-w-lg mb-10">Spin a live 3D model, pick your colours and patterns, drop in your club crest and sponsor logos, then add every player's name and number — we turn it into print-ready artwork automatically.</p>
        <ol class="grid sm:grid-cols-2 gap-4 mb-10">
          <?php foreach ([
            ['fa-palette', 'Colours & patterns', 'Kit presets, stripes, hoops, sash, camo & more'],
            ['fa-shield-halved', 'Logos, layered', 'Upload PNG/SVG, drag on the 3D model, rotate & resize'],
            ['fa-signature', 'Names & numbers', 'Arched names, outlines, 8 pro fonts'],
            ['fa-users', 'Whole-team roster', 'Paste your squad list — one click to cart'],
          ] as $n => $step): ?>
          <li class="glass-dark rounded-2xl p-5 hover:border-ignite/60 transition" data-reveal>
            <div class="flex items-center gap-3 mb-2">
              <span class="font-poster text-2xl text-ignite leading-none">0<?= $n + 1 ?></span>
              <i class="fa-solid <?= $step[0] ?> text-white/40"></i>
            </div>
            <p class="font-display font-semibold uppercase tracking-wide"><?= $step[1] ?></p>
            <p class="text-sm text-white/55 mt-1"><?= $step[2] ?></p>
          </li>
          <?php endforeach; ?>
        </ol>
        <div class="flex flex-wrap gap-3">
          <a href="<?= $studio_product ? url('product/' . $studio_product['slug']) . '?customize' : url('kit-builder') ?>" class="btn-primary btn-shine"><i class="fa-solid fa-wand-magic-sparkles"></i> Start Designing</a>
          <a href="<?= url('kit-builder') ?>" class="btn-ghost-light">Kit Builder</a>
        </div>
      </div>
      <div class="relative aspect-square max-w-[560px] w-full mx-auto" data-reveal>
        <div class="absolute inset-6 rounded-full border border-white/10 spin-slow" aria-hidden="true"></div>
        <div class="absolute inset-16 rounded-full border border-dashed border-ignite/40 spin-slow [animation-direction:reverse]" aria-hidden="true"></div>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="tilt-3d relative w-3/4 aspect-square rounded-[36px] bg-gradient-to-br from-white/10 to-white/0 border border-white/15 backdrop-blur flex items-center justify-center" data-tilt="14">
            <div class="text-center">
              <div class="font-poster text-[120px] sm:text-[160px] leading-none text-gradient">10</div>
              <p class="font-display uppercase tracking-[0.4em] text-white/70 -mt-2">Your Name</p>
            </div>
            <span class="absolute -top-4 -right-4 w-16 h-16 rounded-2xl bg-ignite shadow-glow flex items-center justify-center text-2xl rotate-12 pulse-ring"><i class="fa-solid fa-cube"></i></span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ NEW ARRIVALS (scroller) ============ -->
  <?php if ($new_arrivals): ?>
  <section class="section" aria-labelledby="new-title" data-scroller>
    <div class="container-x section-head">
      <div>
        <p class="eyebrow mb-3">Just dropped</p>
        <h2 id="new-title" class="section-title"><?= h(setting('section_new_arrivals_title', 'New Arrivals')) ?></h2>
      </div>
      <div class="flex gap-2">
        <button type="button" data-scroller-prev class="w-12 h-12 rounded-full border border-ink/15 hover:bg-ink hover:text-white transition" aria-label="Scroll left"><i class="fa-solid fa-arrow-left"></i></button>
        <button type="button" data-scroller-next class="w-12 h-12 rounded-full border border-ink/15 hover:bg-ink hover:text-white transition" aria-label="Scroll right"><i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </div>
    <div data-scroller-track class="flex gap-5 overflow-x-auto snap-x snap-mandatory scrollbar-none px-4 sm:px-6 lg:px-[max(2rem,calc((100vw-80rem)/2+2rem))] pb-4">
      <?php foreach ($new_arrivals as $p): ?>
      <div class="snap-start shrink-0 w-[70%] sm:w-[40%] md:w-[30%] lg:w-[23%]"><?= render_product_card($p, 'new') ?></div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ FEATURED PRODUCTS ============ -->
  <?php if ($featured_prods): ?>
  <section class="section bg-white border-y border-black/5" aria-labelledby="featured-title">
    <div class="container-x">
      <div class="section-head">
        <div>
          <p class="eyebrow mb-3">Hand-picked</p>
          <h2 id="featured-title" class="section-title"><?= h(setting('section_featured_title', 'Featured Products')) ?></h2>
        </div>
        <a href="<?= url('shop') ?>" class="link-arrow">Shop all <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-5 gap-y-10">
        <?php foreach ($featured_prods as $p) echo render_product_card($p, 'featured'); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ PROMO BANNERS ============ -->
  <?php if ($promo_banners): ?>
  <section class="container-x section" aria-label="Promotions">
    <div class="grid grid-cols-1 <?= count($promo_banners) > 1 ? 'md:grid-cols-2' : '' ?> gap-5">
      <?php foreach ($promo_banners as $pb): ?>
      <a href="<?= h($pb['button_url'] ?: '#') ?>" class="tilt-3d relative rounded-3xl overflow-hidden block group min-h-[280px] sm:min-h-[340px]" data-tilt="5" data-reveal>
        <img src="<?= UPLOAD_URL . h($pb['image']) ?>" alt="<?= h($pb['image_alt'] ?: $pb['title']) ?>" loading="lazy" decoding="async" width="1200" height="600" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-[1.2s]">
        <div class="absolute inset-0 bg-gradient-to-tr from-ink/90 via-ink/40 to-transparent"></div>
        <div class="relative z-10 h-full flex flex-col justify-end p-8 sm:p-10 min-h-[280px] sm:min-h-[340px]">
          <?php if ($pb['subtitle']): ?><p class="eyebrow mb-2"><?= h($pb['subtitle']) ?></p><?php endif; ?>
          <?php if ($pb['title']): ?><h3 class="font-display font-bold uppercase text-white text-3xl sm:text-4xl leading-none mb-4 max-w-md"><?= h($pb['title']) ?></h3><?php endif; ?>
          <?php if ($pb['button_text']): ?><span class="btn-light btn-sm w-fit"><?= h($pb['button_text']) ?> <i class="fa-solid fa-arrow-right text-xs"></i></span><?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ BEST SELLERS ============ -->
  <?php if ($best_sellers): ?>
  <section class="container-x section pt-0" aria-labelledby="best-title">
    <div class="section-head">
      <div>
        <p class="eyebrow mb-3">Most loved</p>
        <h2 id="best-title" class="section-title"><?= h(setting('section_bestsellers_title', 'Best Sellers')) ?></h2>
      </div>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-5 gap-y-10">
      <?php foreach ($best_sellers as $p) echo render_product_card($p); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ COMING SOON ============ -->
  <?php if ($coming_soon): ?>
  <section class="section bg-white border-y border-black/5" aria-labelledby="soon-title">
    <div class="container-x">
      <div class="section-head">
        <div>
          <p class="eyebrow mb-3">On the way</p>
          <h2 id="soon-title" class="section-title"><?= h(setting('section_comingsoon_title', 'Coming Soon')) ?></h2>
        </div>
      </div>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-5 gap-y-10">
        <?php foreach ($coming_soon as $p) echo render_product_card($p, 'coming_soon'); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ WHY CHOOSE US ============ -->
  <?php $intro_title = section_enabled('why') ? setting('homepage_intro_title', '') : ''; $intro_content = setting('homepage_intro_content', ''); ?>
  <?php if ($intro_title || $intro_content): ?>
  <section class="section relative overflow-hidden" aria-labelledby="why-title">
    <div class="absolute inset-0 bg-grid-dark [mask-image:radial-gradient(ellipse_at_center,#000_30%,transparent_70%)]" aria-hidden="true"></div>
    <div class="container-x relative max-w-4xl text-center" data-reveal>
      <p class="eyebrow mb-4 justify-center"><?= h(setting('section_why_title', 'Why Choose Us')) ?></p>
      <?php if ($intro_title): ?><h2 id="why-title" class="section-title mb-6"><?= h($intro_title) ?></h2><?php endif; ?>
      <?php if ($intro_content): ?><p class="text-slate-600 text-lg leading-relaxed"><?= h($intro_content) ?></p><?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ TESTIMONIALS ============ -->
  <?php if ($testimonials): ?>
  <section class="container-x section pt-0" aria-labelledby="reviews-title">
    <div class="text-center mb-12">
      <p class="eyebrow mb-3 justify-center">Reviews</p>
      <h2 id="reviews-title" class="section-title"><?= h(setting('section_testimonials_title', 'What Our Customers Say')) ?></h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <?php foreach ($testimonials as $t): ?>
      <figure class="card tilt-3d p-7 flex flex-col" data-tilt="5" data-reveal>
        <i class="fa-solid fa-quote-left text-3xl text-ignite/25 mb-4" aria-hidden="true"></i>
        <?= star_html($t['rating'], 'text-sm') ?>
        <blockquote class="text-slate-600 leading-relaxed my-4 flex-1">&ldquo;<?= h($t['quote']) ?>&rdquo;</blockquote>
        <figcaption class="flex items-center gap-3 pt-4 border-t border-slate-100">
          <?php if ($t['photo']): ?><img src="<?= UPLOAD_URL . h($t['photo']) ?>" alt="<?= h($t['name']) ?>" loading="lazy" width="44" height="44" class="w-11 h-11 rounded-full object-cover"><?php else: ?><div class="w-11 h-11 rounded-full bg-ink text-white flex items-center justify-center text-sm font-bold"><?= h(strtoupper(substr($t['name'], 0, 1))) ?></div><?php endif; ?>
          <div>
            <div class="text-sm font-bold"><?= h($t['name']) ?></div>
            <?php if ($t['role']): ?><div class="text-xs text-slate-400"><?= h($t['role']) ?></div><?php endif; ?>
          </div>
        </figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ CERTIFICATIONS ============ -->
  <?php if ($certifications): ?>
  <section class="border-y border-black/5 bg-white" aria-labelledby="cert-title">
    <div class="container-x py-14">
      <h2 id="cert-title" class="font-display font-semibold uppercase tracking-[0.2em] text-sm text-center text-slate-500 mb-8"><?= h(setting('section_certifications_title', 'Certifications & Memberships')) ?></h2>
      <div class="flex flex-wrap justify-center gap-4">
        <?php foreach ($certifications as $c): ?>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 px-5 py-3 hover:border-ignite transition" data-reveal>
          <?php if ($c['image']): ?>
          <img src="<?= UPLOAD_URL . h($c['image']) ?>" alt="<?= h($c['title']) ?>" loading="lazy" width="44" height="44" class="w-11 h-11 object-cover rounded-lg">
          <?php else: ?>
          <div class="w-11 h-11 rounded-lg bg-ignite/10 text-ignite flex items-center justify-center"><i class="fa-solid fa-certificate"></i></div>
          <?php endif; ?>
          <div>
            <div class="font-display font-semibold text-sm uppercase"><?= h($c['title']) ?></div>
            <?php if ($c['issuer']): ?><div class="text-xs text-slate-400"><?= h($c['issuer']) ?></div><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ NEWSLETTER ============ -->
  <?php if (section_enabled('newsletter')): ?>
  <section id="newsletter" class="container-x section" aria-labelledby="nl-title">
    <div class="relative rounded-[32px] bg-white border border-black/5 shadow-card overflow-hidden px-6 py-14 sm:px-16 text-center" data-reveal>
      <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[600px] h-[300px] rounded-full bg-ignite/15 blur-3xl" aria-hidden="true"></div>
      <div class="relative max-w-xl mx-auto">
        <?php $nl_success = get_flash('success'); $nl_error = get_flash('error'); ?>
        <?php if ($nl_success): ?><p class="mb-4 inline-flex items-center gap-2 rounded-full bg-emerald-50 text-emerald-700 px-4 py-2 text-sm font-semibold" role="status"><i class="fa-solid fa-circle-check"></i><?= h($nl_success) ?></p><?php endif; ?>
        <?php if ($nl_error): ?><p class="mb-4 inline-flex items-center gap-2 rounded-full bg-red-50 text-red-700 px-4 py-2 text-sm font-semibold" role="alert"><i class="fa-solid fa-circle-exclamation"></i><?= h($nl_error) ?></p><?php endif; ?>
        <p class="eyebrow mb-3 justify-center">Newsletter</p>
        <h2 id="nl-title" class="section-title mb-3"><?= h(setting('section_newsletter_title', 'Join Our Newsletter')) ?></h2>
        <p class="text-slate-500 mb-8"><?= h(setting('section_newsletter_subtitle', '')) ?></p>
        <form method="POST" class="flex flex-col sm:flex-row gap-3">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="subscribe">
          <label for="nlEmail" class="sr-only">Email address</label>
          <input id="nlEmail" type="email" name="email" required placeholder="Enter your email" autocomplete="email" class="input flex-1 !rounded-full !px-6">
          <button type="submit" class="btn-primary btn-shine">Subscribe <i class="fa-solid fa-paper-plane"></i></button>
        </form>
      </div>
    </div>
  </section>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
