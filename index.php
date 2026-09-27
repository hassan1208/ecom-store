<?php
// index.php — Homepage. Every section below is toggled on/off and titled from
// Admin → Homepage Manager.
require_once __DIR__ . '/includes/config.php';

// Newsletter subscribe (this section posts back to the homepage itself)
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

include __DIR__ . '/includes/site-header.php';
?>
<main>

  <!-- ============ HERO ============ -->
  <?php if ($banners): ?>
  <section class="relative h-[70vh] min-h-[420px] max-h-[720px] overflow-hidden bg-ink" id="heroSlider">
    <?php foreach ($banners as $i => $b): ?>
    <div class="hero-slide absolute inset-0 transition-opacity duration-700 <?= $i === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0' ?>" data-slide="<?= $i ?>">
      <img src="<?= UPLOAD_URL . h($b['image']) ?>" alt="<?= h($b['image_alt'] ?: $b['title']) ?>"
           class="absolute inset-0 w-full h-full object-cover" <?= $i === 0 ? '' : 'loading="lazy"' ?>>
      <div class="absolute inset-0 bg-gradient-to-t from-ink/90 via-ink/30 to-ink/10"></div>
      <div class="relative z-20 h-full max-w-7xl mx-auto px-4 sm:px-6 flex flex-col justify-end pb-16">
        <?php if ($b['subtitle']): ?><p class="text-ignite font-display font-semibold uppercase tracking-[0.2em] text-sm mb-3"><?= h($b['subtitle']) ?></p><?php endif; ?>
        <?php if ($b['title']): ?>
        <<?= $i === 0 ? 'h1' : 'p' ?> class="font-display font-bold text-white text-4xl sm:text-5xl lg:text-6xl leading-[1.05] max-w-2xl mb-6"><?= h($b['title']) ?></<?= $i === 0 ? 'h1' : 'p' ?>>
        <?php endif; ?>
        <?php if ($b['button_text'] && $b['button_url']): ?>
        <a href="<?= h($b['button_url']) ?>" class="inline-flex items-center gap-2 bg-ignite hover:bg-ignite-dark text-white font-display font-semibold uppercase tracking-wide text-sm px-7 py-3.5 rounded-full w-fit transition">
          <?= h($b['button_text']) ?> <i class="fa-solid fa-arrow-right"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <div id="hero3dCanvas" class="absolute inset-0 z-[12] pointer-events-none"></div>
    <?php if (count($banners) > 1): ?>
    <div class="absolute bottom-6 right-6 sm:right-10 z-20 flex gap-2">
      <?php foreach ($banners as $i => $b): ?>
      <button class="hero-dot w-2.5 h-2.5 rounded-full transition <?= $i === 0 ? 'bg-ignite w-7' : 'bg-white/40' ?>" data-dot="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js"></script>
  <script>
  (function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (typeof THREE === 'undefined') return;
    const mount = document.getElementById('hero3dCanvas');
    if (!mount) return;

    let renderer;
    try {
      renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    } catch (e) { return; }

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(50, mount.clientWidth / mount.clientHeight, 0.1, 100);
    camera.position.z = 9;

    function resize() {
      const w = mount.clientWidth, h = mount.clientHeight;
      renderer.setSize(w, h);
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
    }
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    mount.appendChild(renderer.domElement);
    resize();
    window.addEventListener('resize', resize);

    const brandColor = <?= json_encode(setting('theme_primary_color', '#ff4d2e')) ?>;
    const geometries = [
      new THREE.IcosahedronGeometry(1.1, 0),
      new THREE.TorusGeometry(0.9, 0.28, 8, 24),
      new THREE.OctahedronGeometry(1, 0),
    ];
    const shapes = geometries.map((geo, i) => {
      const mat = new THREE.MeshBasicMaterial({ color: brandColor, wireframe: true, transparent: true, opacity: 0.35 });
      const mesh = new THREE.Mesh(geo, mat);
      mesh.position.set((i - 1) * 4.2, Math.sin(i) * 1.5, -2 - i);
      scene.add(mesh);
      return mesh;
    });

    let mouseX = 0, mouseY = 0;
    mount.parentElement.addEventListener('mousemove', function (e) {
      const rect = mount.getBoundingClientRect();
      mouseX = ((e.clientX - rect.left) / rect.width - 0.5) * 2;
      mouseY = ((e.clientY - rect.top) / rect.height - 0.5) * 2;
    });

    const clock = new THREE.Clock();
    function animate() {
      requestAnimationFrame(animate);
      const t = clock.getElapsedTime();
      shapes.forEach(function (mesh, i) {
        mesh.rotation.x = t * 0.15 + i;
        mesh.rotation.y = t * 0.2 + i;
        mesh.position.y += (Math.sin(t * 0.6 + i) * 0.003);
      });
      camera.position.x += (mouseX * 1.2 - camera.position.x) * 0.03;
      camera.position.y += (-mouseY * 0.8 - camera.position.y) * 0.03;
      camera.lookAt(0, 0, 0);
      renderer.render(scene, camera);
    }
    animate();
  })();
  </script>
  <?php elseif (section_enabled('hero')): ?>
  <section class="bg-ink py-24 text-center">
    <h1 class="font-display font-bold text-white text-4xl sm:text-5xl"><?= h(setting('homepage_intro_title', setting('site_name'))) ?></h1>
  </section>
  <?php endif; ?>

  <!-- ============ TRUST BAR ============ -->
  <?php if ($features): ?>
  <section class="border-b border-black/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 grid grid-cols-2 md:grid-cols-4 gap-6">
      <?php foreach ($features as $f): ?>
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-full bg-ignite/10 text-ignite flex items-center justify-center shrink-0"><i class="fa-solid <?= h($f['icon']) ?>"></i></div>
        <div>
          <div class="font-display font-semibold text-sm uppercase tracking-wide"><?= h($f['title']) ?></div>
          <?php if ($f['description']): ?><div class="text-xs text-slate-500"><?= h($f['description']) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ SHOP BY CATEGORY ============ -->
  <?php if ($cats): ?>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
    <div class="text-center mb-12">
      <p class="text-ignite font-display font-semibold uppercase tracking-[0.2em] text-xs mb-2">Explore</p>
      <h2 class="font-display font-bold text-3xl sm:text-4xl"><?= h(setting('section_category_title', 'Shop by Category')) ?></h2>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
      <?php foreach ($cats as $c): ?>
      <a href="<?= url('category/' . $c['slug']) ?>" class="group relative rounded-2xl overflow-hidden aspect-[4/5] bg-slate-100 block">
        <?php if ($c['image']): ?>
        <img src="<?= UPLOAD_URL . h($c['image']) ?>" alt="<?= h($c['image_alt'] ?: $c['name']) ?>" loading="lazy" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition duration-500">
        <?php else: ?>
        <div class="absolute inset-0 flex items-center justify-center text-slate-300"><i class="fa-solid fa-image text-3xl"></i></div>
        <?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/10 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 p-4"><h3 class="font-display font-semibold text-white text-base sm:text-lg"><?= h($c['name']) ?></h3></div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ NEW ARRIVALS ============ -->
  <?php if ($new_arrivals): ?>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
    <h2 class="font-display font-bold text-2xl sm:text-3xl mb-8"><?= h(setting('section_new_arrivals_title', 'New Arrivals')) ?></h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
      <?php foreach ($new_arrivals as $p) echo render_product_card($p, 'new'); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ FEATURED PRODUCTS ============ -->
  <?php if ($featured_prods): ?>
  <section class="bg-slate-50 border-y border-black/5 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <h2 class="font-display font-bold text-2xl sm:text-3xl mb-8"><?= h(setting('section_featured_title', 'Featured Products')) ?></h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
        <?php foreach ($featured_prods as $p) echo render_product_card($p, 'featured'); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ PROMO BANNERS ============ -->
  <?php if ($promo_banners): ?>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
    <div class="grid grid-cols-1 <?= count($promo_banners) > 1 ? 'md:grid-cols-2' : '' ?> gap-5">
      <?php foreach ($promo_banners as $pb): ?>
      <a href="<?= h($pb['button_url'] ?: '#') ?>" class="relative rounded-2xl overflow-hidden block group min-h-[220px]">
        <img src="<?= UPLOAD_URL . h($pb['image']) ?>" alt="<?= h($pb['image_alt'] ?: $pb['title']) ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500">
        <div class="absolute inset-0 bg-gradient-to-t from-ink/70 via-ink/10 to-transparent"></div>
        <div class="relative z-10 h-full flex flex-col justify-end p-6">
          <?php if ($pb['subtitle']): ?><p class="text-ignite font-display font-semibold uppercase tracking-widest text-xs mb-1"><?= h($pb['subtitle']) ?></p><?php endif; ?>
          <?php if ($pb['title']): ?><h3 class="font-display font-bold text-white text-xl mb-2"><?= h($pb['title']) ?></h3><?php endif; ?>
          <?php if ($pb['button_text']): ?><span class="text-white text-sm font-semibold inline-flex items-center gap-1"><?= h($pb['button_text']) ?> <i class="fa-solid fa-arrow-right text-xs"></i></span><?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ BEST SELLERS ============ -->
  <?php if ($best_sellers): ?>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
    <h2 class="font-display font-bold text-2xl sm:text-3xl mb-8"><?= h(setting('section_bestsellers_title', 'Best Sellers')) ?></h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
      <?php foreach ($best_sellers as $p) echo render_product_card($p); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ COMING SOON ============ -->
  <?php if ($coming_soon): ?>
  <section class="bg-slate-50 border-y border-black/5 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <h2 class="font-display font-bold text-2xl sm:text-3xl mb-8"><?= h(setting('section_comingsoon_title', 'Coming Soon')) ?></h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
        <?php foreach ($coming_soon as $p) echo render_product_card($p, 'coming_soon'); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ WHY CHOOSE US ============ -->
  <?php $intro_title = section_enabled('why') ? setting('homepage_intro_title', '') : ''; $intro_content = setting('homepage_intro_content', ''); ?>
  <?php if ($intro_title || $intro_content): ?>
  <section class="border-y border-black/5">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-20 text-center">
      <?php if ($intro_title): ?><h2 class="font-display font-bold text-2xl sm:text-3xl mb-4"><?= h($intro_title) ?></h2><?php endif; ?>
      <?php if ($intro_content): ?><p class="text-slate-500 leading-relaxed"><?= h($intro_content) ?></p><?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ TESTIMONIALS ============ -->
  <?php if ($testimonials): ?>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
    <h2 class="font-display font-bold text-2xl sm:text-3xl text-center mb-10"><?= h(setting('section_testimonials_title', 'What Our Customers Say')) ?></h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <?php foreach ($testimonials as $t): ?>
      <div class="rounded-2xl border border-slate-200 p-6">
        <?= star_html($t['rating'], 'text-sm') ?>
        <p class="text-sm text-slate-600 leading-relaxed my-4">&ldquo;<?= h($t['quote']) ?>&rdquo;</p>
        <div class="flex items-center gap-3">
          <?php if ($t['photo']): ?><img src="<?= UPLOAD_URL . h($t['photo']) ?>" class="w-10 h-10 rounded-full object-cover"><?php else: ?><div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xs font-bold"><?= h(strtoupper(substr($t['name'], 0, 1))) ?></div><?php endif; ?>
          <div>
            <div class="text-sm font-semibold"><?= h($t['name']) ?></div>
            <?php if ($t['role']): ?><div class="text-xs text-slate-400"><?= h($t['role']) ?></div><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ CERTIFICATIONS ============ -->
  <?php if ($certifications): ?>
  <section class="border-y border-black/5 bg-slate-50/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
      <h2 class="font-display font-bold text-2xl sm:text-3xl text-center mb-10"><?= h(setting('section_certifications_title', 'Certifications & Memberships')) ?></h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
        <?php foreach ($certifications as $c): ?>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center">
          <?php if ($c['image']): ?>
          <img src="<?= UPLOAD_URL . h($c['image']) ?>" alt="<?= h($c['title']) ?>" class="w-16 h-16 object-cover rounded-lg mx-auto mb-3">
          <?php else: ?>
          <div class="w-16 h-16 rounded-lg bg-ignite/10 text-ignite flex items-center justify-center mx-auto mb-3"><i class="fa-solid fa-certificate text-xl"></i></div>
          <?php endif; ?>
          <div class="font-display font-semibold text-sm"><?= h($c['title']) ?></div>
          <?php if ($c['issuer']): ?><div class="text-xs text-slate-400 mt-1"><?= h($c['issuer']) ?></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============ NEWSLETTER ============ -->
  <?php if (section_enabled('newsletter')): ?>
  <section id="newsletter" class="bg-ink text-white text-center">
    <div class="max-w-lg mx-auto px-4 sm:px-6 py-16">
      <?php $nl_success = get_flash('success'); $nl_error = get_flash('error'); ?>
      <?php if ($nl_success): ?><p class="text-emerald-400 font-semibold mb-4"><?= h($nl_success) ?></p><?php endif; ?>
      <?php if ($nl_error): ?><p class="text-red-400 font-semibold mb-4"><?= h($nl_error) ?></p><?php endif; ?>
      <h2 class="font-display font-bold text-2xl mb-2"><?= h(setting('section_newsletter_title', 'Join Our Newsletter')) ?></h2>
      <p class="text-white/50 text-sm mb-6"><?= h(setting('section_newsletter_subtitle', '')) ?></p>
      <form method="POST" class="flex gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="subscribe">
        <input type="email" name="email" required placeholder="Enter your email" class="flex-1 rounded-full px-5 py-3 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-ignite">
        <button type="submit" class="bg-ignite hover:bg-ignite-dark text-white font-display font-semibold uppercase tracking-wide text-sm px-6 rounded-full transition">Subscribe</button>
      </form>
    </div>
  </section>
  <?php endif; ?>

</main>

<?php if (count($banners) > 1): ?>
<script>
(function () {
  const slides = document.querySelectorAll('.hero-slide');
  const dots = document.querySelectorAll('.hero-dot');
  let idx = 0;
  function show(n) {
    slides.forEach((s, i) => s.classList.toggle('opacity-100', i === n) || s.classList.toggle('opacity-0', i !== n) || s.classList.toggle('z-10', i === n) || s.classList.toggle('z-0', i !== n));
    dots.forEach((d, i) => {
      d.classList.toggle('bg-ignite', i === n); d.classList.toggle('w-7', i === n);
      d.classList.toggle('bg-white/40', i !== n); d.classList.toggle('w-2.5', i !== n);
    });
    idx = n;
  }
  dots.forEach(d => d.addEventListener('click', () => show(parseInt(d.dataset.dot))));
  setInterval(() => show((idx + 1) % slides.length), 5500);
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
